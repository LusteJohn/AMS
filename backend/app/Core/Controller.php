<?php

namespace App\Core;

abstract class Controller
{
    protected Response $response;
    private ?array $jsonBody = null;

    private function getJsonBody(): array
    {
        if ($this->jsonBody !== null) {
            return $this->jsonBody;
        }

        $raw = file_get_contents('php://input');
        if (!$raw) {
            $this->jsonBody = [];
            return $this->jsonBody;
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            $this->jsonBody = [];
            return $this->jsonBody;
        }

        $this->jsonBody = $data;
        return $this->jsonBody;
    }

    public function __construct()
    {
        $this->response = new Response();
    }

    protected function view(string $path, array $data = []): void
    {
        $file = __DIR__ . '/../app/views/' . $path . '.php';

        if (!file_exists($file)) {
            http_response_code(404);
            echo "View not found: {$path}";
            return;
        }

        $layout = $data['_layout'] ?? 'app';
        unset($data['_layout']);

        extract($data);

        if ($layout === false) {
            include $file;
            return;
        }

        ob_start();
        include $file;
        $content = ob_get_clean();

        $layoutFile = __DIR__ . '/../app/views/layout/' . $layout . '.php';

        if (file_exists($layoutFile)) {
            include $layoutFile;
            return;
        }

        echo $content;
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        $this->response->json($data, $statusCode);
    }

    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }

    protected function abort(int $statusCode, string $message = ''): void
    {
        http_response_code($statusCode);
        $this->response->json(['error' => $message ?: "Error {$statusCode}"], $statusCode);
    }

    protected function param(string $name, mixed $default = null): mixed
    {
        $json = $this->getJsonBody();
        return $_GET[$name] ?? $_POST[$name] ?? ($json[$name] ?? $default);
    }

    protected function input(string $name, mixed $default = null): mixed
    {
        $json = $this->getJsonBody();
        return $_POST[$name] ?? ($json[$name] ?? $default);
    }

    protected function query(string $name, mixed $default = null): mixed
    {
        return $_GET[$name] ?? $default;
    }

    protected function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    protected function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    protected function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    protected function isPut(): bool
    {
        return $this->method() === 'PUT';
    }

    protected function isDelete(): bool
    {
        return $this->method() === 'DELETE';
    }
}
