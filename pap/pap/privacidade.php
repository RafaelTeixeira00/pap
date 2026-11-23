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
    <title><?= e(t('Política de Privacidade')) ?> — PowerLift Gym</title>
    <style>
        :root { color-scheme: dark; --bg: #090909; --panel: #111; --red: #ed1025; --text: #f5f5f5; --muted: #b9b9b9; --line: #292929; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 22px; background: radial-gradient(ellipse at 50% 0, rgba(237, 16, 37, .13), transparent 42%), var(--bg); color: var(--text); font: 16px/1.65 Arial, Helvetica, sans-serif; }
        main { width: min(100%, 760px); padding: clamp(24px, 6vw, 42px); border: 1px solid var(--line); border-radius: 16px; background: var(--panel); }
        .language-switch { display: flex; justify-content: flex-end; align-items: center; gap: 6px; margin-bottom: 22px; }
        .language-switch select, .language-switch button { min-height: 36px; border: 1px solid var(--line); border-radius: 7px; background: #090909; color: #fff; font: inherit; font-size: 13px; }
        .language-switch select { padding: 0 8px; }
        .language-switch button { padding: 0 10px; cursor: pointer; }
        .visually-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .eyebrow { margin: 0 0 8px; color: var(--red); font-size: 12px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 0 0 22px; font-size: 32px; }
        h2 { margin: 22px 0 6px; font-size: 19px; }
        p { margin: 0 0 12px; color: var(--muted); }
        a { color: #fff; }
        .draft-note { margin-top: 24px; padding: 12px; border: 1px solid #6b5223; border-radius: 8px; background: #231d10; color: #f5d992; font-size: 14px; }
        .back { display: inline-block; margin-top: 20px; color: var(--muted); }
    </style>
</head>
<body>
<main>
    <?= language_selector() ?>
    <p class="eyebrow"><?= e($settings['gym_name']) ?></p>
    <h1><?= e(t('Política de Privacidade')) ?></h1>

    <h2><?= e(t('Responsável pelo tratamento')) ?></h2>
    <p><?= e($settings['gym_name']) ?> — <a href="mailto:<?= e($settings['contact_email']) ?>"><?= e($settings['contact_email']) ?></a></p>

    <h2><?= e(t('Dados recolhidos')) ?></h2>
    <p><?= e(t('Recolhemos nome, email e palavra-passe para contas, e nome, email, telefone, plano ou aula, data e hora nos pedidos de marcação.')) ?></p>

    <h2><?= e(t('Como usamos os dados')) ?></h2>
    <p><?= e(t('Os dados são usados para gerir a tua conta e responder a pedidos de marcação.')) ?></p>

    <h2><?= e(t('Armazenamento e acesso')) ?></h2>
    <p><?= e(t('Os dados são guardados na base de dados do ginásio e são acessíveis apenas a administradores autorizados.')) ?></p>
    <p><?= e(t('A palavra-passe é guardada protegida por hash e nunca em texto simples.')) ?></p>

    <h2><?= e(t('Os teus direitos')) ?></h2>
    <p><?= e(t('Para pedir acesso, correção ou remoção dos teus dados, contacta-nos por email.')) ?></p>

    <p class="draft-note"><?= e(t('Esta página é informativa e deve ser revista com os dados legais e prazos de conservação aplicáveis ao ginásio.')) ?></p>
    <a class="back" href="index.php"><?= e(t('← Voltar à página inicial')) ?></a>
</main>
</body>
</html>
