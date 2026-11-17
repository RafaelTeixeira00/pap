<?php

function start_app_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function app_database()
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $configPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'pap-private-data' . DIRECTORY_SEPARATOR . 'auth-config.php';
    if (!is_file($configPath)) {
        throw new RuntimeException('Falta o ficheiro privado de configuração da autenticação.');
    }

    $config = require $configPath;
    if (
        !is_array($config) ||
        !isset($config['host'], $config['database'], $config['username'], $config['password']) ||
        !is_string($config['host']) ||
        !is_string($config['database']) ||
        !is_string($config['username']) ||
        !is_string($config['password']) ||
        $config['host'] === '' ||
        $config['database'] === '' ||
        $config['username'] === ''
    ) {
        throw new RuntimeException('A configuração privada da autenticação é inválida.');
    }

    $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['database'] . ';charset=utf8mb4';
    $connection = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $connection;
}

function app_csrf_token()
{
    if (!isset($_SESSION['auth_csrf_token']) || !is_string($_SESSION['auth_csrf_token'])) {
        $_SESSION['auth_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['auth_csrf_token'];
}

function app_current_client()
{
    return isset($_SESSION['client']) && is_array($_SESSION['client'])
        ? $_SESSION['client']
        : null;
}

function require_client()
{
    $client = app_current_client();
    if ($client === null || !isset($client['id'])) {
        header('Location: login.php');
        exit;
    }

    $statement = app_database()->prepare(
        'SELECT id, name, email, is_active, is_admin FROM client_accounts WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => (int) $client['id']]);
    $account = $statement->fetch();

    if (!$account || (int) $account['is_active'] !== 1) {
        unset($_SESSION['client']);
        header('Location: login.php');
        exit;
    }

    $_SESSION['client'] = [
        'id' => (int) $account['id'],
        'name' => $account['name'],
        'email' => $account['email'],
        'is_admin' => (int) $account['is_admin'] === 1,
    ];

    return $_SESSION['client'];
}

function require_admin()
{
    $client = require_client();
    if (empty($client['is_admin'])) {
        unset($_SESSION['client']);
        header('Location: login.php');
        exit;
    }

    return $client;
}
