<?php

declare(strict_types=1);

namespace UrlShortener;

use InvalidArgumentException;
use RuntimeException;

final class Router
{
    private const CODE_PATTERN = '/^[a-zA-Z0-9]{1,12}$/';

    public function __construct(
        private readonly Request       $request,
        private readonly string        $basePath,
        private readonly string        $baseUrl,
    ) {}

    public function dispatch(): void
    {
        $path = $this->resolveLocalPath($this->request->getPath());

        $this->handleRedirect(ltrim($path, '/'));
    }

    // TODO: Need to fix the refreshing of the page and from resubmission
    private function handleRedirect(string $code): void
    {
        if (!preg_match(self::CODE_PATTERN, $code)) {
            $this->render404();
            return;
        }

       // TODO need to fix  data from database
        $testData = [
            'abc123' => [
                'id'         => 1,
                'code'       => 'abc123',
                'long_url'   => 'https://google.com',
                'created_at' => time(),
                'expires_at' => null, 
            ]];
        $row = $testData; // TODO replace the test data with db data.
        if ($row === null) {
            $this->render404();
            return;
        }

        if ($row['expires_at'] !== null && (int) $row['expires_at'] <= time()) {
            $this->render404('This link has expired.');
            return;
        }

        // 302 not 301 — links can expire so  don't want browsers caching the redirect
        header('Location: ' . $row['long_url'], true, 302);
        exit;
    }

    private function render404(string $reason = 'The short URL you requested could not be found.'): void
    {
        http_response_code(404);
        // TODO need to render a 404 page
    }

    // Reads the timestamp as unix
    private function parseExpiry(): ?int
    {
        $ts = trim($this->request->post('expires_ts'));

        if ($ts === '' || $ts === '0') {
            return null;
        }

        if (!ctype_digit($ts)) {
            throw new InvalidArgumentException('The expiry date/time you entered was not recognised.');
        }

        $timestamp = (int) $ts;

        if ($timestamp <= time()) {
            throw new InvalidArgumentException('The expiry date must be in the future.');
        }

        return $timestamp;
    }

    private function resolveLocalPath(string $requestPath): string
    {
        $normalBase = '/' . trim($this->basePath, '/');

        if ($normalBase !== '/' && str_starts_with($requestPath, $normalBase)) {
            $requestPath = substr($requestPath, strlen($normalBase)) ?: '/';
        }

        return '/' . ltrim($requestPath, '/');
    }

}