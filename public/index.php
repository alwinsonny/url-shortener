<?php

declare(strict_types=1);

// Serve static files when using PHP's built-in server (php -S).
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    if (is_file($file)) {
        $mimeTypes = [
            'js'   => 'application/javascript',
            'css'  => 'text/css',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'ico'  => 'image/x-icon',
            'svg'  => 'image/svg+xml',
        ];

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
            readfile($file);
            exit;
        }

        return false;
    }
}

require_once dirname(__DIR__) . '/autoload.php';

use UrlShortener\Database;
use UrlShortener\Request;
use UrlShortener\Router;
use UrlShortener\UrlRepository;

$scheme   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl  = rtrim(isset($_ENV['APP_BASE_URL']) ? (string) $_ENV['APP_BASE_URL'] : "{$scheme}://{$host}", '/');
$basePath = '/' . trim(isset($_ENV['APP_BASE_PATH']) ? (string) $_ENV['APP_BASE_PATH'] : (string) ($_SERVER['SCRIPT_NAME'] ?? ''), '/');

if (str_ends_with($basePath, '/index.php')) {
    $basePath = substr($basePath, 0, -strlen('/index.php')) ?: '/';
}

try {
    $db   = new Database(dirname(__DIR__) . '/storage/urls.db');

    $router = new Router(
        new Request(),
        new UrlRepository($db->getConnection()),
        $basePath,
        $baseUrl
    );

    $router->dispatch();

} catch (\Throwable $e) {
    error_log('[urlshortener] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    if (!headers_sent()) {
        http_response_code(500);
    }

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Error</title></head>'
       . '<body style="font-family:sans-serif;padding:2rem;">'
       . '<h1>Something went wrong</h1><p>Please try again later.</p>'
       . '</body></html>';
}
