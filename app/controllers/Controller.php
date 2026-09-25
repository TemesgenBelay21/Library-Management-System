<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/User.php';

abstract class Controller
{
    protected string $viewPath;

    protected array $sharedData = [];

    protected ?array $authUser = null;

    protected string $authRole = '';

    private bool $dispatchBlocked = false;

    public function __construct(array $sharedData = [])
    {
        $this->viewPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views';
        $this->beginSession();
        $this->hydrateAuthentication();
        $this->enforceControllerRole();
        $this->sharedData = array_merge([
            'authUser' => $this->authUser,
            'authRole' => $this->authRole,
            'isAuthenticated' => $this->isAuthenticated(),
            'isAdmin' => $this->hasRole('admin'),
            'isMember' => $this->hasRole('member'),
            'currentRoute' => $this->currentRoute(),
            'csrfToken' => $this->csrfToken(),
            'roleNavigation' => $this->navigationForRole(),
        ], $sharedData);
    }

    public function isDispatchBlocked(): bool
    {
        return $this->dispatchBlocked;
    }

    protected function render(string $view, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $content = $this->capture($view, $data);
        $layoutData = array_merge($this->sharedData, $data, ['content' => $content]);

        if ($layout === null) {
            return $content;
        }

        return $this->capture($layout, $layoutData);
    }

    protected function view(string $view, array $data = []): string
    {
        return $this->capture($view, $data);
    }

    protected function json(array $payload, int $status = 200): string
    {
        $encoded = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');

        return $encoded;
    }

    protected function redirect(string $path, int $status = 302): string
    {
        if (!$this->isSafeRedirectPath($path)) {
            throw new InvalidArgumentException('Redirect paths must target this application.');
        }

        http_response_code($status);
        header('Location: ' . $path, true, $status);

        return '';
    }

    protected function post(string $key, $default = null)
    {
        return array_key_exists($key, $_POST) ? $_POST[$key] : $default;
    }

    protected function query(string $key, $default = null)
    {
        return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
    }

    protected function input(string $key, $default = null)
    {
        $source = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? $_POST : $_GET;

        return array_key_exists($key, $source) ? $source[$key] : $default;
    }

    protected function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrfToken($token): bool
    {
        if (!is_string($token) || $token === '' || !isset($_SESSION['csrf_token'])) {
            return false;
        }

        $sessionToken = $_SESSION['csrf_token'];

        return is_string($sessionToken) && hash_equals($sessionToken, $token);
    }

    protected function integerInput(string $key, int $default = 0): int
    {
        $value = $this->input($key);

        if (is_int($value)) {
            return $value;
        }

        if (!is_string($value) || !ctype_digit($value)) {
            return $default;
        }

        return (int) $value;
    }

    protected function isAuthenticated(): bool
    {
        return is_array($this->authUser);
    }

    protected function hasRole(string $role): bool
    {
        return $this->isAuthenticated() && hash_equals($this->authRole, $role);
    }

    protected function currentUserId(): int
    {
        return is_array($this->authUser) ? (int) $this->authUser['id'] : 0;
    }

    protected function homeForRole(string $role): string
    {
        $homes = $this->contextMap()['homes'];
        $context = array_key_exists($role, $homes) ? $role : 'guest';

        return url((string) $homes[$context]);
    }

    protected function canAccess(string $permission): bool
    {
        return in_array($permission, $this->navigationForRole(), true);
    }

    protected function flash(string $type, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $allowedTypes = ['info', 'success', 'danger', 'warning'];
        $flashType = in_array($type, $allowedTypes, true) ? $type : 'info';
        $_SESSION['flash_messages'][] = [
            'type' => $flashType,
            'message' => $message,
        ];
    }

    protected function pullFlashMessages(): array
    {
        $messages = isset($_SESSION['flash_messages']) && is_array($_SESSION['flash_messages'])
            ? $_SESSION['flash_messages']
            : [];
        unset($_SESSION['flash_messages']);

        return $messages;
    }

