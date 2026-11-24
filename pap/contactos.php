<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'idioma.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'gym.php';
start_app_session();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$settings = app_gym_settings(true);
?>
<!doctype html>
<html lang="<?= e(app_language_tag()) ?>">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="Icon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090909">
    <title><?= e(t('Contactos')) ?> — PowerLift Gym</title>
    <style>
        :root { color-scheme: dark; --bg: #090909; --panel: #111; --red: #ed1025; --text: #f5f5f5; --muted: #b9b9b9; --line: #292929; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 22px; background: radial-gradient(ellipse at 50% 0, rgba(237, 16, 37, .13), transparent 42%), var(--bg); color: var(--text); font: 16px/1.55 Arial, Helvetica, sans-serif; }
        main { width: min(100%, 560px); padding: clamp(24px, 6vw, 40px); border: 1px solid var(--line); border-radius: 16px; background: var(--panel); }
        .language-switch { display: flex; justify-content: flex-end; align-items: center; gap: 6px; margin-bottom: 22px; }
        .language-switch select, .language-switch button { min-height: 36px; border: 1px solid var(--line); border-radius: 7px; background: #090909; color: #fff; font: inherit; font-size: 13px; }
        .language-switch select { padding: 0 8px; }
        .language-switch button { padding: 0 10px; cursor: pointer; }
        .visually-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .eyebrow { margin: 0 0 8px; color: var(--red); font-size: 12px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 0 0 22px; font-size: 32px; }
        .contact { margin: 14px 0; padding: 14px; border: 1px solid var(--line); border-radius: 9px; background: #090909; }
        .contact strong { display: block; margin-bottom: 4px; }
        a { color: #fff; }
        .back { display: inline-block; margin-top: 20px; color: var(--muted); }
    </style>
</head>
<body>
<main>
    <?= language_selector() ?>
    <p class="eyebrow"><?= e($settings['gym_name']) ?></p>
    <h1><?= e(t('Contacta o ginásio')) ?></h1>
    <div class="contact">
        <strong><?= e(t('Email de contacto')) ?></strong>
        <a href="mailto:<?= e($settings['contact_email']) ?>"><?= e($settings['contact_email']) ?></a>
    </div>
    <div class="contact">
        <strong><?= e(t('Telefone')) ?></strong>
        <?php if ($settings['contact_phone'] !== ''): ?>
            <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $settings['contact_phone'])) ?>"><?= e($settings['contact_phone']) ?></a>
        <?php else: ?>
            <?= e(t('Por definir')) ?>
        <?php endif; ?>
    </div>
    <div class="contact">
        <strong><?= e(t('Morada')) ?></strong>
        <?= $settings['contact_address'] !== '' ? nl2br(e($settings['contact_address'])) : e(t('Por definir')) ?>
    </div>
    <a class="back" href="index.php"><?= e(t('← Voltar à página inicial')) ?></a>
</main>
</body>
</html>
