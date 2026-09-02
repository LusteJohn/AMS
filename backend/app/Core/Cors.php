<?php

namespace App\Core;

class Cors
{
    /**
     * Attach CORS middleware to the router.
     * This will add headers to all responses and respond to OPTIONS preflight.
     */
    public static function attach(Router $router): void
    {
        $router->middleware(function () {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

            // When credentials are allowed, Access-Control-Allow-Origin cannot be '*'
            if (isset($_SERVER['HTTP_ORIGIN'])) {
                header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
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
        });
    }
}
