<?php

declare(strict_types=1);

namespace App\Config;

/**
 * Загружает переменные окружения из .env файла.
 * Предоставляет типизированный доступ к конфигурации.
 */
final class Config
{
    private static ?self $instance = null;
    private array $items = [];

    private function __construct()
    {
        $this->loadEnvFile();
        $this->loadFromEnvironment();
    }

    public static function getInstance(): self
    {
        // START_CONTRACT_getInstance
        // Intent: Singleton pattern для единой точки доступа к конфигурации
        // Input: None
        // Output: Config instance (всегда один и тот же)
        // END_CONTRACT_getInstance
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        // START_CONTRACT_get
        // Intent: Получить значение конфигурации с fallback
        // Input: key (string), default (mixed)
        // Output: Значение или default если ключ не найден
        // END_CONTRACT_get
        return $this->items[$key] ?? $default;
    }

    public function getRequired(string $key): mixed
    {
        // START_CONTRACT_getRequired
        // Intent: Получить обязательное значение конфигурации
        // Input: key (string)
        // Output: Значение или исключение если ключ не найден
        // END_CONTRACT_getRequired
        if (!isset($this->items[$key])) {
            throw new \RuntimeException("Required config key '{$key}' not found");
        }
        return $this->items[$key];
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);
        if (is_bool($value)) {
            return $value;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function loadEnvFile(): void
    {
        $envFile = dirname(__DIR__, 2) . '/.env';
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                // Убираем кавычки
                $value = trim($value, '"\'');
                $this->items[$key] = $value;
            }
        }
    }

    private function loadFromEnvironment(): void
    {
        // Переменные окружения имеют приоритет над .env файлом
        foreach ($_ENV as $key => $value) {
            $this->items[$key] = $value;
        }
        foreach ($_SERVER as $key => $value) {
            if (is_string($value)) {
                $this->items[$key] = $value;
            }
        }
    }
}
