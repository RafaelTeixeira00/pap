<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'idioma.php';
start_app_session();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$faqs = [
    [
        'question' => t('Como posso pedir uma sessão experimental?'),
        'answer' => t('Preenche o formulário de marcação na página inicial com os teus dados e horário pretendido. A equipa confirma a disponibilidade.'),
    ],
    [
        'question' => t('O pedido de marcação fica confirmado imediatamente?'),
        'answer' => t('O envio confirma apenas a receção do pedido; a disponibilidade será confirmada pela equipa.'),
    ],
    [
        'question' => t('Qual é o horário do ginásio?'),
        'answer' => t('Segunda a sexta: 07:00 - 22:00. Sábado: 09:00 - 18:00. Domingo: 09:00 - 13:00.'),
    ],
    [
        'question' => t('Que planos estão disponíveis?'),
        'answer' => t('Estão disponíveis os planos Base, Power e Personal Training. Consulta a secção Planos para veres o que inclui cada um.'),
    ],
    [
        'question' => t('Onde posso consultar os preços?'),
        'answer' => t('Os preços atuais aparecem na secção Planos. Se um preço não estiver indicado, contacta a receção para mais informação.'),
    ],
    [
        'question' => t('Preciso de uma conta para pedir uma marcação?'),
        'answer' => t('Não. Podes enviar um pedido através do formulário da página inicial sem iniciar sessão. Para consultar ou cancelar marcações na área de cliente, estas têm de estar associadas à tua conta.'),
    ],
    [
        'question' => t('Como posso cancelar uma marcação?'),
        'answer' => t('Inicia sessão, abre a área de cliente e seleciona Cancelar marcação numa marcação futura. As marcações passadas não podem ser canceladas.'),
    ],
    [
        'question' => t('Como crio uma conta de cliente?'),
        'answer' => t('Acede a SignIn.php e escolhe Criar conta. Depois do registo, a sessão é iniciada automaticamente.'),
    ],
    [
        'question' => t('Como posso contactar o ginásio?'),
        'answer' => t('Consulta a página Contactos para veres o email, telefone e morada disponíveis.'),
    ],
];
?>
<!doctype html>
<html lang="<?= e(app_language_tag()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090909">
    <link rel="icon" type="image/png" href="Icon.png">
    <title><?= e(t('FAQ')) ?> — PowerLift Gym</title>
    <style>
        :root { color-scheme: dark; --bg: #090909; --panel: #111; --red: #ed1025; --text: #f5f5f5; --muted: #b9b9b9; --line: #292929; }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 22px;
            background:
                radial-gradient(ellipse at 8% 12%, rgba(237, 16, 37, .24), transparent 36%),
                radial-gradient(ellipse at 92% 88%, rgba(237, 16, 37, .16), transparent 40%),
                var(--bg);
            color: var(--text);
            font: 16px/1.6 Arial, Helvetica, sans-serif;
        }
        main { width: min(100%, 820px); padding: clamp(22px, 5vw, 40px); border: 1px solid #55202a; border-radius: 16px; background: linear-gradient(145deg, #191214, #111); box-shadow: 0 24px 70px rgba(0, 0, 0, .45); }
        .page-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
        .brand-logo { display: block; width: 110px; height: 104px; object-fit: contain; }
        .language-switch { display: flex; justify-content: flex-end; align-items: center; gap: 6px; margin: 0; }
        .language-switch select, .language-switch button { min-height: 36px; border: 1px solid #54202a; border-radius: 7px; background: #120d0e; color: #fff; font: inherit; font-size: 13px; }
        .language-switch select { padding: 0 8px; }
        .language-switch button { padding: 0 10px; cursor: pointer; }
        .eyebrow { margin: 0 0 8px; color: var(--red); font-size: 12px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 0; color: #fff; font-size: clamp(28px, 5vw, 36px); }
        .intro { margin: 8px 0 24px; color: #d7c5c8; }
        .faq-item { padding: 18px; border: 1px solid #4b2027; border-left: 4px solid var(--red); border-radius: 10px; background: linear-gradient(115deg, #1a1113, #0d0b0c); }
        .faq-item + .faq-item { margin-top: 12px; }
        .faq-item:hover { border-color: #9c2534; }
        .faq-item h2 { margin: 0 0 7px; color: #ff5362; font-size: 18px; }
        .faq-item p { margin: 0; color: #d0c4c6; }
        .page-links { display: flex; flex-wrap: wrap; gap: 16px; margin-top: 24px; }
        .page-links a { padding: 9px 12px; border: 1px solid #6b2530; border-radius: 8px; background: #1b1012; color: #ffe8eb; text-decoration: none; }
        .page-links a:first-child { border-color: var(--red); background: var(--red); color: #fff; }
        .page-links a:hover { border-color: #ff5362; background: #92202d; color: #fff; }
        @media (max-width: 480px) {
            body { padding: 14px; }
            .page-header { align-items: flex-start; }
            .brand-logo { width: 88px; height: 84px; }
            .faq-item { padding: 15px; }
        }
    </style>
</head>
<body>
<main>
    <header class="page-header">
        <a href="index.php" aria-label="<?= e(t('PowerLift Gym, início')) ?>">
            <?php include __DIR__ . DIRECTORY_SEPARATOR . 'Icon.php'; ?>
        </a>
        <?= language_selector() ?>
    </header>
    <p class="eyebrow">PowerLift Gym</p>
    <h1><?= e(t('FAQ')) ?></h1>
    <p class="intro"><?= e(t('Encontra respostas às dúvidas mais comuns sobre planos, marcações e contas.')) ?></p>
    <section aria-label="<?= e(t('FAQ')) ?>">
        <?php foreach ($faqs as $faq): ?>
            <article class="faq-item">
                <h2><?= e($faq['question']) ?></h2>
                <p><?= e($faq['answer']) ?></p>
            </article>
        <?php endforeach; ?>
    </section>
    <nav class="page-links" aria-label="<?= e(t('Navegação principal')) ?>">
        <a href="index.php#marcar"><?= e(t('Pedir marcação')) ?></a>
        <a href="SignIn.php"><?= e(t('Inicia sessão')) ?></a>
        <a href="contactos.php"><?= e(t('Contactos')) ?></a>
        <a href="index.php"><?= e(t('← Voltar à página inicial')) ?></a>
    </nav>
</main>
</body>
</html>
