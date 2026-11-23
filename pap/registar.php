<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'idioma.php';
start_app_session();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$registerError = '';
$registerStatus = 200;
$name = '';
$email = '';

if (app_current_client() !== null) {
    $client = app_current_client();
    header('Location: ' . (!empty($client['is_admin']) ? 'admin.php' : 'area-cliente.php'));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $nameInput = $_POST['name'] ?? '';
    $emailInput = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';
    $token = $_POST['auth_csrf_token'] ?? '';
    $name = is_string($nameInput) ? trim($nameInput) : '';
    $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';

    if (
        !is_string($token) ||
        !isset($_SESSION['auth_csrf_token']) ||
        !is_string($_SESSION['auth_csrf_token']) ||
        !hash_equals($_SESSION['auth_csrf_token'], $token)
    ) {
        $registerError = 'A sessão expirou. Atualiza a página e tenta novamente.';
        $registerStatus = 400;
    } elseif (
        !is_string($nameInput) ||
        strlen($name) < 2 ||
        strlen($name) > 120 ||
        !is_string($emailInput) ||
        strlen($email) > 254 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !is_string($password) ||
        strlen($password) < 12 ||
        strlen($password) > 72 ||
        !is_string($passwordConfirmation)
    ) {
        $registerError = 'Verifica os dados do formulário e tenta novamente.';
        $registerStatus = 400;
    } elseif (!hash_equals($password, $passwordConfirmation)) {
        $registerError = 'As palavras-passe não coincidem.';
        $registerStatus = 400;
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            error_log('Falha ao proteger a palavra-passe da nova conta de cliente.');
            $registerError = 'Não foi possível criar a conta. Tenta novamente mais tarde.';
            $registerStatus = 500;
        } else {
            try {
                $connection = app_database();
                $statement = $connection->prepare(
                    'INSERT INTO client_accounts (name, email, password_hash, is_active, is_admin) '
                    . 'VALUES (:name, :email, :password_hash, 1, 0)'
                );
                $statement->execute([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => $passwordHash,
                ]);

                session_regenerate_id(true);
                $_SESSION['client'] = [
                    'id' => (int) $connection->lastInsertId(),
                    'name' => $name,
                    'email' => $email,
                    'is_admin' => false,
                ];
                unset($_SESSION['auth_csrf_token']);
                header('Location: area-cliente.php');
                exit;
            } catch (PDOException $error) {
                if ($error->getCode() === '23000') {
                    $registerError = 'Esse email já está associado a outra conta.';
                    $registerStatus = 409;
                } else {
                    error_log('Falha ao registar conta de cliente: ' . $error->getMessage());
                    $registerError = 'Não foi possível criar a conta. Tenta novamente mais tarde.';
                    $registerStatus = 500;
                }
            } catch (RuntimeException $error) {
                error_log('Falha na configuração ao registar conta: ' . $error->getMessage());
                $registerError = 'A autenticação ainda não está configurada. Contacta a receção.';
                $registerStatus = 503;
            }
        }
    }
}

