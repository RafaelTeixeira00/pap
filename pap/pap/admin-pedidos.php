<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'idioma.php';
start_app_session();
require_admin();

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$connection = app_database();
$notice = $_SESSION['admin_notice'] ?? '';
unset($_SESSION['admin_notice']);
$httpStatus = 200;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['auth_csrf_token'] ?? '';
    $bookingIdInput = $_POST['booking_id'] ?? null;
    $bookingId = is_string($bookingIdInput) ? filter_var($bookingIdInput, FILTER_VALIDATE_INT) : false;
    $status = $_POST['status'] ?? '';

    if (
        !is_string($token) ||
        !isset($_SESSION['auth_csrf_token']) ||
        !hash_equals($_SESSION['auth_csrf_token'], $token) ||
        $bookingId === false ||
        $bookingId < 1 ||
        !is_string($status) ||
        !in_array($status, ['pending', 'confirmed', 'cancelled'], true)
    ) {
        $notice = 'O pedido não é válido. Atualiza a página e tenta novamente.';
        $httpStatus = 400;
    } else {
        $exists = $connection->prepare('SELECT id FROM bookings WHERE id = :id LIMIT 1');
        $exists->execute(['id' => $bookingId]);
        if (!$exists->fetchColumn()) {
            $notice = 'Não foi encontrada essa marcação.';
            $httpStatus = 404;
        } else {
            $statement = $connection->prepare('UPDATE bookings SET status = :status WHERE id = :id');
            $statement->execute(['status' => $status, 'id' => $bookingId]);
            $_SESSION['admin_notice'] = 'Estado da marcação atualizado.';
            header('Location: admin-pedidos.php');
            exit;
        }
    }
}

if ($httpStatus !== 200) {
    http_response_code($httpStatus);
}

$plans = [
    'base' => t('Base'),
    'power' => t('Power'),
    'personal' => t('Personal Training'),
];
$classDefinitions = require __DIR__ . DIRECTORY_SEPARATOR . 'aulas-config.php';
$classNames = [];
foreach ($classDefinitions as $key => $class) {
    $classNames[$key] = $class['title'];
}
$statement = $connection->query(
    'SELECT id, name, email, phone, plan_key, class_key, booking_date, booking_time, status, created_at '
    . 'FROM bookings ORDER BY booking_date DESC, booking_time DESC, id DESC'
);
$bookings = $statement->fetchAll();
?>
<!doctype html>
<html lang="<?= e(app_language_tag()) ?>">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="Icon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('Gerir marcações')) ?> — PowerLift Gym</title>
    <style>
        :root { color-scheme: dark; --bg: #090909; --panel: #111; --red: #ed1025; --text: #f5f5f5; --muted: #b9b9b9; --line: #292929; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 26px 16px; background: var(--bg); color: var(--text); font: 15px/1.5 Arial, Helvetica, sans-serif; }
        main { width: min(100%, 1180px); margin: 0 auto; }
        h1 { margin: 0 0 8px; }
        a { color: #fff; }
        .back { display: inline-block; margin-bottom: 20px; color: var(--muted); }
        .notice { margin: 15px 0; padding: 12px; border: 1px solid #276443; border-radius: 8px; background: #10251a; color: #b9f1ce; }
        .notice.error { border-color: #74313b; background: #2a1115; color: #ffc0c7; }
        .table-wrap { overflow-x: auto; margin-top: 20px; border: 1px solid var(--line); border-radius: 10px; }
        table { width: 100%; min-width: 860px; border-collapse: collapse; background: var(--panel); }
        th, td { padding: 12px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        th { color: var(--muted); }
        select, button { min-height: 38px; padding: 0 9px; border: 1px solid var(--line); border-radius: 6px; background: #090909; color: #fff; font: inherit; }
        button { border-color: var(--red); background: var(--red); cursor: pointer; }
        .status-form { display: flex; gap: 6px; }
        .empty { color: var(--muted); }
    </style>
</head>
<body>
<main>
    <a class="back" href="admin.php">← <?= e(t('Painel de administração')) ?></a>
    <h1><?= e(t('Gerir marcações')) ?></h1>
    <?php if ($notice !== ''): ?>
        <p class="notice<?= $httpStatus === 200 ? '' : ' error' ?>" role="<?= $httpStatus === 200 ? 'status' : 'alert' ?>"><?= e(t($notice)) ?></p>
    <?php endif; ?>
    <?php if (count($bookings) === 0): ?>
        <p class="empty"><?= e(t('Ainda não há marcações.')) ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th>#</th><th><?= e(t('Data')) ?></th><th><?= e(t('Hora')) ?></th>
                    <th><?= e(t('Cliente')) ?></th><th><?= e(t('Contacto')) ?></th>
                    <th><?= e(t('Plano / Aula')) ?></th><th><?= e(t('Estado')) ?></th><th><?= e(t('Ação')) ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?= e($booking['id']) ?></td>
                        <td><?= e(date('d/m/Y', strtotime($booking['booking_date']))) ?></td>
                        <td><?= e(substr($booking['booking_time'], 0, 5)) ?></td>
                        <td><?= e($booking['name']) ?></td>
                        <td><a href="mailto:<?= e($booking['email']) ?>"><?= e($booking['email']) ?></a><br><?= e($booking['phone']) ?></td>
                        <td>
                            <?php if ($booking['class_key'] !== null && isset($classNames[$booking['class_key']])): ?>
                                <?= e(t($classNames[$booking['class_key']])) ?>
                            <?php else: ?>
                                <?= e($plans[$booking['plan_key']] ?? $booking['plan_key']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= e(t($booking['status'] === 'pending' ? 'Pendente' : ($booking['status'] === 'confirmed' ? 'Confirmada' : 'Cancelada'))) ?></td>
                        <td>
                            <form class="status-form" method="post">
                                <input type="hidden" name="auth_csrf_token" value="<?= e(app_csrf_token()) ?>">
                                <input type="hidden" name="booking_id" value="<?= e($booking['id']) ?>">
                                <select name="status" aria-label="<?= e(t('Estado')) ?>">
                                    <option value="pending"<?= $booking['status'] === 'pending' ? ' selected' : '' ?>><?= e(t('Pendente')) ?></option>
                                    <option value="confirmed"<?= $booking['status'] === 'confirmed' ? ' selected' : '' ?>><?= e(t('Confirmada')) ?></option>
                                    <option value="cancelled"<?= $booking['status'] === 'cancelled' ? ' selected' : '' ?>><?= e(t('Cancelada')) ?></option>
                                </select>
                                <button type="submit"><?= e(t('Guardar')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
