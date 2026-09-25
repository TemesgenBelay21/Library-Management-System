<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

Environment::load(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');

$appUrl = getenv('APP_URL');
$appUrl = is_string($appUrl) ? rtrim(trim($appUrl), '/') : '';
$appEnvironment = getenv('APP_ENV');
$appEnvironment = is_string($appEnvironment) && $appEnvironment !== '' ? $appEnvironment : 'production';
$appDebug = filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN);

define('APP_NAME', 'AuraLib');
define('APP_ENV', $appEnvironment);
define('APP_DEBUG', $appDebug);
define('APP_URL', $appUrl);
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'app');
define('VIEW_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'views');
define('CONFIG_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'config');
define('PUBLIC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'public');
define('STORAGE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'storage');
define('UPLOAD_PATH', PUBLIC_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('SESSION_NAME', 'auralib_session');
define('SESSION_IDLE_TIMEOUT', 7200);
define('DEFAULT_PER_PAGE', 12);
define('MAX_PER_PAGE', 48);
define('LOAN_DAYS', 14);
define('FINE_PER_DAY', 1.00);
define('DISPLAY_DATE_FORMAT', 'M j, Y');
define('DISPLAY_DATETIME_FORMAT', 'M j, Y · H:i');

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $normalizedPath = ltrim($path, '/');

        return APP_URL . ($normalizedPath === '' ? '' : '/' . $normalizedPath);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('upload_url')) {
    function upload_url(string $path): string
    {
        return url('uploads/' . ltrim($path, '/'));
    }
}
