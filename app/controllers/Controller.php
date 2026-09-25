<?php

declare(strict_types=1);

abstract class Controller
{
    protected string $viewPath;

    protected array $sharedData = [];

    public function __construct(array $sharedData = [])
    {
        $this->viewPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views';
        $this->sharedData = $sharedData;
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
        if ($path === '' || $path[0] !== '/' || strpos($path, '//') === 0) {
            throw new InvalidArgumentException('Redirect paths must be application-relative.');
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
