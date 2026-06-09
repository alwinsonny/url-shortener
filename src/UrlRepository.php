<?php

declare(strict_types=1);

namespace UrlShortener;

use PDO;
use RuntimeException;

final class UrlRepository
{
    private const ALPHABET        = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private const CODE_LENGTH     = 6;
    private const MAX_ATTEMPTS    = 10;

    public function __construct(private readonly PDO $db) {}

    public function store(string $longUrl, ?int $expiresAt): string
    {
        $code = $this->generateCode();

        $stmt = $this->db->prepare(
            'INSERT INTO urls (code, long_url, created_at, expires_at)
             VALUES (:code, :long_url, :created_at, :expires_at)'
        );

        $stmt->execute([
            ':code'       => $code,
            ':long_url'   => $longUrl,
            ':created_at' => time(),
            ':expires_at' => $expiresAt,
        ]);

        return $code;
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, code, long_url, created_at, expires_at
               FROM urls
              WHERE code = :code'
        );
        $stmt->execute([':code' => $code]);

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    // Generates a unique code
    private function generateCode(): string
    {
        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $code = $this->randomCode();

            if (!$this->codeExists($code)) {
                return $code;
            }
        }

        throw new RuntimeException('Could not generate a unique short code.');
    }

    // Uses random_int()
    private function randomCode(): string
    {
        $max  = strlen(self::ALPHABET) - 1;
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, $max)];
        }

        return $code;
    }

    private function codeExists(string $code): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM urls WHERE code = :code LIMIT 1');
        $stmt->execute([':code' => $code]);

        return $stmt->fetchColumn() !== false;
    }
}
