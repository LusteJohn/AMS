<?php

// Front controller - boot the application and dispatch routes

// Send CORS headers early so preflight requests still get a valid response
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Temporarily disable Composer platform check so older PHP on dev doesn't abort requests.
// Set this before including Composer's autoload so platform_check.php sees it.
putenv('DISABLE_COMPOSER_PLATFORM_CHECK=1');

require_once __DIR__ . '/../vendor/autoload.php';

$router = require __DIR__ . '/../route/api.php';

if (!is_object($router) || !method_exists($router, 'dispatch')) {
    http_response_code(500);
    echo "Router not available";
    exit;
}

// Attach CORS middleware (ensures preflight requests are handled)
if (class_exists('\App\\Core\\Cors')) {
    \App\Core\Cors::attach($router);
}
$router->dispatch();
