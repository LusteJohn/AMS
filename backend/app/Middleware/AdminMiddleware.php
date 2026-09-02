<?php

namespace App\Middleware;

use App\Core\Response;

class AdminMiddleware
{
    public static function requireAdmin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $_SESSION['user'] ?? null;

        if (!$user || strtolower((string)($user['role'] ?? '')) !== 'admin') {
            $res = new Response();
            $res->unauthorized('Admin access required');
        }
    }
}
