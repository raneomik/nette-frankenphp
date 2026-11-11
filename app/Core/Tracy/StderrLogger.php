<?php

declare(strict_types=1);

namespace App\Core\Tracy;

use Tracy;

/**
 * Keeps Tracy's files (var/log, bluescreens) and copies each entry as one JSON line to stderr:
 * so it reaches the container logs (Dozzle, later Loki), in the Caddy log format.
 */
final readonly class StderrLogger implements Tracy\ILogger
{
    // Tracy levels → levels recognised by Dozzle / Loki
    private const array Levels = [
        self::WARNING => 'warn',
        self::EXCEPTION => 'error',
        self::CRITICAL => 'fatal',
    ];

    public function __construct(
        private Tracy\ILogger $inner,
    ) {
    }

    public function log(mixed $value, string $level = self::INFO): mixed
    {
        $bluescreen = $this->inner->log($value, $level); // path of the .html for an exception

        file_put_contents('php://stderr', json_encode([
            'level' => self::Levels[$level] ?? $level,
            'ts' => microtime(true),
            'logger' => 'php.tracy',
            'msg' => Tracy\Logger::formatMessage($value),
            'exception' => $value instanceof \Throwable ? $value::class : null,
            'file' => $value instanceof \Throwable ? $value->getFile() . ':' . $value->getLine() : null,
            'bluescreen' => is_string($bluescreen) ? basename($bluescreen) : null,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . PHP_EOL);

        return $bluescreen;
    }
}
