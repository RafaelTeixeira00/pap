<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'idioma.php';
start_app_session();
$client = require_client();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$notice = $_SESSION['client_notice'] ?? '';
$noticeIsError = false;
unset($_SESSION['client_notice']);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['cancel_booking_id'])) {
    $token = $_POST['auth_csrf_token'] ?? '';
    $bookingIdInput = $_POST['cancel_booking_id'];
    $bookingId = is_string($bookingIdInput) ? filter_var($bookingIdInput, FILTER_VALIDATE_INT) : false;

    if (
        !is_string($token) ||
        !isset($_SESSION['auth_csrf_token']) ||
        !hash_equals($_SESSION['auth_csrf_token'], $token) ||
        $bookingId === false ||
        $bookingId < 1
    ) {
        $notice = 'Não foi possível cancelar a marcação. Atualiza a página e tenta novamente.';
        $noticeIsError = true;
        http_response_code(400);
    } else {
        $connection = app_database();
        $statement = $connection->prepare(
            'SELECT id, status, booking_date FROM bookings '
            . 'WHERE id = :id AND client_account_id = :client_id LIMIT 1'
        );
        $statement->execute([
            'id' => $bookingId,
            'client_id' => (int) $client['id'],
        ]);
        $booking = $statement->fetch();

        if (!$booking) {
            $notice = 'A marcação não existe ou não pertence à tua conta.';
            $noticeIsError = true;
            http_response_code(404);
        } elseif ($booking['status'] === 'cancelled') {
            $notice = 'Esta marcação já foi cancelada.';
        } elseif ($booking['booking_date'] < (new DateTimeImmutable('today'))->format('Y-m-d')) {
            $notice = 'Não é possível cancelar uma marcação passada.';
            $noticeIsError = true;
            http_response_code(400);
        } else {
            $update = $connection->prepare(
                "UPDATE bookings SET status = 'cancelled' WHERE id = :id AND client_account_id = :client_id"
            );
            $update->execute([
                'id' => $bookingId,
                'client_id' => (int) $client['id'],
            ]);
            $_SESSION['client_notice'] = 'A marcação foi cancelada.';
            header('Location: area-cliente.php');
            exit;
        }
    }
}

$bookingStatement = app_database()->prepare(
    'SELECT id, booking_date, booking_time, plan_key, status FROM bookings '
    . 'WHERE client_account_id = :client_id ORDER BY booking_date DESC, booking_time DESC'
);
$bookingStatement->execute(['client_id' => (int) $client['id']]);
$bookings = $bookingStatement->fetchAll();
?>
<!doctype html>
<html lang="<?= e(app_language_tag()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090909">
    <title><?= e(t('Área de cliente')) ?> — PowerLift Gym</title>
    <style>
        :root { color-scheme: dark; --bg: #090909; --panel: #111; --red: #ed1025; --text: #f5f5f5; --muted: #b9b9b9; --line: #292929; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; background: radial-gradient(ellipse at 50% 0, rgba(237, 16, 37, .13), transparent 42%), var(--bg); color: var(--text); font-family: Arial, Helvetica, sans-serif; line-height: 1.55; }
        main { width: min(100%, 520px); padding: clamp(24px, 7vw, 38px); border: 1px solid var(--line); border-radius: 16px; background: linear-gradient(145deg, #151515, var(--panel)); box-shadow: 0 25px 80px rgba(0, 0, 0, .35); }
        .eyebrow { margin: 0 0 7px; color: var(--red); font-size: 12px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 0; font-size: 32px; line-height: 1.15; }
        p { color: var(--muted); }
        .account { margin: 25px 0; padding: 16px; border: 1px solid var(--line); border-radius: 9px; background: #090909; }
        .account strong { display: block; color: #fff; }
        .button { min-height: 46px; padding: 0 20px; border: 0; border-radius: 9px; background: var(--red); color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .button:hover { background: #ff263b; }
        a { display: inline-block; margin-left: 12px; color: var(--muted); text-decoration: none; }
        a:hover { color: #fff; }
        .language-switch { display: flex; justify-content: flex-end; align-items: center; gap: 6px; margin-bottom: 20px; }
        .language-switch select, .language-switch button { min-height: 36px; border: 1px solid var(--line); border-radius: 7px; background: #090909; color: #fff; font: inherit; font-size: 13px; }
        .language-switch select { padding: 0 8px; }
        .language-switch button { padding: 0 10px; cursor: pointer; }
        .visually-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .notice { margin: 18px 0 0; padding: 12px; border: 1px solid #276443; border-radius: 8px; background: #10251a; color: #b9f1ce; }
        .notice.error { border-color: #74313b; background: #2a1115; color: #ffc0c7; }
        .bookings { margin: 25px 0; }
        .booking { margin: 10px 0; padding: 14px; border: 1px solid var(--line); border-radius: 9px; background: #090909; }
        .booking strong { display: block; color: #fff; }
        .booking p { margin: 4px 0 10px; }
        .cancel-button { min-height: 38px; padding: 0 12px; border: 0; border-radius: 7px; background: var(--red); color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
    <main>
        <?= language_selector() ?>
        <p class="eyebrow"><?= e(t('Área de cliente')) ?></p>
        <h1><?= e(t('Olá, ')) ?><?= e($client['name'] ?? '') ?></h1>
        <p><?= e(t('A tua sessão foi iniciada com sucesso.')) ?></p>
        <?php if ($notice !== ''): ?>
            <p class="notice<?= $noticeIsError ? ' error' : '' ?>" role="<?= $noticeIsError ? 'alert' : 'status' ?>"><?= e(t($notice)) ?></p>
        <?php endif; ?>
        <div class="account">
            <strong><?= e(t('Email da conta')) ?></strong>
            <?= e($client['email'] ?? '') ?>
        </div>
        <section class="bookings" aria-labelledby="bookings-title">
            <h2 id="bookings-title"><?= e(t('As minhas marcações')) ?></h2>
            <?php if (count($bookings) === 0): ?>
                <p><?= e(t('Ainda não tens marcações associadas à tua conta.')) ?></p>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <?php
                    $planNames = [
                        'base' => t('Base'),
                        'power' => t('Power'),
                        'personal' => t('Personal Training'),
                    ];
                    $canCancel = $booking['status'] !== 'cancelled' &&
                        $booking['booking_date'] >= (new DateTimeImmutable('today'))->format('Y-m-d');
                    ?>
                    <article class="booking">
                        <strong><?= e(date('d/m/Y', strtotime($booking['booking_date']))) ?> — <?= e(substr($booking['booking_time'], 0, 5)) ?></strong>
                        <p><?= e($planNames[$booking['plan_key']] ?? $booking['plan_key']) ?> · <?= e(t($booking['status'] === 'pending' ? 'Pendente' : ($booking['status'] === 'confirmed' ? 'Confirmada' : 'Cancelada'))) ?></p>
                        <?php if ($canCancel): ?>
                            <form method="post">
                                <input type="hidden" name="auth_csrf_token" value="<?= e(app_csrf_token()) ?>">
                                <input type="hidden" name="cancel_booking_id" value="<?= e($booking['id']) ?>">
                                <button class="cancel-button" type="submit"><?= e(t('Cancelar marcação')) ?></button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <form method="post" action="logout.php">
            <input type="hidden" name="auth_csrf_token" value="<?= e(app_csrf_token()) ?>">
            <button class="button" type="submit"><?= e(t('Terminar sessão')) ?></button>
            <a href="index.php"><?= e(t('Voltar ao site')) ?></a>
        </form>
    </main>
</body>
</html>
