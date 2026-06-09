<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix  = 'UrlShortener\\';
    $baseDir = __DIR__ . '/src/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
