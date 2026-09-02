<?php

namespace App\Middleware;

use App\Core\Response;

class StudentMiddleware
{
    public static function requireStudent(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $_SESSION['user'] ?? null;

        if (!$user || strtolower((string)($user['role'] ?? '')) !== 'student') {
            $res = new Response();
            $res->unauthorized('Student access required');
        }
    }
}
