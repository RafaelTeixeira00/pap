<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'idioma.php';
start_app_session();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$loginError = '';
$loginStatus = 200;
$email = '';

if (app_current_client() !== null && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: area-cliente.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $emailValue = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $token = $_POST['auth_csrf_token'] ?? '';
    $email = is_string($emailValue) ? strtolower(trim($emailValue)) : '';

    if (
        !is_string($token) ||
        !isset($_SESSION['auth_csrf_token']) ||
        !hash_equals($_SESSION['auth_csrf_token'], $token)
    ) {
        $loginError = 'A sessão expirou. Atualiza a página e tenta novamente.';
        $loginStatus = 400;
    } elseif (!is_string($password) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loginError = 'Email ou palavra-passe incorretos.';
        $loginStatus = 401;
    } else {
        try {
            $statement = app_database()->prepare(
                'SELECT id, name, email, password_hash, is_admin FROM client_accounts WHERE email = :email AND is_active = 1 LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $client = $statement->fetch();

            if ($client && password_verify($password, $client['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['client'] = [
                    'id' => (int) $client['id'],
                    'name' => $client['name'],
                    'email' => $client['email'],
                    'is_admin' => (int) $client['is_admin'] === 1,
                ];
                unset($_SESSION['auth_csrf_token']);
                header('Location: ' . ($_SESSION['client']['is_admin'] ? 'admin.php' : 'area-cliente.php'));
                exit;
            }

            $loginError = 'Email ou palavra-passe incorretos.';
            $loginStatus = 401;
        } catch (PDOException $error) {
            error_log('Falha na autenticação do cliente: ' . $error->getMessage());
            $loginError = 'Não foi possível ligar à área de cliente. Tenta novamente mais tarde.';
            $loginStatus = 503;
        } catch (RuntimeException $error) {
            error_log('Falha de configuração da autenticação: ' . $error->getMessage());
            $loginError = 'A autenticação ainda não está configurada. Contacta a receção.';
            $loginStatus = 503;
        }
    }
}

if ($loginError !== '') {
    http_response_code($loginStatus);
}
?>
<!doctype html>
<html lang="<?= e(app_language_tag()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090909">
    <title><?= e(t('Login')) ?> — PowerLift Gym</title>
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
        .login-shell { width: min(100%, 440px); }
        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 26px;
            font-size: 23px;
            font-weight: 800;
        }
        .brand-mark { width: 43px; height: 34px; color: var(--red); }
        .brand-name span { color: var(--red); }
        .panel {
            padding: clamp(24px, 7vw, 38px);
            border: 1px solid var(--line);
            border-radius: 16px;
            background: linear-gradient(145deg, #151515, var(--panel));
            box-shadow: 0 25px 80px rgba(0, 0, 0, .35);
        }
        .eyebrow {
            margin: 0 0 7px;
            color: var(--red);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }
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
        .notice {
            margin: 18px 0 0;
            padding: 12px 14px;
            border: 1px solid #74313b;
            border-radius: 8px;
            background: #2a1115;
            color: #ffc0c7;
            font-size: 14px;
        }
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
        .back-link {
            display: block;
            margin-top: 21px;
            color: var(--muted);
            font-size: 14px;
            text-align: center;
        }
        .back-link:hover { color: #fff; }
        .language-switch { display: flex; justify-content: center; align-items: center; gap: 6px; margin: -12px 0 20px; }
        .language-switch select, .language-switch button { min-height: 36px; border: 1px solid var(--line); border-radius: 7px; background: #111; color: #fff; font: inherit; font-size: 13px; }
        .language-switch select { padding: 0 8px; }
        .language-switch button { padding: 0 10px; cursor: pointer; }
        .visually-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        @media (max-width: 480px) {
            body { padding: 18px; }
            h1 { font-size: 28px; }
        }
    </style>
</head>
<body>
    <main class="login-shell">
        <a class="brand" href="index.php" aria-label="<?= e(t('PowerLift Gym, início')) ?>">
            <svg class="brand-mark" viewBox="0 0 64 44" fill="none" aria-hidden="true">
                <path d="M4 17v10M11 9v26M18 4v36M24 17v10M40 17v10M46 4v36M53 9v26M60 17v10M18 19h28v6H18z" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="brand-name">PowerLift <span>Gym</span></span>
        </a>
        <?= language_selector() ?>
        <section class="panel" aria-labelledby="login-title">
            <p class="eyebrow"><?= e(t('Área de cliente')) ?></p>
            <h1 id="login-title"><?= e(t('Inicia sessão')) ?></h1>
            <p class="intro"><?= e(t('Entra na tua conta PowerLift.')) ?></p>
            <form method="post" action="login.php">
                <input type="hidden" name="auth_csrf_token" value="<?= e(app_csrf_token()) ?>">
                <div class="field">
                    <label for="email"><?= e(t('Email')) ?></label>
                    <input id="email" name="email" type="email" autocomplete="email" required value="<?= e($email) ?>">
                </div>
                <div class="field">
                    <label for="password"><?= e(t('Palavra-passe')) ?></label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button class="button" type="submit"><?= e(t('Entrar')) ?></button>
            </form>
            <?php if ($loginError !== ''): ?>
                <p class="notice" role="alert"><?= e(t($loginError)) ?></p>
            <?php else: ?>
                <p class="notice" role="status"><?= e(t('A receção fornece as credenciais de acesso. Se ainda não consegues entrar, contacta a receção.')) ?></p>
            <?php endif; ?>
        </section>
        <a class="back-link" href="index.php"><?= e(t('← Voltar à página inicial')) ?></a>
    </main>
</body>
</html>
