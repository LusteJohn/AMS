<?php

// If this file is accessed directly (not via public/index.php), ensure autoload is available
if (!class_exists('\App\\Core\\Router')) {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }
}

use App\Core\Router;
use App\Core\Database;
use App\Core\Csrf;
use App\Controller\UserController;
use App\Middleware\AdminMiddleware;

$router = new Router();

// CORS middleware - adjust origin as needed for your frontend
$router->middleware(function () {
    // Allow your frontend origin (change to specific origin in production)
    header('Access-Control-Allow-Origin: http://localhost:5173');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
    header('Access-Control-Allow-Credentials: true');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
});

$router->get('/api/health', function () {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $connection = Database::getInstance()->getConnection();
        $connection->query('SELECT 1');

        echo json_encode([
            'success' => true,
            'message' => 'API and database are connected',
            'data' => ['api' => 'ok', 'database' => 'ok'],
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $exception) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'message' => 'API is reachable, but the database connection failed',
            'data' => ['api' => 'ok', 'database' => 'error'],
        ], JSON_UNESCAPED_UNICODE);
    }
});

$router->get('/api/csrf-token', function () {
    $response = new \App\Core\Response();
    $response->success(['csrf_token' => Csrf::token()]);
});

$router->groupWithMiddleware('/api', function (Router $router) {
    $router->get('/users', [UserController::class, 'index']);
    $router->get('/users/{id}', [UserController::class, 'show']);
    $router->post('/users', [UserController::class, 'store']);
    $router->put('/users/{id}', [UserController::class, 'update']);
    $router->delete('/users/{id}', [UserController::class, 'destroy']);
}, [AdminMiddleware::class . '::requireAdmin']);

$router->post('/api/auth/register', [UserController::class, 'register']);
$router->post('/api/auth/login', [UserController::class, 'login']);
$router->post('/api/auth/logout', [UserController::class, 'logout']);
$router->get('/api/auth/session', [UserController::class, 'session']);

return $router;
