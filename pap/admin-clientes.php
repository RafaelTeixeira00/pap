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
    $action = $_POST['action'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $tokenIsValid = is_string($token) &&
        isset($_SESSION['auth_csrf_token']) &&
        hash_equals($_SESSION['auth_csrf_token'], $token);

    if ($action === 'create') {
        $password = $_POST['password'] ?? '';
        if (
            !$tokenIsValid ||
            !is_string($name) ||
            strlen(trim($name)) < 2 ||
            strlen(trim($name)) > 120 ||
            !is_string($email) ||
            strlen(trim($email)) > 254 ||
            !filter_var(trim($email), FILTER_VALIDATE_EMAIL) ||
            !is_string($password) ||
            strlen($password) < 12 ||
            strlen($password) > 72
        ) {
            $notice = 'Verifica os dados do cliente e tenta novamente.';
            $httpStatus = 400;
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            if ($passwordHash === false) {
                error_log('Falha ao proteger a palavra-passe inicial do cliente.');
                $notice = 'Não foi possível criar o cliente.';
                $httpStatus = 500;
            } else {
                try {
                    $statement = $connection->prepare(
                        'INSERT INTO client_accounts (name, email, password_hash) VALUES (:name, :email, :password_hash)'
                    );
                    $statement->execute([
                        'name' => trim($name),
                        'email' => strtolower(trim($email)),
                        'password_hash' => $passwordHash,
                    ]);
                    $_SESSION['admin_notice'] = 'Conta de cliente criada. Comunica a palavra-passe por um canal seguro.';
                    header('Location: admin-clientes.php');
                    exit;
                } catch (PDOException $error) {
                    if ($error->getCode() === '23000') {
                        $notice = 'Esse email já está associado a outra conta.';
                        $httpStatus = 409;
                    } else {
                        error_log('Falha ao criar cliente: ' . $error->getMessage());
                        $notice = 'Não foi possível criar o cliente.';
                        $httpStatus = 500;
                    }
                }
            }
        }
    } else {
        $clientIdInput = $_POST['client_id'] ?? null;
        $clientId = is_string($clientIdInput) ? filter_var($clientIdInput, FILTER_VALIDATE_INT) : false;
        $active = $_POST['is_active'] ?? '';

        if (
            !$tokenIsValid ||
            $clientId === false ||
            $clientId < 1 ||
            !is_string($name) ||
            strlen(trim($name)) < 2 ||
            strlen(trim($name)) > 120 ||
            !is_string($email) ||
            strlen(trim($email)) > 254 ||
            !filter_var(trim($email), FILTER_VALIDATE_EMAIL) ||
            !is_string($active) ||
            !in_array($active, ['0', '1'], true)
        ) {
            $notice = 'Verifica os dados do cliente e tenta novamente.';
            $httpStatus = 400;
        } else {
            $exists = $connection->prepare(
                'SELECT id FROM client_accounts WHERE id = :id AND is_admin = 0 LIMIT 1'
            );
            $exists->execute(['id' => $clientId]);
            if (!$exists->fetchColumn()) {
                $notice = 'Não foi encontrado esse cliente.';
                $httpStatus = 404;
            } else {
                try {
                    $statement = $connection->prepare(
                        'UPDATE client_accounts SET name = :name, email = :email, is_active = :is_active '
                        . 'WHERE id = :id AND is_admin = 0'
                    );
                    $statement->execute([
                        'name' => trim($name),
                        'email' => strtolower(trim($email)),
                        'is_active' => (int) $active,
                        'id' => $clientId,
                    ]);
                    $_SESSION['admin_notice'] = 'Dados do cliente atualizados.';
                    header('Location: admin-clientes.php');
                    exit;
                } catch (PDOException $error) {
                    if ($error->getCode() === '23000') {
                        $notice = 'Esse email já está associado a outra conta.';
                        $httpStatus = 409;
                    } else {
                        error_log('Falha ao atualizar cliente: ' . $error->getMessage());
                        $notice = 'Não foi possível atualizar o cliente.';
                        $httpStatus = 500;
                    }
                }
            }
        }
    }
}

if ($httpStatus !== 200) {
    http_response_code($httpStatus);
}

