<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

try {
    $name = getenv('POWERLIFT_CLIENT_NAME');
    $email = getenv('POWERLIFT_CLIENT_EMAIL');
    $password = getenv('POWERLIFT_CLIENT_PASSWORD');
    $role = getenv('POWERLIFT_ACCOUNT_ROLE');
    if (!is_string($name) || !is_string($email) || !is_string($password)) {
        fwrite(STDERR, "Usa o script create-client.ps1 para pedir os dados com segurança.\n");
        exit(1);
    }
    if ($role === false || $role === '') {
        $role = 'client';
    }
    if (!in_array($role, ['client', 'admin'], true)) {
        fwrite(STDERR, "Tipo de conta inválido.\n");
        exit(1);
    }

    $name = trim($name);
    $email = strtolower(trim($email));

    if ($email === app_admin_email() && $role !== 'admin') {
        fwrite(STDERR, "Este email está reservado para a conta de administração.\n");
        exit(1);
    }
    if ($role === 'admin' && $email !== app_admin_email()) {
        fwrite(STDERR, "Só o email administrativo configurado pode ter permissões de administrador.\n");
        exit(1);
    }

    if (strlen($name) < 2 || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        fwrite(STDERR, "Nome ou email inválido.\n");
        exit(1);
    }
    if (strlen($password) < 12 || strlen($password) > 72) {
        fwrite(STDERR, "A palavra-passe deve ter entre 12 e 72 bytes.\n");
        exit(1);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        throw new RuntimeException('Não foi possível proteger a palavra-passe.');
    }

    $statement = app_database()->prepare(
        'INSERT INTO client_accounts (name, email, password_hash, is_admin) VALUES (:name, :email, :password_hash, :is_admin)'
    );
    $statement->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => $passwordHash,
        'is_admin' => $role === 'admin' ? 1 : 0,
    ]);

    fwrite(STDOUT, "Conta criada com sucesso para {$email}.\n");
} catch (PDOException $error) {
    error_log('Falha ao criar conta de cliente: ' . $error->getMessage());
    fwrite(STDERR, "Não foi possível criar a conta. Verifica a ligação à base de dados e se o email já existe.\n");
    exit(1);
} catch (RuntimeException $error) {
    error_log('Falha na configuração ao criar conta: ' . $error->getMessage());
    fwrite(STDERR, "A autenticação não está configurada corretamente. Consulta as instruções de configuração.\n");
    exit(1);
}