if ($registerStatus !== 200) {
    http_response_code($registerStatus);
}
?>
<!doctype html>
<html lang="<?= e(app_language_tag()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090909">
    <link rel="icon" type="image/png" href="Icon.png">
    <title><?= e(t('Criar conta de cliente')) ?> — PowerLift Gym</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #090909;
            --panel: #111;
            --red: #ed1025;
            --text: #f5f5f5;
            --muted: #b9b9b9;
            --line: #292929;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                radial-gradient(ellipse at 50% 0, rgba(237, 16, 37, .13), transparent 42%),
                var(--bg);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.55;
        }
        a { color: inherit; text-decoration: none; }
        .register-shell { width: min(100%, 440px); }
        .brand { display: flex; align-items: center; justify-content: center; margin-bottom: 26px; }
        .brand-logo { display: block; width: 200px; height: 190px; object-fit: contain; }
        .panel {
            padding: clamp(24px, 7vw, 38px);
            border: 1px solid var(--line);
            border-radius: 16px;
            background: linear-gradient(145deg, #151515, var(--panel));
            box-shadow: 0 25px 80px rgba(0, 0, 0, .35);
        }
        .eyebrow { margin: 0 0 7px; color: var(--red); font-size: 12px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 0; font-size: 32px; line-height: 1.15; }
        .intro { margin: 9px 0 23px; color: var(--muted); }
        .field { display: grid; gap: 6px; margin-bottom: 16px; }
        label { color: #ddd; font-size: 13px; font-weight: 700; }
        input {
            width: 100%;
            min-height: 47px;
            padding: 10px 12px;
            border: 1px solid #353535;
            border-radius: 8px;
            outline: none;
            background: #090909;
            color: #fff;
            font: inherit;
        }
        input:focus { border-color: var(--red); box-shadow: 0 0 0 3px rgba(237, 16, 37, .15); }
        .hint { margin: -8px 0 16px; color: var(--muted); font-size: 13px; }
        .notice { margin: 18px 0 0; padding: 12px 14px; border: 1px solid #74313b; border-radius: 8px; background: #2a1115; color: #ffc0c7; font-size: 14px; }
        .button {
            width: 100%;
            min-height: 48px;
            border: 0;
            border-radius: 9px;
            background: var(--red);
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
        .button:hover { background: #ff263b; }
        .back-link { display: block; margin-top: 21px; color: var(--muted); font-size: 14px; text-align: center; }
        .back-link:hover { color: #fff; }
        .language-switch { display: flex; justify-content: center; align-items: center; gap: 6px; margin: -12px 0 20px; }
        .language-switch select, .language-switch button { min-height: 36px; border: 1px solid var(--line); border-radius: 7px; background: #111; color: #fff; font: inherit; font-size: 13px; }
        .language-switch select { padding: 0 8px; }
        .language-switch button { padding: 0 10px; cursor: pointer; }
        @media (max-width: 480px) {
            body { padding: 18px; }
            h1 { font-size: 28px; }
            .brand-logo { width: 170px; height: 160px; }
        }
    </style>
</head>
<body>
    <main class="register-shell">
        <a class="brand" href="index.php" aria-label="<?= e(t('PowerLift Gym, início')) ?>">
            <?php include __DIR__ . DIRECTORY_SEPARATOR . 'Icon.php'; ?>
        </a>
        <?= language_selector() ?>
        <section class="panel" aria-labelledby="register-title">
            <p class="eyebrow"><?= e(t('Área de cliente')) ?></p>
            <h1 id="register-title"><?= e(t('Criar conta de cliente')) ?></h1>
            <p class="intro"><?= e(t('Regista uma conta de cliente para acederes à área de cliente.')) ?></p>
            <form method="post" action="registar.php">
                <input type="hidden" name="auth_csrf_token" value="<?= e(app_csrf_token()) ?>">
                <div class="field">
                    <label for="name"><?= e(t('Nome')) ?></label>
                    <input id="name" name="name" type="text" autocomplete="name" required minlength="2" maxlength="120" value="<?= e($name) ?>">
                </div>
                <div class="field">
                    <label for="email"><?= e(t('Email')) ?></label>
                    <input id="email" name="email" type="email" autocomplete="email" required maxlength="254" value="<?= e($email) ?>">
                </div>
                <div class="field">
                    <label for="password"><?= e(t('Palavra-passe')) ?></label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required minlength="12" maxlength="72">
                </div>
                <p class="hint"><?= e(t('A palavra-passe deve ter pelo menos 12 caracteres.')) ?></p>
                <div class="field">
                    <label for="password_confirmation"><?= e(t('Confirmar palavra-passe')) ?></label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="12" maxlength="72">
                </div>
                <button class="button" type="submit"><?= e(t('Criar conta')) ?></button>
            </form>
            <?php if ($registerError !== ''): ?>
                <p class="notice" role="alert"><?= e(t($registerError)) ?></p>
            <?php endif; ?>
        </section>
        <a class="back-link" href="SignIn.php"><?= e(t('Já tens conta?')) ?> <?= e(t('Inicia sessão')) ?></a>
    </main>
</body>
</html>
