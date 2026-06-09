<?php

declare(strict_types=1);

namespace UrlShortener;

use InvalidArgumentException;

final class UrlValidator
{
    private const MAX_LENGTH      = 2048;
    private const ALLOWED_SCHEMES = ['http', 'https'];

    public function validate(string $url): string
    {
        if ($url === '') {
            throw new InvalidArgumentException('Please enter a URL.');
        }

        if (strlen($url) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('URL must not exceed %d characters.', self::MAX_LENGTH)
            );
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException(
                'The URL you entered is not valid. Please include the full address (e.g. https://example.com).'
            );
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (!in_array($scheme, self::ALLOWED_SCHEMES, true)) {
            throw new InvalidArgumentException('Only http:// and https:// URLs are supported.');
        }

        return $url;
    }
}
