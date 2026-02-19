<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Файловый логгер с уровнями.
 * Поддерживает Belief Log формат для отладки.
 */
final class Logger
{
    private const LEVEL_DEBUG = 0;
    private const LEVEL_INFO = 1;
    private const LEVEL_WARNING = 2;
    private const LEVEL_ERROR = 3;

    private static ?self $instance = null;
    private string $logFile;
    private int $minLevel;

    private function __construct(string $logFile, int $minLevel = self::LEVEL_DEBUG)
    {
        $this->logFile = $logFile;
        $this->minLevel = $minLevel;
        $this->ensureLogDirectory();
    }

    public static function getInstance(): self
    {
        // START_CONTRACT_getInstance
        // Intent: Singleton для единой точки логирования
        // Input: None
        // Output: Logger instance
        // END_CONTRACT_getInstance
        if (self::$instance === null) {
            $logDir = dirname(__DIR__, 2) . '/logs';
            self::$instance = new self($logDir . '/app.log');
        }
        return self::$instance;
    }

    public function debug(string $message, array $context = []): void
    {
        // START_CONTRACT_debug
        // Intent: Записать отладочное сообщение (Belief Log формат)
        // Input: message, context
        // Output: void (запись в файл)
        // END_CONTRACT_debug
        $this->log(self::LEVEL_DEBUG, 'DEBUG', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_INFO, 'INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_WARNING, 'WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ERROR, 'ERROR', $message, $context);
    }

    private function log(int $level, string $levelName, string $message, array $context): void
    {
        if ($level < $this->minLevel) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = $context !== [] ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = "[{$timestamp}] [{$levelName}] {$message}{$contextStr}\n";

        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }

    private function ensureLogDirectory(): void
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
