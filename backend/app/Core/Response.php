<?php

namespace App\Core;

class Response
{
    public function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): void
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        $this->json($response, $statusCode);
    }

    public function error(string $message = 'Error', mixed $errors = null, int $statusCode = 400): void
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        $this->json($response, $statusCode);
    }

    public function paginated(array $data, int $page, int $perPage, int $total, string $message = 'Success'): void
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'lastPage' => ceil($total / $perPage),
            ],
        ];

        $this->json($response, 200);
    }

    public function created(mixed $data, string $message = 'Created successfully'): void
    {
        $this->success($data, $message, 201);
    }

    public function updated(mixed $data = null, string $message = 'Updated successfully'): void
    {
        $this->success($data, $message, 200);
    }

    public function deleted(string $message = 'Deleted successfully'): void
    {
        $this->success(null, $message, 200);
    }

    public function badRequest(string $message = 'Bad request', mixed $errors = null): void
    {
        $this->error($message, $errors, 400);
    }

    public function unauthorized(string $message = 'Unauthorized'): void
    {
        $this->error($message, null, 401);
    }

    public function forbidden(string $message = 'Forbidden'): void
    {
        $this->error($message, null, 403);
    }

    public function notFound(string $message = 'Not found'): void
    {
        $this->error($message, null, 404);
    }

    public function serverError(string $message = 'Internal server error'): void
    {
        $this->error($message, null, 500);
    }

    public function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    public function download(string $filePath, string $fileName = ''): void
    {
        if (!file_exists($filePath)) {
            $this->notFound('File not found');
        }

        $fileName = $fileName ?: basename($filePath);
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"{$fileName}\"");
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}
