<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'idioma.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'email.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'gym.php';
start_app_session();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$plans = [
    'base' => 'Base',
    'power' => 'Power',
    'personal' => 'Personal Training',
];
$times = ['07:00', '08:00', '09:00', '12:00', '17:00', '18:00', '19:00', '20:00'];
$message = 'Não foi possível processar o pedido. Volta à página inicial e tenta novamente.';
$isSuccess = false;
$statusCode = 400;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (
        !isset($_SESSION['csrf_token']) ||
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $message = 'A sessão expirou. Volta à página inicial e tenta novamente.';
    } else {
        $values = [];
        foreach (['name', 'email', 'phone', 'plan', 'date', 'time'] as $field) {
            $posted = $_POST[$field] ?? '';
            $values[$field] = is_string($posted) ? trim($posted) : '';
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $values['date']);
        $dateErrors = DateTimeImmutable::getLastErrors();
        $validDate = $date &&
            ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0)) &&
            $date->format('Y-m-d') === $values['date'] &&
            $date >= new DateTimeImmutable('today');

        if (
            strlen($values['name']) < 2 ||
            strlen($values['name']) > 120 ||
            strlen($values['email']) > 254 ||
            !filter_var($values['email'], FILTER_VALIDATE_EMAIL) ||
            ($_POST['privacy_consent'] ?? '') !== '1' ||
            strlen(preg_replace('/\D+/', '', $values['phone'])) < 9 ||
            strlen($values['phone']) > 30 ||
            !array_key_exists($values['plan'], $plans) ||
            !in_array($values['time'], $times, true) ||
            !$validDate
        ) {
            $message = 'Verifica os dados do formulário e tenta novamente.';
        } else {
            $booking = [
                'created_at' => date(DATE_ATOM),
                'name' => $values['name'],
                'email' => $values['email'],
                'phone' => $values['phone'],
                'plan' => $plans[$values['plan']],
                'plan_key' => $values['plan'],
                'date' => $values['date'],
                'time' => $values['time'],
            ];
            $storageError = null;

            try {
                $clientQuery = app_database()->prepare(
                    'SELECT id FROM client_accounts WHERE email = :email AND is_active = 1 LIMIT 1'
                );
                $clientQuery->execute(['email' => $booking['email']]);
                $clientId = $clientQuery->fetchColumn();

                $statement = app_database()->prepare(
                    'INSERT INTO bookings (client_account_id, name, email, phone, plan_key, booking_date, booking_time) '
                    . 'VALUES (:client_account_id, :name, :email, :phone, :plan_key, :booking_date, :booking_time)'
                );
                $statement->execute([
                    'client_account_id' => $clientId === false ? null : (int) $clientId,
                    'name' => $booking['name'],
                    'email' => $booking['email'],
                    'phone' => $booking['phone'],
                    'plan_key' => $booking['plan_key'],
                    'booking_date' => $booking['date'],
                    'booking_time' => $booking['time'],
                ]);
            } catch (PDOException $error) {
                $storageError = $error;
            } catch (RuntimeException $error) {
                $storageError = $error;
            }

            if ($storageError !== null) {
                error_log('Falha ao guardar pedido de horário na base de dados: ' . $storageError->getMessage());
                $message = 'Não foi possível guardar o pedido. Tenta novamente mais tarde.';
                $statusCode = $storageError instanceof RuntimeException ? 503 : 500;
            } else {
                $emailSent = false;
                set_error_handler(static function ($severity, $errorMessage, $file, $line) {
                    throw new ErrorException($errorMessage, 0, $severity, $file, $line);
                });
                try {
                    $gymSettings = app_gym_settings();
                    $emailSent = send_booking_notification_email($booking, $gymSettings['contact_email']);
                } catch (ErrorException $error) {
                    error_log('Falha ao enviar email do pedido de horário: ' . $error->getMessage());
                } catch (PDOException $error) {
                    error_log('Falha ao obter a configuração de email: ' . $error->getMessage());
                } catch (RuntimeException $error) {
                    error_log('Falha ao obter a configuração de email: ' . $error->getMessage());
                } finally {
                    restore_error_handler();
                }

                if ($emailSent) {
                    $message = 'Pedido enviado com sucesso! A equipa irá confirmar a disponibilidade contigo.';
                    $isSuccess = true;
                    $statusCode = 200;
                } else {
                    error_log('O PHP mail() não confirmou o envio do pedido de horário.');
                    $message = 'O pedido foi guardado, mas não foi possível enviá-lo por email. Contacta a receção para confirmar.';
                    $statusCode = 500;
                }
            }
        }
    }
} else {
    $message = 'Envia o formulário de marcação para pedir um horário.';
    $statusCode = 405;
}

http_response_code($statusCode);
?>
<!doctype html>
<html lang="<?= e(app_language_tag()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090909">
    <title><?= e(t('Pedido de horário')) ?> — PowerLift Gym</title>
    <style>
        :root { color-scheme: dark; --bg: #090909; --red: #ed1025; --text: #f5f5f5; --muted: #b9b9b9; }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            background: radial-gradient(ellipse at 50% 0, rgba(237, 16, 37, .13), transparent 42%), var(--bg);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.55;
        }
        main { width: min(100%, 520px); padding: 30px; border: 1px solid #292929; border-radius: 16px; background: #111; }
        h1 { margin: 0 0 12px; font-size: 28px; }
        p { color: var(--muted); }
        .message { color: <?= $isSuccess ? '#b9f1ce' : '#ffc0c7' ?>; }
        a { display: inline-block; margin-top: 12px; color: #fff; font-weight: 700; }
        a:hover { color: var(--red); }
        .language-switch { display: flex; justify-content: flex-end; align-items: center; gap: 6px; margin-bottom: 20px; }
        .language-switch select, .language-switch button { min-height: 36px; border: 1px solid #292929; border-radius: 7px; background: #090909; color: #fff; font: inherit; font-size: 13px; }
        .language-switch select { padding: 0 8px; }
        .language-switch button { padding: 0 10px; cursor: pointer; }
        .visually-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
    </style>
</head>
<body>
    <main>
        <?= language_selector() ?>
        <h1><?= e(t($isSuccess ? 'Pedido recebido' : 'Pedido de horário')) ?></h1>
        <p class="message" role="<?= $isSuccess ? 'status' : 'alert' ?>"><?= e(t($message)) ?></p>
        <a href="index.php#marcar"><?= e(t('Voltar à marcação')) ?></a>
    </main>
</body>
</html>
