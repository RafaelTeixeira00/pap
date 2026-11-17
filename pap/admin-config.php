<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'idioma.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'gym.php';
start_app_session();
require_admin();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$settings = app_gym_settings();
$notice = '';
$isError = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['auth_csrf_token'] ?? '';
    $values = [];
    foreach (['contact_email', 'contact_phone', 'contact_address', 'price_base', 'price_power', 'price_personal'] as $key) {
        $posted = $_POST[$key] ?? '';
        $values[$key] = is_string($posted) ? trim($posted) : '';
    }

    if (
        !is_string($token) ||
        !isset($_SESSION['auth_csrf_token']) ||
        !hash_equals($_SESSION['auth_csrf_token'], $token)
    ) {
        $notice = 'A sessão expirou. Atualiza a página e tenta novamente.';
        $isError = true;
    } elseif (
        strlen($values['contact_email']) > 254 ||
        !filter_var($values['contact_email'], FILTER_VALIDATE_EMAIL) ||
        strlen($values['contact_phone']) > 30 ||
        strlen($values['contact_address']) > 255 ||
        strlen($values['price_base']) > 50 ||
        strlen($values['price_power']) > 50 ||
        strlen($values['price_personal']) > 50
    ) {
        $notice = 'Verifica os contactos e preços e tenta novamente.';
        $isError = true;
    } else {
        try {
            save_gym_settings($values);
            $settings = array_merge($settings, $values);
            $notice = 'Configuração guardada.';
        } catch (PDOException $error) {
            error_log('Falha ao guardar a configuração do ginásio: ' . $error->getMessage());
            $notice = 'Não foi possível guardar a configuração.';
            $isError = true;
        }
    }
}
?>
<!doctype html>
<html lang="<?= e(app_language_tag()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('Contactos e preços')) ?> — PowerLift Gym</title>
    <style>
        :root { color-scheme: dark; --bg: #090909; --panel: #111; --red: #ed1025; --text: #f5f5f5; --muted: #b9b9b9; --line: #292929; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; padding: 26px 16px; background: var(--bg); color: var(--text); font: 15px/1.5 Arial, Helvetica, sans-serif; }
        main { width: min(100%, 700px); margin: 0 auto; padding: clamp(20px, 5vw, 36px); border: 1px solid var(--line); border-radius: 14px; background: var(--panel); }
        h1 { margin: 0 0 8px; }
        p { color: var(--muted); }
        .back { display: inline-block; margin-bottom: 20px; color: var(--muted); }
        .notice { margin: 15px 0; padding: 12px; border: 1px solid #276443; border-radius: 8px; background: #10251a; color: #b9f1ce; }
        .notice.error { border-color: #74313b; background: #2a1115; color: #ffc0c7; }
        .fields { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .field { display: grid; gap: 6px; }
        .field.full { grid-column: 1 / -1; }
        label { color: #ddd; font-weight: 700; }
        input, textarea { width: 100%; padding: 10px 12px; border: 1px solid #353535; border-radius: 8px; background: #090909; color: #fff; font: inherit; }
        input { min-height: 44px; }
        textarea { min-height: 90px; resize: vertical; }
        button { min-height: 46px; padding: 0 20px; border: 0; border-radius: 8px; background: var(--red); color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        button:hover { background: #ff263b; }
        @media (max-width: 580px) { .fields { grid-template-columns: 1fr; } .field.full { grid-column: auto; } }
    </style>
</head>
<body>
<main>
    <a class="back" href="admin.php">← <?= e(t('Painel de administração')) ?></a>
    <h1><?= e(t('Contactos e preços')) ?></h1>
    <p><?= e(t('Preenche os dados reais do ginásio. Os preços são texto livre, por exemplo 29,99 €/mês.')) ?></p>
    <?php if ($notice !== ''): ?>
        <p class="notice<?= $isError ? ' error' : '' ?>" role="<?= $isError ? 'alert' : 'status' ?>"><?= e(t($notice)) ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="auth_csrf_token" value="<?= e(app_csrf_token()) ?>">
        <div class="fields">
            <div class="field">
                <label for="contact_email"><?= e(t('Email de contacto')) ?></label>
                <input id="contact_email" name="contact_email" type="email" maxlength="254" required value="<?= e($settings['contact_email']) ?>">
            </div>
            <div class="field">
                <label for="contact_phone"><?= e(t('Telefone')) ?></label>
                <input id="contact_phone" name="contact_phone" type="tel" maxlength="30" value="<?= e($settings['contact_phone']) ?>">
            </div>
            <div class="field full">
                <label for="contact_address"><?= e(t('Morada')) ?></label>
                <textarea id="contact_address" name="contact_address" maxlength="255"><?= e($settings['contact_address']) ?></textarea>
            </div>
            <div class="field">
                <label for="price_base"><?= e(t('Preço Base')) ?></label>
                <input id="price_base" name="price_base" maxlength="50" value="<?= e($settings['price_base']) ?>">
            </div>
            <div class="field">
                <label for="price_power"><?= e(t('Preço Power')) ?></label>
                <input id="price_power" name="price_power" maxlength="50" value="<?= e($settings['price_power']) ?>">
            </div>
            <div class="field">
                <label for="price_personal"><?= e(t('Preço Personal Training')) ?></label>
                <input id="price_personal" name="price_personal" maxlength="50" value="<?= e($settings['price_personal']) ?>">
            </div>
            <div class="field full">
                <button type="submit"><?= e(t('Guardar')) ?></button>
            </div>
        </div>
    </form>
</main>
</body>
</html>
