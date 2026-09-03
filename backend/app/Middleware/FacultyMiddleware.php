<?php

namespace App\Middleware;

use App\Core\Response;

class FacultyMiddleware
{
    public static function requireFaculty(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $_SESSION['user'] ?? null;

        if (!$user || strtolower((string)($user['role'] ?? '')) !== 'faculty') {
            $res = new Response();
            $res->unauthorized('Faculty access required');
        }
    }
}
