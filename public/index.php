<?php

declare(strict_types=1);

final class Router
{
    private const CONTROLLER_PATHS = [
        'AuthController' => '/../app/controllers/AuthController.php',
        'BookController' => '/../app/controllers/BookController.php',
        'AdminController' => '/../app/controllers/AdminController.php',
        'MemberController' => '/../app/controllers/MemberController.php',
    ];

    public static function dispatch(string $method, string $path, array $routes): void
    {
        $pathMatched = false;

        foreach ($routes as $route) {
            $parameters = self::match($route['path'], $path);

            if ($parameters === null) {
                continue;
            }

            $pathMatched = true;

            if ($route['method'] !== $method) {
                continue;
            }

            $controllerName = $route['controller'];
            $controllerPath = __DIR__ . self::CONTROLLER_PATHS[$controllerName];
            $baseControllerPath = __DIR__ . '/../app/controllers/Controller.php';

            if (is_file($baseControllerPath)) {
                require_once $baseControllerPath;
            }

            if (is_file($controllerPath)) {
                require_once $controllerPath;
            }

            if (!class_exists($controllerName)) {
                self::fail(500, 'The requested application module is unavailable.');

                return;
            }

            $controller = new $controllerName();

            if ($controller->isDispatchBlocked()) {
                return;
            }

            $action = $route['action'];

            if (!method_exists($controller, $action)) {
                self::fail(500, 'The requested application action is unavailable.');

                return;
            }

            echo $controller->{$action}($parameters);

            return;
        }

        if ($pathMatched) {
            self::fail(405, 'This request method is not allowed for the route.');

            return;
        }

        self::fail(404, 'The requested resource could not be found.');
    }

    private static function match(string $pattern, string $path): ?array
    {
        $patternSegments = self::segments($pattern);
        $pathSegments = self::segments($path);

        if (count($patternSegments) !== count($pathSegments)) {
            return null;
        }

        $parameters = [];

        foreach ($patternSegments as $index => $patternSegment) {
            $pathSegment = $pathSegments[$index];
            $parameterName = null;

            if (strlen($patternSegment) > 2 && $patternSegment[0] === '{') {
                $parameterName = substr($patternSegment, 1, -1);
            } elseif ($patternSegment !== $pathSegment) {
                return null;
            }

            if ($parameterName !== null) {
                if ($pathSegment === '' || !ctype_digit($pathSegment)) {
                    return null;
                }

                $parameters[$parameterName] = (int) $pathSegment;
            }
        }

        return $parameters;
    }

    private static function segments(string $path): array
    {
        $path = trim($path, '/');

        return $path === '' ? [] : explode('/', $path);
    }

    public static function requestPath(): string
    {
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $requestUri = is_string($requestUri) ? $requestUri : '/';
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $scriptDirectory = str_replace('\\', '/', dirname($scriptName));
        $scriptDirectory = rtrim($scriptDirectory, '/');

        if ($scriptDirectory !== '' && $scriptDirectory !== '.' && strpos($requestUri, $scriptDirectory) === 0) {
            $requestUri = substr($requestUri, strlen($scriptDirectory));
        }

        $requestUri = preg_replace('#^/index\.php#', '', $requestUri);
        $requestUri = preg_replace('#/+#', '/', $requestUri);

        return trim(is_string($requestUri) ? rawurldecode($requestUri) : '/', '/');
    }

    private static function fail(int $status, string $message): void
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $message;
    }
}

$routes = [
    ['method' => 'GET', 'path' => '', 'controller' => 'AuthController', 'action' => 'home'],
    ['method' => 'GET', 'path' => 'login', 'controller' => 'AuthController', 'action' => 'showLogin'],
    ['method' => 'POST', 'path' => 'login', 'controller' => 'AuthController', 'action' => 'login'],
    ['method' => 'GET', 'path' => 'register', 'controller' => 'AuthController', 'action' => 'showRegister'],
    ['method' => 'POST', 'path' => 'register', 'controller' => 'AuthController', 'action' => 'register'],
    ['method' => 'GET', 'path' => 'dashboard', 'controller' => 'AuthController', 'action' => 'dashboard'],
    ['method' => 'GET', 'path' => 'admin', 'controller' => 'AdminController', 'action' => 'dashboard'],
    ['method' => 'GET', 'path' => 'admin/books', 'controller' => 'AdminController', 'action' => 'books'],
    ['method' => 'GET', 'path' => 'admin/books/create', 'controller' => 'AdminController', 'action' => 'createBook'],
    ['method' => 'GET', 'path' => 'admin/books/search', 'controller' => 'AdminController', 'action' => 'searchBooks'],
    ['method' => 'GET', 'path' => 'admin/books/{id}', 'controller' => 'AdminController', 'action' => 'book'],
    ['method' => 'GET', 'path' => 'admin/books/{id}/edit', 'controller' => 'AdminController', 'action' => 'editBook'],
    ['method' => 'POST', 'path' => 'admin/books', 'controller' => 'AdminController', 'action' => 'storeBook'],
    ['method' => 'POST', 'path' => 'admin/books/{id}', 'controller' => 'AdminController', 'action' => 'updateBook'],
    ['method' => 'POST', 'path' => 'admin/books/{id}/delete', 'controller' => 'AdminController', 'action' => 'deleteBook'],
    ['method' => 'GET', 'path' => 'admin/transactions', 'controller' => 'AdminController', 'action' => 'transactions'],
    ['method' => 'GET', 'path' => 'admin/transactions/issue', 'controller' => 'AdminController', 'action' => 'issueForm'],
    ['method' => 'POST', 'path' => 'admin/transactions/issue', 'controller' => 'AdminController', 'action' => 'issueBook'],
    ['method' => 'GET', 'path' => 'admin/transactions/{id}', 'controller' => 'AdminController', 'action' => 'transaction'],
    ['method' => 'GET', 'path' => 'admin/transactions/overdue', 'controller' => 'AdminController', 'action' => 'overdue'],
    ['method' => 'GET', 'path' => 'admin/members/search', 'controller' => 'AdminController', 'action' => 'searchMembers'],
    ['method' => 'GET', 'path' => 'admin/transactions/issue/books', 'controller' => 'AdminController', 'action' => 'searchIssueableBooks'],
    ['method' => 'POST', 'path' => 'admin/transactions/{id}/return', 'controller' => 'AdminController', 'action' => 'returnBook'],
    ['method' => 'GET', 'path' => 'member', 'controller' => 'MemberController', 'action' => 'dashboard'],
    ['method' => 'GET', 'path' => 'member/catalog', 'controller' => 'MemberController', 'action' => 'catalog'],
    ['method' => 'GET', 'path' => 'member/history', 'controller' => 'MemberController', 'action' => 'history'],
    ['method' => 'GET', 'path' => 'catalog', 'controller' => 'MemberController', 'action' => 'catalog'],
    ['method' => 'GET', 'path' => 'catalog/{id}/cover', 'controller' => 'BookController', 'action' => 'cover'],
    ['method' => 'GET', 'path' => 'catalog/{id}', 'controller' => 'MemberController', 'action' => 'showBook'],
];

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

try {
    Router::dispatch(
        strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
        Router::requestPath(),
        $routes
    );
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'A temporary application error occurred.';
}
