<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Method not allowed.';

    exit;
}

$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_start();

$submittedToken = isset($_POST['_token']) && is_string($_POST['_token']) ? $_POST['_token'] : '';
$sessionToken = isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token'])
    ? $_SESSION['csrf_token']
    : '';

if ($submittedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    $_SESSION['flash_messages'][] = [
        'type' => 'danger',
        'message' => 'The sign-out request could not be verified.',
    ];
    header('Location: ' . url('login'), true, 303);

    exit;
}

$_SESSION = [];
$cookieParams = session_get_cookie_params();
setcookie(
    session_name(),
    '',
    time() - 42000,
    $cookieParams['path'],
    $cookieParams['domain'],
    (bool) $cookieParams['secure'],
    (bool) $cookieParams['httponly']
);
unset($_COOKIE[session_name()]);
session_destroy();
gc_collect_cycles();
header('Location: ' . url('login'), true, 303);
exit;
