<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'idioma.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'gym.php';
start_app_session();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$currentUser = app_current_client();
$accountLink = $currentUser === null
    ? 'SignIn.php'
    : (!empty($currentUser['is_admin']) ? 'admin.php' : 'area-cliente.php');
$accountName = is_array($currentUser) && isset($currentUser['name']) && is_string($currentUser['name'])
    ? $currentUser['name']
    : null;

$plans = [
    'base' => t('Base'),
    'power' => t('Power'),
    'personal' => t('Personal Training'),
];
$gymSettings = app_gym_settings(true);
$prices = [
    'base' => $gymSettings['price_base'] !== '' ? $gymSettings['price_base'] : t('A combinar'),
    'power' => $gymSettings['price_power'] !== '' ? $gymSettings['price_power'] : t('A combinar'),
    'personal' => $gymSettings['price_personal'] !== '' ? $gymSettings['price_personal'] : t('A combinar'),
];

$times = ['07:00', '08:00', '09:00', '12:00', '17:00', '18:00', '19:00', '20:00'];
$values = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'plan' => '',
    'date' => '',
    'time' => '',
];
$errors = [];
?>
<!doctype html>
<html lang="<?= e(app_language_tag()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090909">
    <link rel="icon" type="image/png" href="Icon.png">
    <title><?= e(t('PowerLift Gym — Supera os teus limites')) ?></title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #090909;
            --panel: #111;
            --red: #ed1025;
            --red-dark: #b90819;
            --text: #f5f5f5;
            --muted: #b9b9b9;
            --line: #292929;
            --max: 1240px;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 104px; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.55;
        }
        body::before, body::after {
            position: fixed;
            z-index: 19;
            top: 0;
            bottom: 0;
            width: clamp(24px, 7vw, 110px);
            content: "";
            pointer-events: none;
        }
        body::before {
            left: 0;
            background: linear-gradient(90deg, rgba(237,16,37,.18), rgba(237,16,37,.06) 42%, transparent);
        }
        body::after {
            right: 0;
            background: linear-gradient(270deg, rgba(237,16,37,.18), rgba(237,16,37,.06) 42%, transparent);
        }
        a { color: inherit; text-decoration: none; }
        button, input, select { font: inherit; }
        button, a { -webkit-tap-highlight-color: transparent; }
        .wrap { width: min(var(--max), calc(100% - 48px)); margin: 0 auto; }
        .site-header {
            position: sticky;
            z-index: 20;
            top: 0;
            background: rgba(8, 8, 8, .96);
            border-bottom: 1px solid #292929;
            backdrop-filter: blur(12px);
        }
        .nav {
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        .brand { display: inline-flex; align-items: center; white-space: nowrap; }
        .brand-logo { display: block; width: 90px; height: 86px; object-fit: contain; }
        .nav-links { display: flex; align-items: center; gap: clamp(16px, 3vw, 38px); }
        .nav-links a { color: #ddd; font-size: 14px; font-weight: 700; transition: color .2s; }
        .nav-links a:hover, .nav-links a[aria-current="page"] { color: var(--red); }
        .nav-login-mobile { display: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .nav-actions { display: flex; align-items: center; gap: 12px; }
        .nav-account { max-width: 190px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .language-switch { display: flex; align-items: center; gap: 5px; margin: 0; }
        .language-switch select, .language-switch button { min-height: 38px; border: 1px solid var(--line); border-radius: 7px; background: #111; color: #fff; font-size: 13px; }
        .language-switch select { max-width: 115px; padding: 0 7px; }
        .language-switch button { padding: 0 8px; cursor: pointer; }
        .visually-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .button {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 0 20px;
            border: 1px solid transparent;
            border-radius: 10px;
            background: var(--red);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            transition: transform .18s, background .18s, border-color .18s;
        }
        .button:hover { background: #ff263b; transform: translateY(-1px); }
        .button-icon {
            width: 36px;
            height: 36px;
            object-fit: contain;
            filter: drop-shadow(0 0 4px rgba(237,16,37,.5));
        }
        .button-outline { border-color: var(--red); background: transparent; color: #fff; }
        .button-outline:hover { background: var(--red); }
        .button-quiet { border-color: #e4e4e4; background: transparent; }
        .button-quiet:hover { border-color: var(--red); background: rgba(237, 16, 37, .1); }
        .menu-toggle { display: none; width: 44px; height: 44px; border: 1px solid var(--line); border-radius: 9px; background: transparent; color: #fff; cursor: pointer; }

        .hero {
            position: relative;
            min-height: 492px;
            overflow: hidden;
            isolation: isolate;
            display: flex;
            align-items: center;
            background:
                linear-gradient(90deg, #090909 0%, #090909 32%, rgba(9,9,9,.78) 54%, rgba(9,9,9,.2) 100%),
                url("PLG0.png") right center / auto 100% no-repeat,
                #090909;
        }
        .hero::before, .hero::after { position: absolute; z-index: -1; content: ""; pointer-events: none; }
        .hero::before {
            top: -50px;
            left: -85px;
            width: 220px;
            height: 340px;
            transform: skewX(-43deg);
            background: linear-gradient(90deg, transparent 0 20%, rgba(237,16,37,.62) 20% 39%, rgba(151,8,22,.55) 39% 61%, transparent 61%);
        }
        .hero::after {
            right: -78px;
            bottom: -180px;
            width: 210px;
            height: 330px;
            transform: skewX(-43deg);
            background: linear-gradient(90deg, transparent 0 24%, rgba(237,16,37,.64) 24% 43%, rgba(151,8,22,.55) 43% 62%, transparent 62%);
        }
        .hero-copy { position: relative; z-index: 1; width: min(680px, 64%); padding: 60px 0 68px; }
        .eyebrow { margin: 0 0 12px; color: var(--red); font-size: 13px; font-weight: 800; letter-spacing: .2em; text-transform: uppercase; }
        h1 { margin: 0; font-size: clamp(44px, 6.8vw, 86px); line-height: .98; letter-spacing: -.055em; font-style: italic; font-weight: 900; }
        h1 span { color: var(--red); }
        .hero h2 { margin: 16px 0 14px; font-size: clamp(22px, 3vw, 37px); line-height: 1.15; font-style: italic; font-weight: 900; text-transform: uppercase; }
        .hero h2 span { color: var(--red); }
        .hero-copy > p:not(.eyebrow) { max-width: 530px; margin: 0; color: #e0e0e0; font-size: clamp(17px, 2vw, 22px); }
        .hero-buttons { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 30px; }
        .hero-buttons .button { min-height: 54px; padding: 0 24px; }

        .features { padding: 24px 0 30px; border-bottom: 1px solid var(--line); background: #0c0c0c; }
        .feature-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; text-align: center; }
        .feature { padding: 0 10px; }
        .feature svg { width: 43px; height: 43px; margin-bottom: 4px; color: var(--red); }
        .feature h3 { margin: 0; font-size: 18px; }
        .feature p { margin: 2px 0 0; color: var(--muted); font-size: 15px; }

        section.content-section { padding: 84px 0; }
        .section-heading { max-width: 670px; margin: 0 auto 34px; text-align: center; }
        .section-heading h2 { margin: 0 0 9px; font-size: clamp(30px, 4vw, 44px); line-height: 1.1; }
        .section-heading p { margin: 0; color: var(--muted); }
        .red { color: var(--red); }
        .cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        .card {
            padding: 26px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: linear-gradient(145deg, #151515, #0d0d0d);
            box-shadow: 0 8px 30px rgba(237,16,37,.12), 9px 3px 28px -8px rgba(237,16,37,.28);
            transition: border-color .2s, box-shadow .2s;
        }
        .card:hover { border-color: rgba(237,16,37,.45); box-shadow: 0 12px 36px rgba(237,16,37,.18), 12px 5px 32px -8px rgba(237,16,37,.38); }
        .card-icon { width: 38px; height: 38px; color: var(--red); }
        .card h3 { margin: 12px 0 7px; font-size: 21px; }
        .card p { margin: 0; color: var(--muted); }
        .plans-section { background: #0d0d0d; }
        .plan-card { position: relative; display: flex; flex-direction: column; min-height: 280px; }
        .plan-card.featured { border-color: var(--red); box-shadow: 0 8px 30px rgba(237,16,37,.15), 9px 3px 28px -8px rgba(237,16,37,.34); }
        .plan-card.featured:hover { box-shadow: 0 12px 36px rgba(237,16,37,.2), 12px 5px 32px -8px rgba(237,16,37,.44); }
        .plan-tag { position: absolute; top: 17px; right: 17px; color: var(--red); font-size: 11px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .plan-title-toggle { display: flex; width: 100%; align-items: center; justify-content: space-between; padding: 0; border: 0; background: transparent; color: inherit; font: inherit; text-align: left; cursor: pointer; }
        .plan-toggle-indicator { margin-left: 12px; color: var(--red); }
        .plan-title-toggle:focus-visible { outline: 2px solid var(--red); outline-offset: 4px; }
        .plan-description { margin: 0 0 8px !important; color: var(--muted); font-size: 14px; }
        .plan-card ul { margin: 8px 0 22px; padding-left: 19px; color: var(--muted); }
        .plan-card li { margin: 5px 0; }
        .plan-card .button { align-self: flex-start; margin-top: auto; }
        .plan-card .button:hover, .plan-card .button:focus-visible {
            transform: translateY(-2px) scale(1.06);
            box-shadow: 0 7px 20px rgba(237,16,37,.28);
        }
        .price { margin: 8px 0; font-size: 30px; font-weight: 800; }
        .price small { color: var(--muted); font-size: 13px; font-weight: 400; }
        .schedule-layout { display: grid; grid-template-columns: .9fr 1.1fr; gap: 38px; align-items: start; }
        .schedule-copy h2 { margin: 0 0 12px; font-size: 36px; line-height: 1.15; }
        .schedule-copy p { color: var(--muted); }
        .hours { width: 100%; margin-top: 24px; border-collapse: collapse; }
        .hours th, .hours td { padding: 11px 8px; border-bottom: 1px solid var(--line); text-align: left; }
        .hours th { color: #fff; }
        .hours td { color: var(--muted); }
        .booking-form { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-card { padding: 26px; border: 1px solid var(--line); border-radius: 14px; background: #111; box-shadow: 0 8px 30px rgba(237,16,37,.14), 9px 3px 28px -8px rgba(237,16,37,.3); }
        .form-card:focus-within { box-shadow: 0 10px 34px rgba(237,16,37,.2), 11px 4px 32px -8px rgba(237,16,37,.4); }
        .form-card h3 { margin: 0 0 4px; font-size: 23px; }
        .form-card > p { margin: 0 0 19px; color: var(--muted); font-size: 14px; }
        .field { display: flex; flex-direction: column; gap: 6px; }
        .field.full { grid-column: 1 / -1; }
        .field label { color: #ddd; font-size: 13px; font-weight: 700; }
        .field input, .field select {
            width: 100%;
            min-height: 45px;
            padding: 9px 11px;
            border: 1px solid #353535;
            border-radius: 8px;
            outline: none;
            background: #090909;
            color: #fff;
        }
        .field input:focus, .field select:focus { border-color: var(--red); box-shadow: 0 0 0 3px rgba(237,16,37,.15); }
        .field-error { color: #ff7885; font-size: 12px; }
        .privacy-consent { display: flex; align-items: flex-start; gap: 8px; color: var(--muted) !important; font-weight: 400 !important; }
        .privacy-consent input { width: 18px; min-height: 18px; margin: 3px 0 0; accent-color: var(--red); }
        .privacy-consent a { color: #fff; text-decoration: underline; }
        .form-card .button { grid-column: 1 / -1; margin-top: 3px; }
        .form-note, .message { grid-column: 1 / -1; margin: 0; color: var(--muted); font-size: 12px; }
        .message { padding: 12px 14px; border: 1px solid #276443; border-radius: 8px; background: #10251a; color: #b9f1ce; font-size: 14px; }
        .message.error { border-color: #74313b; background: #2a1115; color: #ffc0c7; }
        .about-section { background: linear-gradient(180deg, #0d0d0d, #090909); }
        .about-panel { display: grid; grid-template-columns: 1fr 1fr; gap: 35px; align-items: center; padding: clamp(25px, 5vw, 54px); border: 1px solid var(--line); border-radius: 16px; background: #111; box-shadow: 0 8px 30px rgba(237,16,37,.12), 9px 3px 28px -8px rgba(237,16,37,.28); }
        .about-panel h2 { margin: 0 0 12px; font-size: 38px; line-height: 1.08; }
        .about-panel p { margin: 0 0 12px; color: var(--muted); }
        .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .stat { min-height: 120px; display: flex; flex-direction: column; justify-content: center; padding: 18px; border-radius: 12px; background: #191919; box-shadow: 0 6px 22px rgba(237,16,37,.12), 7px 3px 22px -7px rgba(237,16,37,.3); }
        .stat strong { color: var(--red); font-size: 30px; }
        .stat span { color: var(--muted); font-size: 13px; }
        footer { padding: 28px 0; border-top: 1px solid var(--line); color: var(--muted); font-size: 13px; }
        .footer-inner { display: flex; align-items: center; justify-content: space-between; gap: 18px; }
        .footer-inner p { margin: 0; }
        .footer-links { display: flex; flex-wrap: wrap; gap: 18px; }
        .footer-links a:hover { color: #fff; }

        @media (max-width: 900px) {
            .nav-links { gap: 16px; }
            .nav-actions .button { padding: 0 13px; }
            .hero-copy { width: 68%; }
            .schedule-layout { grid-template-columns: 1fr; gap: 28px; }
        }
        @media (max-width: 700px) {
            body::before, body::after { width: 28px; }
            body::before { background: linear-gradient(90deg, rgba(237,16,37,.16), rgba(237,16,37,.04) 42%, transparent); }
            body::after { background: linear-gradient(270deg, rgba(237,16,37,.16), rgba(237,16,37,.04) 42%, transparent); }
            .wrap { width: min(100% - 32px, var(--max)); }
            .nav { min-height: 90px; }
            .menu-toggle { display: inline-grid; place-items: center; }
            .nav-links {
                position: absolute;
                top: 90px;
                right: 0;
                left: 0;
                display: none;
                flex-direction: column;
                align-items: stretch;
                gap: 0;
                padding: 8px 16px 16px;
                border-bottom: 1px solid var(--line);
                background: #090909;
            }
            .nav-links.is-open { display: flex; }
            .nav-links a { padding: 12px; }
            .nav-login-mobile { display: block; padding: 12px; border: 0; background: transparent; color: #ddd; text-align: left; font-weight: 700; cursor: pointer; }
            .nav-login-mobile:hover { color: var(--red); }
            .nav-actions .button { min-height: 40px; padding: 0 12px; font-size: 13px; }
            .nav-actions .button-outline { display: none; }
            .brand-logo { width: 76px; height: 72px; }
            .hero { min-height: 560px; align-items: flex-start; background: linear-gradient(90deg, rgba(9,9,9,.96), rgba(9,9,9,.5)), url("PLG0.png") center bottom / 100% auto no-repeat, #090909; }
            .hero-copy { width: 100%; padding: 55px 0 240px; }
            .hero-buttons { gap: 10px; }
            .hero-buttons .button { min-height: 48px; padding: 0 15px; font-size: 14px; }
            .feature-grid { grid-template-columns: 1fr 1fr; row-gap: 22px; }
            .feature h3 { font-size: 15px; }
            .feature p { font-size: 13px; }
            section.content-section { padding: 64px 0; }
            .cards { grid-template-columns: 1fr; }
            .plan-card { min-height: 0; }
            .about-panel { grid-template-columns: 1fr; gap: 22px; }
            .about-panel h2 { font-size: 32px; }
            .booking-form { grid-template-columns: 1fr; }
            .field.full, .form-card .button, .form-note, .message { grid-column: auto; }
            .form-card { padding: 20px; }
            .footer-inner { flex-direction: column; align-items: flex-start; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
<header class="site-header">
    <div class="wrap nav">
        <a class="brand" href="#inicio" aria-label="<?= e(t('PowerLift Gym, início')) ?>">
            <?php include __DIR__ . DIRECTORY_SEPARATOR . 'Icon.php'; ?>
        </a>
        <button class="menu-toggle" type="button" aria-label="<?= e(t('Abrir menu')) ?>" aria-expanded="false" aria-controls="nav-links">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
        <nav class="nav-links" id="nav-links" aria-label="<?= e(t('Navegação principal')) ?>">
            <a href="#inicio" aria-current="page"><?= e(t('Início')) ?></a>
            <a href="#treinos"><?= e(t('Treinos')) ?></a>
            <a href="Aulas.php"><?= e(t('Aulas')) ?></a>
            <a href="#planos"><?= e(t('Planos')) ?></a>
            <a href="#horarios"><?= e(t('Horários')) ?></a>
            <a href="#sobre"><?= e(t('Sobre Nós')) ?></a>
            <a class="nav-login-mobile" href="<?= e($accountLink) ?>"><?= e($accountName ?? t('Login / Área de Cliente')) ?></a>
        </nav>
        <div class="nav-actions">
            <?= language_selector() ?>
            <a class="button button-outline nav-account" href="<?= e($accountLink) ?>"><?= e($accountName ?? t('Login')) ?></a>
            <a class="button" href="#marcar"><?= e(t('Marcar Horário')) ?></a>
        </div>
    </div>
</header>

<main>
    <section class="hero" id="inicio">
        <div class="wrap">
            <div class="hero-copy">
                <p class="eyebrow"><?= e(t('Treina com propósito')) ?></p>
                <h1>PowerLift <span>Gym</span></h1>
                <h2><?= e(t('Supera os teus ')) ?><span><?= e(t('limites')) ?></span></h2>
                <p><?= e(t('Treina, evolui e alcança os teus objetivos connosco. Um ginásio feito para ti!')) ?></p>
                <div class="hero-buttons">
                    <a class="button" href="#planos"><?= e(t('Conhece os Nossos Planos')) ?> <span aria-hidden="true">→</span></a>
                    <a class="button button-quiet" href="#marcar"><?= e(t('Marcar Horário')) ?> <img class="button-icon" src="Calendario-sem-fundo.png" alt="" width="36" height="36" aria-hidden="true"></a>
                </div>
            </div>
        </div>
    </section>

    <section class="features" aria-label="<?= e(t('Vantagens PowerLift')) ?>">
        <div class="wrap feature-grid">
            <article class="feature">
                <svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M4 19v10M10 12v24M16 7v34M21 19v10M27 19v10M32 7v34M38 12v24M44 19v10M16 21h16v6H16z" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <h3><?= e(t('Treinos Personalizados')) ?></h3><p><?= e(t('Planos adaptados aos teus objetivos.')) ?></p>
            </article>
            <article class="feature">
                <svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><rect x="7" y="10" width="34" height="33" rx="3" stroke="currentColor" stroke-width="3"/><path d="M15 5v10M33 5v10M7 19h34M15 26h5M27 26h5M15 34h5M27 34h5" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                <h3><?= e(t('Horários Flexíveis')) ?></h3><p><?= e(t('Treina quando quiseres.')) ?></p>
            </article>
            <article class="feature">
                <svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><circle cx="24" cy="14" r="7" stroke="currentColor" stroke-width="3"/><circle cx="10" cy="18" r="5" stroke="currentColor" stroke-width="3"/><circle cx="38" cy="18" r="5" stroke="currentColor" stroke-width="3"/><path d="M10 41v-5c0-7 6-12 14-12s14 5 14 12v5H10ZM2 39v-4c0-5 3-8 8-9M46 39v-4c0-5-3-8-8-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <h3><?= e(t('Acompanhamento Profissional')) ?></h3><p><?= e(t('Estamos contigo em cada passo.')) ?></p>
            </article>
            <article class="feature">
                <svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M14 7h20v7c0 10-4 16-10 16S14 24 14 14V7ZM14 12H5v4c0 7 5 11 13 12M34 12h9v4c0 7-5 11-13 12M24 30v8M17 42h14M19 38h10" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="m24 12 1.6 3.2 3.5.5-2.5 2.4.6 3.5-3.2-1.7-3.2 1.7.6-3.5-2.5-2.4 3.5-.5L24 12Z" fill="currentColor"/></svg>
                <h3><?= e(t('Resultados Reais')) ?></h3><p><?= e(t('Supera-te connosco.')) ?></p>
            </article>
        </div>
    </section>

    <section class="content-section" id="treinos">
        <div class="wrap">
            <div class="section-heading">
                <p class="eyebrow"><?= e(t('O teu treino, à tua maneira')) ?></p>
                <h2><?= e(t('Mais do que treinar.')) ?><br><span class="red"><?= e(t('Evoluir todos os dias.')) ?></span></h2>
                <p><?= e(t('Encontra o acompanhamento e o espaço certo para chegares mais longe.')) ?></p>
            </div>
            <div class="cards">
                <article class="card">
                    <svg class="card-icon" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M4 19v10M10 12v24M16 7v34M21 19v10M27 19v10M32 7v34M38 12v24M44 19v10M16 21h16v6H16z" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <h3><?= e(t('Musculação')) ?></h3><p><?= e(t('Equipamento completo para treinares força, técnica e confiança ao teu ritmo.')) ?></p>
                </article>
                <article class="card">
                    <svg class="card-icon" viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M24 5v8M24 35v8M5 24h8M35 24h8M10.6 10.6l5.7 5.7m15.4 15.4 5.7 5.7m0-26.8-5.7 5.7m-15.4 15.4-5.7 5.7" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><circle cx="24" cy="24" r="11" stroke="currentColor" stroke-width="3"/></svg>
                    <h3><?= e(t('Treino funcional')) ?></h3><p><?= e(t('Movimento, resistência e energia em sessões dinâmicas para todo o corpo.')) ?></p>
                </article>
                <article class="card">
                    <svg class="card-icon" viewBox="0 0 48 48" fill="none" aria-hidden="true"><circle cx="24" cy="14" r="7" stroke="currentColor" stroke-width="3"/><path d="M10 42v-5c0-8 6-13 14-13s14 5 14 13v5H10Z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/><path d="m18 34 4 4 8-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <h3><?= e(t('Acompanhamento')) ?></h3><p><?= e(t('Orientação próxima para definires metas realistas e manteres a motivação.')) ?></p>
                </article>
            </div>
        </div>
    </section>

    <section class="content-section plans-section" id="planos">
        <div class="wrap">
            <div class="section-heading">
                <p class="eyebrow"><?= e(t('Começa hoje')) ?></p>
                <h2><?= e(t('Um plano para cada ')) ?><span class="red"><?= e(t('objetivo.')) ?></span></h2>
                <p><?= e(t('Escolhe a opção que combina contigo. Os valores podem ser definidos pelo teu ginásio.')) ?></p>
                <p><?= e(t('Clica no nome de cada plano para veres uma breve descrição de como funciona.')) ?></p>
            </div>
            <div class="cards">
                <article class="card plan-card">
                    <h3><button class="plan-title-toggle" type="button" aria-expanded="false" aria-controls="plan-description-base"><?= e(t('Base')) ?><span class="plan-toggle-indicator" aria-hidden="true">+</span></button></h3>
                    <p class="plan-description" id="plan-description-base" hidden><?= e(t('O plano Base dá-te acesso ao ginásio e ao equipamento para treinares por conta própria, ao teu ritmo e durante o horário de funcionamento.')) ?></p>
                    <p class="price"><?= e($prices['base']) ?></p>
                    <ul><li><?= e(t('Acesso ao ginásio')) ?></li><li><?= e(t('Treina ao teu ritmo')) ?></li><li><?= e(t('Equipamento completo')) ?></li></ul>
                    <a class="button button-outline" href="#marcar" data-plan="base"><?= e(t('Escolher Base')) ?></a>
                </article>
                <article class="card plan-card featured">
                    <span class="plan-tag"><?= e(t('Mais escolhido')) ?></span>
                    <h3><button class="plan-title-toggle" type="button" aria-expanded="false" aria-controls="plan-description-power"><?= e(t('Power')) ?><span class="plan-toggle-indicator" aria-hidden="true">+</span></button></h3>
                    <p class="plan-description" id="plan-description-power" hidden><?= e(t('O plano Power inclui tudo do Base, mais uma avaliação inicial e acompanhamentos periódicos para rever a tua evolução e ajustar os teus objetivos.')) ?></p>
                    <p class="price"><?= e($prices['power']) ?></p>
                    <ul><li><?= e(t('Tudo do plano Base')) ?></li><li><?= e(t('Avaliação inicial')) ?></li><li><?= e(t('Acompanhamento periódico')) ?></li></ul>
                    <a class="button" href="#marcar" data-plan="power"><?= e(t('Escolher Power')) ?></a>
                </article>
                <article class="card plan-card">
                    <h3><button class="plan-title-toggle" type="button" aria-expanded="false" aria-controls="plan-description-personal"><?= e(t('Personal Training')) ?><span class="plan-toggle-indicator" aria-hidden="true">+</span></button></h3>
                    <p class="plan-description" id="plan-description-personal" hidden><?= e(t('No Personal Training, treinas em sessões individuais com um profissional, com um plano adaptado aos teus objetivos e acompanhamento próximo.')) ?></p>
                    <p class="price"><?= e($prices['personal']) ?></p>
                    <ul><li><?= e(t('Sessões individuais')) ?></li><li><?= e(t('Plano adaptado a ti')) ?></li><li><?= e(t('Acompanhamento próximo')) ?></li></ul>
                    <a class="button button-outline" href="#marcar" data-plan="personal"><?= e(t('Escolher Personal')) ?></a>
                </article>
            </div>
        </div>
    </section>

    <section class="content-section" id="horarios">
        <div class="wrap schedule-layout">
            <div class="schedule-copy">
                <p class="eyebrow"><?= e(t('Estamos à tua espera')) ?></p>
                <h2><?= e(t('O teu próximo treino começa ')) ?><span class="red"><?= e(t('aqui.')) ?></span></h2>
                <p><?= e(t('Escolhe um horário e envia o teu pedido. A equipa confirma contigo a disponibilidade.')) ?></p>
                <table class="hours">
                    <thead><tr><th><?= e(t('Dia')) ?></th><th><?= e(t('Horário')) ?></th></tr></thead>
                    <tbody>
                        <tr><td><?= e(t('Segunda a sexta')) ?></td><td>07:00 — 22:00</td></tr>
                        <tr><td><?= e(t('Sábado')) ?></td><td>09:00 — 18:00</td></tr>
                        <tr><td><?= e(t('Domingo')) ?></td><td>09:00 — 13:00</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="form-card" id="marcar">
                <h3><?= e(t('Marca o teu horário')) ?></h3>
                <p><?= e(t('Preenche os dados para pedires uma sessão experimental.')) ?></p>
                <form class="booking-form" method="post" action="horario.php">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <div class="field">
                        <label for="name"><?= e(t('Nome')) ?></label>
                        <input id="name" name="name" autocomplete="name" required minlength="2" value="<?= e($values['name']) ?>" <?= isset($errors['name']) ? 'aria-describedby="name-error"' : '' ?>>
                        <?php if (isset($errors['name'])): ?><span class="field-error" id="name-error"><?= e($errors['name']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="email"><?= e(t('Email')) ?></label>
                        <input id="email" name="email" type="email" autocomplete="email" required value="<?= e($values['email']) ?>" <?= isset($errors['email']) ? 'aria-describedby="email-error"' : '' ?>>
                        <?php if (isset($errors['email'])): ?><span class="field-error" id="email-error"><?= e($errors['email']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="phone"><?= e(t('Telefone')) ?></label>
                        <input id="phone" name="phone" type="tel" autocomplete="tel" required value="<?= e($values['phone']) ?>" <?= isset($errors['phone']) ? 'aria-describedby="phone-error"' : '' ?>>
                        <?php if (isset($errors['phone'])): ?><span class="field-error" id="phone-error"><?= e($errors['phone']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="plan"><?= e(t('Plano')) ?></label>
                        <select id="plan" name="plan" required <?= isset($errors['plan']) ? 'aria-describedby="plan-error"' : '' ?>>
                            <option value=""><?= e(t('Seleciona um plano')) ?></option>
                            <?php foreach ($plans as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $values['plan'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['plan'])): ?><span class="field-error" id="plan-error"><?= e($errors['plan']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="date"><?= e(t('Data')) ?></label>
                        <input id="date" name="date" type="date" min="<?= e((new DateTimeImmutable('today'))->format('Y-m-d')) ?>" required value="<?= e($values['date']) ?>" <?= isset($errors['date']) ? 'aria-describedby="date-error"' : '' ?>>
                        <?php if (isset($errors['date'])): ?><span class="field-error" id="date-error"><?= e($errors['date']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="time"><?= e(t('Hora')) ?></label>
                        <select id="time" name="time" required <?= isset($errors['time']) ? 'aria-describedby="time-error"' : '' ?>>
                            <option value=""><?= e(t('Seleciona uma hora')) ?></option>
                            <?php foreach ($times as $time): ?>
                                <option value="<?= e($time) ?>" <?= $values['time'] === $time ? 'selected' : '' ?>><?= e($time) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['time'])): ?><span class="field-error" id="time-error"><?= e($errors['time']) ?></span><?php endif; ?>
                    </div>
                    <div class="field full">
                        <label class="privacy-consent" for="privacy_consent">
                            <input id="privacy_consent" name="privacy_consent" type="checkbox" value="1" required>
                            <span><?= e(t('Li e aceito a')) ?> <a href="privacidade.php" target="_blank" rel="noopener"><?= e(t('Política de Privacidade')) ?></a>.</span>
                        </label>
                    </div>
                    <button class="button" type="submit"><?= e(t('Pedir marcação')) ?> <span aria-hidden="true">→</span></button>
                    <p class="form-note"><?= e(t('O envio confirma apenas a receção do pedido; a disponibilidade será confirmada pela equipa.')) ?></p>
                </form>
            </div>
        </div>
    </section>

    <section class="content-section about-section" id="sobre">
        <div class="wrap about-panel">
            <div>
                <p class="eyebrow"><?= e(t('Sobre nós')) ?></p>
                <h2><?= e(t('Treina forte.')) ?><br><span class="red"><?= e(t('Sente-te em casa.')) ?></span></h2>
                <p><?= e(t('Na PowerLift Gym, acreditamos que cada pessoa tem o seu próprio caminho. Aqui encontras espaço, equipamento e apoio para construíres hábitos mais fortes, um treino de cada vez.')) ?></p>
                <a class="button button-outline" href="#marcar"><?= e(t('Vem conhecer o ginásio')) ?></a>
            </div>
            <div class="stat-grid" aria-label="<?= e(t('O que podes esperar')) ?>">
                <div class="stat"><strong>100%</strong><span><?= e(t('Foco nos teus objetivos')) ?></span></div>
                <div class="stat"><strong>+1</strong><span><?= e(t('Passo mais perto da tua meta')) ?></span></div>
                <div class="stat"><strong><?= e(t('Tu')) ?></strong><span><?= e(t('No centro de cada treino')) ?></span></div>
                <div class="stat"><strong><?= e(t('Juntos')) ?></strong><span><?= e(t('Em cada passo da tua jornada')) ?></span></div>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="wrap footer-inner">
        <p>© <?= e(date('Y')) ?> PowerLift Gym. <?= e(t('Todos os direitos reservados.')) ?></p>
        <div class="footer-links"><a href="#inicio"><?= e(t('Início')) ?></a><a href="Aulas.php"><?= e(t('Aulas')) ?></a><a href="#planos"><?= e(t('Planos')) ?></a><a href="#marcar"><?= e(t('Marcar horário')) ?></a><a href="FAQ.php"><?= e(t('FAQ')) ?></a><a href="contactos.php"><?= e(t('Contactos')) ?></a><a href="privacidade.php"><?= e(t('Privacidade')) ?></a></div>
    </div>
</footer>

<script>
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    menuToggle.addEventListener('click', () => {
        const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
        menuToggle.setAttribute('aria-expanded', String(!isOpen));
        menuToggle.setAttribute('aria-label', isOpen ? <?= json_encode(t('Abrir menu')) ?> : <?= json_encode(t('Fechar menu')) ?>);
        navLinks.classList.toggle('is-open', !isOpen);
    });

    navLinks.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            navLinks.classList.remove('is-open');
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.setAttribute('aria-label', <?= json_encode(t('Abrir menu')) ?>);
        });
    });

    document.querySelectorAll('.plan-title-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const expanded = button.getAttribute('aria-expanded') === 'true';
            const description = document.getElementById(button.getAttribute('aria-controls'));
            button.setAttribute('aria-expanded', String(!expanded));
            button.querySelector('.plan-toggle-indicator').textContent = expanded ? '+' : '-';
            description.hidden = expanded;
        });
    });

    const planSelect = document.querySelector('#plan');
    document.querySelectorAll('[data-plan]').forEach((link) => {
        link.addEventListener('click', () => {
            planSelect.value = link.dataset.plan;
        });
    });

</script>
</body>
</html>