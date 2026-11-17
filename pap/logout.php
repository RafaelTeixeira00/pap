<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'idioma.php';
start_app_session();

$token = $_POST['auth_csrf_token'] ?? '';
if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ||
    !is_string($token) ||
    !isset($_SESSION['auth_csrf_token']) ||
    !hash_equals($_SESSION['auth_csrf_token'], $token)
) {
    http_response_code(400);
    exit(t('Pedido inválido. Volta à área de cliente e tenta novamente.'));
}

$_SESSION = [];
session_regenerate_id(true);
header('Location: login.php');
exit;
