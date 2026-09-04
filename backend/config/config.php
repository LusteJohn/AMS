<?php

// Simple config loader using a .env file with INI-like format.
$env = @parse_ini_file(__DIR__ . '/../.env') ?: [];

// Accept multiple common variable names and provide sensible defaults
define('DB_HOST',     $env['DB_HOST'] ?? $env['DB_HOSTNAME'] ?? getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT',     $env['DB_PORT'] ?? $env['DB_SOCKET'] ?? getenv('DB_PORT') ?: '3306');
define('DB_DATABASE', $env['DB_NAME'] ?? $env['DB_DATABASE'] ?? getenv('DB_NAME') ?: getenv('DB_DATABASE') ?: '');
define('DB_USERNAME', $env['DB_USER'] ?? $env['DB_USERNAME'] ?? getenv('DB_USER') ?: getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', $env['DB_PASS'] ?? $env['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: '');
define('APP_URL',     $env['APP_URL'] ?? getenv('APP_URL') ?: '');

define('APP_TIMEZONE', $env['APP_TIMEZONE'] ?? getenv('APP_TIMEZONE') ?: 'Asia/Manila');
date_default_timezone_set(APP_TIMEZONE);