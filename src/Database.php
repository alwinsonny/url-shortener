<?php

declare(strict_types=1);

namespace UrlShortener;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Manages the SQLite database connection and schema initialisation
 */
final class Database
{
    private readonly PDO $pdo;

    /**
     * @throws RuntimeException if the storage directory or database cannot be opened.
     */
    public function __construct(private readonly string $dbPath)
    {
        $this->ensureDirectory(dirname($this->dbPath));

        try {
            $this->pdo = new PDO(
                'sqlite:' . $this->dbPath,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );

            $this->pdo->exec('PRAGMA journal_mode = WAL;');
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
            $this->applySchema();
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Failed to open database: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    private function applySchema(): void
    {
        $this->pdo->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS urls (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                code       TEXT    NOT NULL UNIQUE,
                long_url   TEXT    NOT NULL,
                created_at INTEGER NOT NULL,
                expires_at INTEGER DEFAULT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_urls_code ON urls (code);
            SQL
        );
    }

    /**
     * @throws RuntimeException if the directory cannot be created.
     */
    private function ensureDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        if (!mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create storage directory: ' . $dir);
        }
    }
}
