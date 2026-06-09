<?php

declare(strict_types=1);

namespace UrlShortener;
 
final class Request
{
    private readonly string $method;
    private readonly string $path;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        $rawPath    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $this->path = '/' . trim($rawPath, '/');
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    //Retrieves and trims a POST value
    public function post(string $key, string $default = ''): string
    {
        return trim((string) ($_POST[$key] ?? $default));
    }
}
