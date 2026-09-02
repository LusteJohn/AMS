<?php

// Simple config loader using a .env file with INI-like format.
$env = @parse_ini_file(__DIR__ . '/../.env') ?: [];

// Accept multiple common variable names and provide sensible defaults
define('DB_HOST',     $env['DB_HOST'] ?? $env['DB_HOSTNAME'] ?? '127.0.0.1');
define('DB_PORT',     $env['DB_PORT'] ?? $env['DB_SOCKET'] ?? '3306');
define('DB_DATABASE', $env['DB_NAME'] ?? $env['DB_DATABASE'] ?? '');
define('DB_USERNAME', $env['DB_USER'] ?? $env['DB_USERNAME'] ?? 'root');
define('DB_PASSWORD', $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? '');
define('APP_URL',     $env['APP_URL'] ?? $env['APP_URL'] ?? '');

define('APP_TIMEZONE', $env['APP_TIMEZONE'] ?? 'Asia/Manila');
date_default_timezone_set(APP_TIMEZONE);