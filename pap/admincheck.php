<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

$email = app_admin_email();

try {
    $connection = app_database();
    $findAccount = $connection->prepare(
        'SELECT id, is_active FROM client_accounts WHERE LOWER(email) = :email LIMIT 1'
    );
    $findAccount->execute(['email' => $email]);
    $account = $findAccount->fetch();

    if (!$account) {
        fwrite(STDERR, "A conta {$email} ainda não existe. Executa create-admin.ps1 primeiro.\n");
        exit(1);
    }

    if ((int) $account['is_active'] !== 1) {
        fwrite(STDERR, "A conta {$email} está desativada. Ativa-a antes de configurar o administrador.\n");
        exit(1);
    }

    $connection->beginTransaction();
    $demoteOtherAccounts = $connection->prepare(
        'UPDATE client_accounts SET is_admin = 0 WHERE is_admin = 1 AND id <> :id'
    );
    $demoteOtherAccounts->execute(['id' => (int) $account['id']]);

    $promoteAccount = $connection->prepare(
        'UPDATE client_accounts SET is_admin = 1 WHERE id = :id AND is_active = 1'
    );
    $promoteAccount->execute(['id' => (int) $account['id']]);
    $connection->commit();

    fwrite(STDOUT, "Permissões verificadas: {$email} é o único administrador.\n");
} catch (PDOException $error) {
    if (isset($connection) && $connection->inTransaction()) {
        $connection->rollBack();
    }
    error_log('Falha ao verificar a conta de administrador: ' . $error->getMessage());
    fwrite(STDERR, "Não foi possível verificar a conta de administrador.\n");
    exit(1);
} catch (RuntimeException $error) {
    error_log('Falha de configuração ao verificar administrador: ' . $error->getMessage());
    fwrite(STDERR, "A autenticação não está configurada corretamente.\n");
    exit(1);
}
