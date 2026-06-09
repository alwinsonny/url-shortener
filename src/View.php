<?php

declare(strict_types=1);

namespace UrlShortener;

final class View
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $basePath,
    ) {}

    public function renderForm(?string $error, ?string $shortUrl): void
    {
        $errorHtml    = $error    ? $this->escape($error)    : null;
        $shortUrlHtml = $shortUrl ? $this->escape($shortUrl) : null;
        $actionUrl    = $this->escape($this->buildBaseHref());
        $jsUrl        = $this->escape($this->buildAssetPath('js/app.js'));

        echo $this->layout('URL Shortener', $jsUrl, <<<HTML
            <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4">
              <div class="w-full max-w-lg">

                <h1 class="text-2xl font-semibold text-slate-800 mb-6 text-center">URL Shortener</h1>

                {$this->errorBanner($errorHtml)}
                {$this->resultBanner($shortUrlHtml)}

                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                  <form id="shorten-form" method="POST" action="{$actionUrl}" novalidate>

                  <input type="hidden" id="expires_ts" name="expires_ts" value="" />

                    <div class="mb-4">
                      <label for="url" class="block text-sm font-medium text-slate-700 mb-1">
                        Destination URL
                      </label>
                      <input
                        id="url"
                        name="url"
                        type="url"
                        required
                        placeholder="https://example.com"
                        class="w-full border border-slate-300 rounded px-3 py-2 text-sm text-slate-800
                               placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500
                               focus:border-transparent"
                      />
                      <p id="url-error" class="mt-1 text-xs text-red-600 hidden">Please enter a valid URL.</p>
                    </div>

                    <div class="mb-5">
                      <div class="flex items-center gap-2 mb-2">
                        <input
                          type="checkbox"
                          id="expiry-toggle"
                          class="h-4 w-4 text-blue-600 border-slate-300 rounded"
                        />
                        <label for="expiry-toggle" class="text-sm text-slate-700 cursor-pointer">
                          Set an expiry date
                        </label>
                      </div>
                      <div id="expiry-field" class="hidden">
                        <input
                          id="expires_at"
                          name="expires_at"
                          type="datetime-local"
                          class="border border-slate-300 rounded px-3 py-2 text-sm text-slate-800
                                 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                      </div>
                    </div>

                    <button
                      type="submit"
                      id="submit-btn"
                      class="w-full bg-blue-600 text-white text-sm font-medium py-2 px-4 rounded
                             hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      Shorten URL
                    </button>

                  </form>
                </div>

              </div>
            </div>
        HTML);
    }

    public function render404(string $reason): void
    {
        $reasonHtml = $this->escape($reason);
        $homeUrl    = $this->escape($this->buildBaseHref());
        $jsUrl      = $this->escape($this->buildAssetPath('js/app.js'));

        echo $this->layout('404 – Not Found', $jsUrl, <<<HTML
            <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4">
              <div class="text-center">
                <h1 class="text-6xl font-bold text-slate-200 mb-4">404</h1>
                <p class="text-slate-600 mb-6">{$reasonHtml}</p>
                <a href="{$homeUrl}"
                   class="inline-block bg-blue-600 text-white text-sm font-medium py-2 px-4 rounded
                          hover:bg-blue-700 transition-colors">
                  Shorten a new URL
                </a>
              </div>
            </div>
        HTML);
    }

    private function layout(string $title, string $jsUrl, string $body): string
    {
        $titleHtml = $this->escape($title);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8" />
          <meta name="viewport" content="width=device-width, initial-scale=1.0" />
          <title>{$titleHtml}</title>
          <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body>
          {$body}
          <script src="{$jsUrl}" defer></script>
        </body>
        </html>
        HTML;
    }

    private function errorBanner(?string $message): string
    {
        if ($message === null) {
            return '';
        }

        return <<<HTML
        <div role="alert" class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded">
          {$message}
        </div>
        HTML;
    }

    private function resultBanner(?string $shortUrl): string
    {
        if ($shortUrl === null) {
            return '';
        }

        return <<<HTML
        <div class="mb-4 bg-green-50 border border-green-200 text-sm px-4 py-3 rounded flex items-center justify-between gap-4">
          <a href="{$shortUrl}" target="_blank" rel="noopener noreferrer"
             class="text-blue-600 hover:underline truncate">{$shortUrl}</a>
          <button id="copy-btn" type="button" data-url="{$shortUrl}"
                  class="flex-shrink-0 text-xs bg-white border border-slate-300 rounded px-2 py-1
                         hover:bg-slate-50 text-slate-600">
            Copy
          </button>
        </div>
        HTML;
    }

    private function buildBaseHref(): string
    {
        return $this->normalisePath(
            rtrim($this->baseUrl, '/') . '/' . ltrim($this->basePath, '/')
        );
    }

    private function buildAssetPath(string $asset): string
    {
        return $this->normalisePath(
            '/' . ltrim($this->basePath, '/') . '/' . ltrim($asset, '/')
        );
    }

    private function normalisePath(string $path): string
    {
        return (string) preg_replace('#(?<!:)/{2,}#', '/', $path);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}