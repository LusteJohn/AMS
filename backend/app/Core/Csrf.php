<?php

namespace App\Core;

class Csrf
{
    private const SESSION_KEY = 'csrf_tokens';

    public static function generate(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_KEY] = $token;

        return $token;
    }

    public static function validate(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? '';

        if (!$sessionToken || !$token) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public static function invalidate(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION[self::SESSION_KEY]);
    }

    public static function input(): string
    {
        $token = self::generate();

        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }

    public static function token(): string
    {
        return self::generate();
    }

    public static function fromRequest(): ?string
    {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $data = json_decode($raw, true);
            if (is_array($data) && isset($data['_csrf'])) {
                return (string)$data['_csrf'];
            }
        }

        return $_POST['_csrf'] ?? $_GET['_csrf'] ?? null;
    }
}
