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
        private readonly UrlRepository $repository,
        private readonly UrlValidator  $validator,
        private readonly View          $view,
        private readonly string        $basePath,
        private readonly string        $baseUrl,
    ) {}

    public function dispatch(): void
    {
        $path = $this->resolveLocalPath($this->request->getPath());

        if ($path === '/') {
            $this->request->isPost() ? $this->handleCreate() : $this->handleForm();
            return;
        }

        $this->handleRedirect(ltrim($path, '/'));
    }

    private function handleForm(?string $error = null, ?string $shortUrl = null): void
    {
        $error    ??= $_SESSION['error']     ?? null;
        $shortUrl ??= $_SESSION['short_url'] ?? null;

        unset($_SESSION['error'], $_SESSION['short_url']);

        http_response_code(200);
        $this->view->renderForm($error, $shortUrl);
    }

    private function handleCreate(): void
    {
        $rawUrl = $this->request->post('url');

        try {
            $longUrl = $this->validator->validate($rawUrl);
        } catch (InvalidArgumentException $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirectToForm();
        }

        try {
            $expiresAt = $this->parseExpiry();
        } catch (InvalidArgumentException $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirectToForm();
        }

        try {
            $code                  = $this->repository->store($longUrl, $expiresAt);
            $_SESSION['short_url'] = $this->buildShortUrl($code);
            $this->redirectToForm();
        } catch (RuntimeException $e) {
            $_SESSION['error'] = 'Something went wrong. Please try again.';
            $this->redirectToForm();
        }
    }

    private function redirectToForm(): void
    {
        $url = rtrim($this->baseUrl, '/');

        if ($this->basePath !== '') {
            $url .= '/' . trim($this->basePath, '/');
        }

        header('Location: ' . $url, true, 302);
        exit;
    }

    private function handleRedirect(string $code): void
    {
        if (!preg_match(self::CODE_PATTERN, $code)) {
            $this->render404();
            return;
        }

        $row = $this->repository->findByCode($code);

        if ($row === null) {
            $this->render404();
            return;
        }

        if ($row['expires_at'] !== null && (int) $row['expires_at'] <= time()) {
            $this->render404('This link has expired.');
            return;
        }
        header('Location: ' . $row['long_url'], true, 302);
        exit;
    }

    private function render404(string $reason = 'The short URL you requested could not be found.'): void
    {
        http_response_code(404);
        $this->view->render404($reason);
    }

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

    private function buildShortUrl(string $code): string
    {
        $raw = rtrim($this->baseUrl, '/') . '/' . ltrim($this->basePath, '/') . '/' . $code;

        return (string) preg_replace('#(?<!:)/{2,}#', '/', $raw);
    }
}