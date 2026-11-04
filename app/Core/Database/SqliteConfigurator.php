<?php

declare(strict_types=1);

namespace App\Core\Database;

use Nette\Database\Connection;

/**
 * Runs on every SQLite connection (Connection::$onConnect).
 *
 * With FrankenPHP several threads write to the same file at once: SQLite allows only one
 * writer, so a writer must wait for the lock instead of failing.
 */
final readonly class SqliteConfigurator
{
    public function __construct(
        private int $busyTimeoutMs = 5000,
    ) {
    }

    public function configure(Connection $connection): void
    {
        $pdo = $connection->getPdo();

        // wait for the write lock instead of failing at once with "database is locked";
        // set first: the PRAGMAs below may need the lock themselves
        $pdo->exec('PRAGMA busy_timeout = ' . $this->busyTimeoutMs);

        // a transaction takes the write lock at BEGIN, not at its first write: two
        // "read the game state, then write a move" transactions queue up instead of
        // reading the same state and failing at the write (SQLITE_BUSY, never retried)
        if (\defined('Pdo\Sqlite::ATTR_TRANSACTION_MODE')) { // PHP >= 8.5
            $pdo->setAttribute(\Pdo\Sqlite::ATTR_TRANSACTION_MODE, \Pdo\Sqlite::TRANSACTION_MODE_IMMEDIATE);
        }

        // readers no longer block the writer and vice versa (stored in the file, cheap to repeat)
        $pdo->exec('PRAGMA journal_mode = WAL');
        // safe with WAL (no corruption, at worst the last commits lost on a power cut), much faster than FULL
        $pdo->exec('PRAGMA synchronous = NORMAL');
        // off by default in SQLite, for each connection
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
}
