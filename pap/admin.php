<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'idioma.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'gym.php';
start_app_session();
$admin = require_admin();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$connection = app_database();
$pendingBookings = (int) $connection->query(
    "SELECT COUNT(*) FROM bookings WHERE status = 'pending'"
)->fetchColumn();
$activeClients = (int) $connection->query(
    'SELECT COUNT(*) FROM client_accounts WHERE is_active = 1 AND is_admin = 0'
)->fetchColumn();
$upcomingBookings = (int) $connection->query(
    "SELECT COUNT(*) FROM bookings WHERE booking_date >= CURRENT_DATE AND status <> 'cancelled'"
)->fetchColumn();
$settings = app_gym_settings();
?>
<!doctype html>
<html lang="<?= e(app_language_tag()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('Painel de administração')) ?> — PowerLift Gym</title>
    <style>
        :root { color-scheme: dark; --bg: #090909; --panel: #111; --red: #ed1025; --text: #f5f5f5; --muted: #b9b9b9; --line: #292929; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; padding: 28px 18px; background: var(--bg); color: var(--text); font: 16px/1.55 Arial, Helvetica, sans-serif; }
        main { width: min(100%, 980px); margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 28px; }
        h1, h2, p { margin-top: 0; }
        h1 { margin-bottom: 4px; font-size: clamp(26px, 5vw, 36px); }
        .muted, .eyebrow { color: var(--muted); }
        .eyebrow { margin-bottom: 4px; color: var(--red); font-size: 12px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; }
        .card, .panel { padding: 22px; border: 1px solid var(--line); border-radius: 12px; background: var(--panel); }
        .card strong { display: block; color: var(--red); font-size: 34px; }
        .card span { color: var(--muted); }
        .panel { margin-top: 22px; }
        .links { display: flex; flex-wrap: wrap; gap: 10px; }
        a, button { color: inherit; font: inherit; }
        .link, .button { display: inline-flex; min-height: 43px; align-items: center; padding: 0 15px; border: 1px solid var(--line); border-radius: 8px; background: #171717; text-decoration: none; }
        .link:hover { border-color: var(--red); }
        .button { border: 0; background: var(--red); cursor: pointer; }
        .button:hover { background: #ff263b; }
        @media (max-width: 580px) { header { align-items: flex-start; flex-direction: column; } }
    </style>
</head>
<body>
<main>
    <header>
        <div>
            <p class="eyebrow"><?= e(t('Administração')) ?></p>
            <h1><?= e(t('Olá, ')) ?><?= e($admin['name']) ?></h1>
            <p class="muted"><?= e($settings['gym_name']) ?></p>
        </div>
        <form method="post" action="logout.php">
            <input type="hidden" name="auth_csrf_token" value="<?= e(app_csrf_token()) ?>">
            <button class="button" type="submit"><?= e(t('Terminar sessão')) ?></button>
        </form>
    </header>
    <section class="grid" aria-label="<?= e(t('Resumo')) ?>">
        <article class="card"><strong><?= e($pendingBookings) ?></strong><span><?= e(t('Pedidos pendentes')) ?></span></article>
        <article class="card"><strong><?= e($upcomingBookings) ?></strong><span><?= e(t('Marcações futuras')) ?></span></article>
        <article class="card"><strong><?= e($activeClients) ?></strong><span><?= e(t('Clientes ativos')) ?></span></article>
    </section>
    <section class="panel">
        <h2><?= e(t('Gestão do ginásio')) ?></h2>
        <nav class="links" aria-label="<?= e(t('Gestão do ginásio')) ?>">
            <a class="link" href="admin-pedidos.php"><?= e(t('Gerir marcações')) ?></a>
            <a class="link" href="admin-clientes.php"><?= e(t('Gerir clientes')) ?></a>
            <a class="link" href="admin-config.php"><?= e(t('Contactos e preços')) ?></a>
            <a class="link" href="contactos.php"><?= e(t('Ver página de contactos')) ?></a>
            <a class="link" href="privacidade.php"><?= e(t('Ver política de privacidade')) ?></a>
        </nav>
    </section>
</main>
</body>
</html>