$clients = $connection->query(
    'SELECT id, name, email, is_active, created_at FROM client_accounts '
    . 'WHERE is_admin = 0 ORDER BY name, id'
)->fetchAll();
?>
<!doctype html>
<html lang="<?= e(app_language_tag()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('Gerir clientes')) ?> — PowerLift Gym</title>
    <style>
        :root { color-scheme: dark; --bg: #090909; --panel: #111; --red: #ed1025; --text: #f5f5f5; --muted: #b9b9b9; --line: #292929; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 26px 16px; background: var(--bg); color: var(--text); font: 15px/1.5 Arial, Helvetica, sans-serif; }
        main { width: min(100%, 1120px); margin: 0 auto; }
        h1 { margin: 0 0 8px; }
        p { color: var(--muted); }
        a { color: #fff; }
        .back { display: inline-block; margin-bottom: 20px; color: var(--muted); }
        .notice { margin: 15px 0; padding: 12px; border: 1px solid #276443; border-radius: 8px; background: #10251a; color: #b9f1ce; }
        .notice.error { border-color: #74313b; background: #2a1115; color: #ffc0c7; }
        .table-wrap { overflow-x: auto; margin-top: 20px; border: 1px solid var(--line); border-radius: 10px; }
        table { width: 100%; min-width: 760px; border-collapse: collapse; background: var(--panel); }
        th, td { padding: 12px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        th { color: var(--muted); }
        input, select, button { min-height: 38px; padding: 0 9px; border: 1px solid var(--line); border-radius: 6px; background: #090909; color: #fff; font: inherit; }
        input { width: 100%; }
        button { border-color: var(--red); background: var(--red); cursor: pointer; }
        .edit-form { display: grid; grid-template-columns: minmax(140px, 1fr) minmax(180px, 1.4fr) 120px auto; gap: 8px; min-width: 600px; }
        .empty { color: var(--muted); }
        .create-form { display: grid; grid-template-columns: minmax(150px, 1fr) minmax(180px, 1.4fr) minmax(180px, 1.2fr) auto; gap: 8px; align-items: end; padding: 16px; border: 1px solid var(--line); border-radius: 10px; background: var(--panel); }
        .create-form label { display: grid; gap: 5px; color: var(--muted); font-size: 13px; }
        @media (max-width: 760px) { .create-form { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main>
    <a class="back" href="admin.php">← <?= e(t('Painel de administração')) ?></a>
    <h1><?= e(t('Gerir clientes')) ?></h1>
    <p><?= e(t('A palavra-passe inicial deve ter pelo menos 12 caracteres e ser comunicada ao cliente por um canal seguro.')) ?></p>
    <?php if ($notice !== ''): ?>
        <p class="notice<?= $httpStatus === 200 ? '' : ' error' ?>" role="<?= $httpStatus === 200 ? 'status' : 'alert' ?>"><?= e(t($notice)) ?></p>
    <?php endif; ?>
    <?php if (count($clients) === 0): ?>
        <p class="empty"><?= e(t('Ainda não há clientes.')) ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th><?= e(t('Cliente')) ?></th><th><?= e(t('Criado em')) ?></th></tr></thead>
                <tbody>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?= e($client['id']) ?></td>
                        <td>
                            <form class="edit-form" method="post">
                                <input type="hidden" name="auth_csrf_token" value="<?= e(app_csrf_token()) ?>">
                                <input type="hidden" name="client_id" value="<?= e($client['id']) ?>">
                                <input name="name" aria-label="<?= e(t('Nome')) ?>" required minlength="2" maxlength="120" value="<?= e($client['name']) ?>">
                                <input name="email" type="email" aria-label="<?= e(t('Email')) ?>" required maxlength="254" value="<?= e($client['email']) ?>">
                                <select name="is_active" aria-label="<?= e(t('Estado da conta')) ?>">
                                    <option value="1"<?= (int) $client['is_active'] === 1 ? ' selected' : '' ?>><?= e(t('Ativa')) ?></option>
                                    <option value="0"<?= (int) $client['is_active'] === 0 ? ' selected' : '' ?>><?= e(t('Desativada')) ?></option>
                                </select>
                                <button type="submit"><?= e(t('Guardar')) ?></button>
                            </form>
                        </td>
                        <td><?= e(date('d/m/Y', strtotime($client['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <h2><?= e(t('Criar conta de cliente')) ?></h2>
    <form class="create-form" method="post">
        <input type="hidden" name="auth_csrf_token" value="<?= e(app_csrf_token()) ?>">
        <input type="hidden" name="action" value="create">
        <label><?= e(t('Nome')) ?><input name="name" required minlength="2" maxlength="120"></label>
        <label><?= e(t('Email')) ?><input name="email" type="email" required maxlength="254"></label>
        <label><?= e(t('Palavra-passe inicial')) ?><input name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></label>
        <button type="submit"><?= e(t('Criar cliente')) ?></button>
    </form>
</main>
</body>
</html>