    private function beginSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
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
    }

    private function hydrateAuthentication(): void
    {
        $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
        $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);

        if ($userId === false || $userId < 1) {
            $this->clearAuthentication();

            return;
        }

        if ($lastActivity > 0 && time() - $lastActivity > SESSION_IDLE_TIMEOUT) {
            $this->clearAuthentication();
            $this->flash('warning', 'Your session expired. Please sign in again.');

            return;
        }

        $userModel = new User();
        $user = $userModel->findActiveById((int) $userId);

        if (!is_array($user)) {
            $this->clearAuthentication();
            $this->flash('danger', 'This account is no longer active.');

            return;
        }

        unset($user['password']);
        $this->authUser = $user;
        $this->authRole = (string) $user['role'];
        $_SESSION['user_role'] = $this->authRole;
        $_SESSION['user_status'] = (string) $user['status'];
        $_SESSION['last_activity'] = time();
    }

    private function clearAuthentication(): void
    {
        unset(
            $_SESSION['user_id'],
            $_SESSION['user_role'],
            $_SESSION['user_status'],
            $_SESSION['authenticated_at'],
            $_SESSION['last_activity']
        );
        $this->authUser = null;
        $this->authRole = '';
    }

    private function enforceControllerRole(): void
    {
        $context = $this->contextMap();
        $requiredRole = $context['controller_roles'][get_class($this)] ?? null;

        if ($requiredRole === null || $this->hasRole((string) $requiredRole)) {
            return;
        }

        if (!$this->isAuthenticated()) {
            $this->flash('warning', 'Sign in to continue.');
            $this->redirect($this->homeForRole('guest'));
        } else {
            $this->flash('danger', 'You do not have access to that area.');
            $this->redirect($this->homeForRole($this->authRole));
        }

        $this->dispatchBlocked = true;
    }

    private function contextMap(): array
    {
        static $context = null;

        if ($context === null) {
            $path = CONFIG_PATH . DIRECTORY_SEPARATOR . 'redirects.php';
            $context = is_file($path) ? require $path : null;

            if (
                !is_array($context)
                || !isset($context['homes'], $context['controller_roles'], $context['permissions'])
                || !is_array($context['homes'])
                || !is_array($context['controller_roles'])
                || !is_array($context['permissions'])
            ) {
                throw new RuntimeException('Application context configuration is invalid.');
            }
        }

        return $context;
    }

    private function navigationForRole(): array
    {
        $permissions = $this->contextMap()['permissions'];

        return isset($permissions[$this->authRole]) && is_array($permissions[$this->authRole])
            ? $permissions[$this->authRole]
            : [];
    }

    private function currentRoute(): string
    {
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $requestPath = is_string($requestPath) ? trim($requestPath, '/') : '';
        $basePath = parse_url(APP_URL, PHP_URL_PATH);
        $basePath = is_string($basePath) ? trim($basePath, '/') : '';

        if ($basePath !== '' && ($requestPath === $basePath || strpos($requestPath, $basePath . '/') === 0)) {
            $requestPath = ltrim(substr($requestPath, strlen($basePath)), '/');
        }

        return $requestPath;
    }

    private function isSafeRedirectPath(string $path): bool
    {
        if ($path === '' || strpos($path, "\0") !== false || strpos($path, "\r") !== false || strpos($path, "\n") !== false) {
            return false;
        }

        if ($path[0] === '/') {
            return strpos($path, '//') !== 0;
        }

        $appHost = parse_url(APP_URL, PHP_URL_HOST);
        $targetHost = parse_url($path, PHP_URL_HOST);

        return is_string($appHost) && $appHost !== '' && hash_equals(strtolower($appHost), strtolower((string) $targetHost));
    }

    private function capture(string $view, array $data): string
    {
        $viewPath = $this->resolveViewPath($view);
        $data = array_merge($this->sharedData, $data);
        $outputLevel = ob_get_level();

        extract($data, EXTR_SKIP);
        ob_start();

        try {
            require $viewPath;
        } catch (Throwable $exception) {
            while (ob_get_level() > $outputLevel) {
                ob_end_clean();
            }

            throw $exception;
        }

        return (string) ob_get_clean();
    }

    private function resolveViewPath(string $view): string
    {
        if (
            $view === ''
            || strpos($view, "\0") !== false
            || strpos($view, '\\') !== false
            || preg_match('#(^|/)\.\.(/|$)#', $view) === 1
        ) {
            throw new InvalidArgumentException('Invalid view name.');
        }

        $path = $this->viewPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';

        if (!is_file($path)) {
            throw new RuntimeException(sprintf('View "%s" was not found.', $view));
        }

        return $path;
    }
}
