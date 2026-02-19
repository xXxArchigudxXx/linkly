<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\Config;
use App\Utils\Logger;
use Predis\Client;

/**
 * Redis client wrapper for caching and rate limiting.
 * Handles connection failures gracefully.
 */
final class RedisClient
{
    private static ?Client $client = null;
    private static bool $available = false;
    private static ?Logger $logger = null;

    public static function getClient(): ?Client
    {
        // START_CONTRACT_getClient
        // Intent: Get Redis client instance (null if unavailable)
        // Input: None
        // Output: Client|null - Predis client or null
        // END_CONTRACT_getClient
        if (self::$client === null) {
            self::connect();
        }
        return self::$client;
    }

    public static function get(string $key): ?string
    {
        // START_CONTRACT_get
        // Intent: Get value from Redis
        // Input: key (string)
        // Output: string|null - value or null if not found/Redis unavailable
        // END_CONTRACT_get
        if (!self::isAvailable()) {
            return null;
        }
        try {
            $value = self::$client->get($key);
            return $value !== null ? (string) $value : null;
        } catch (\Exception $e) {
            self::logError('get', $e->getMessage());
            return null;
        }
    }

    public static function set(string $key, string $value, ?int $ttl = null): bool
    {
        // START_CONTRACT_set
        // Intent: Set value in Redis with optional TTL
        // Input: key, value, ttl (optional)
        // Output: bool - success status
        // END_CONTRACT_set
        if (!self::isAvailable()) {
            return false;
        }
        try {
            if ($ttl !== null) {
                self::$client->setex($key, $ttl, $value);
            } else {
                self::$client->set($key, $value);
            }
            return true;
        } catch (\Exception $e) {
            self::logError('set', $e->getMessage());
            return false;
        }
    }

    public static function incr(string $key): int
    {
        // START_CONTRACT_incr
        // Intent: Increment counter in Redis
        // Input: key (string)
        // Output: int - new value (0 if Redis unavailable)
        // END_CONTRACT_incr
        if (!self::isAvailable()) {
            return 0;
        }
        try {
            return (int) self::$client->incr($key);
        } catch (\Exception $e) {
            self::logError('incr', $e->getMessage());
            return 0;
        }
    }

    public static function expire(string $key, int $ttl): bool
    {
        if (!self::isAvailable()) {
            return false;
        }
        try {
            self::$client->expire($key, $ttl);
            return true;
        } catch (\Exception $e) {
            self::logError('expire', $e->getMessage());
            return false;
        }
    }

    public static function ttl(string $key): int
    {
        if (!self::isAvailable()) {
            return -1;
        }
        try {
            return (int) self::$client->ttl($key);
        } catch (\Exception $e) {
            return -1;
        }
    }

    public static function del(string $key): bool
    {
        if (!self::isAvailable()) {
            return false;
        }
        try {
            self::$client->del([$key]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function isAvailable(): bool
    {
        // START_CONTRACT_isAvailable
        // Intent: Check if Redis connection is available
        // Input: None
        // Output: bool - true if Redis is connected
        // END_CONTRACT_isAvailable
        if (self::$client === null) {
            self::connect();
        }
        return self::$available;
    }

    private static function connect(): void
    {
        $config = Config::getInstance();
        $logger = self::getLogger();

        $host = $config->get('REDIS_HOST', 'redis');
        $port = $config->getInt('REDIS_PORT', 6379);
        $password = $config->get('REDIS_PASSWORD', null);

        try {
            $parameters = [
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
            ];
            if ($password !== null && $password !== '') {
                $parameters['password'] = $password;
            }

            self::$client = new Client($parameters);
            // Test connection
            self::$client->ping();
            self::$available = true;
            $logger->debug('[RedisClient][connect] Belief: Redis connected | Input: host=' . $host . ' | Expected: Connection OK');
        } catch (\Exception $e) {
            self::$available = false;
            $logger->warning('[RedisClient][connect] Redis unavailable: ' . $e->getMessage());
        }
    }

    private static function logError(string $operation, string $message): void
    {
        self::getLogger()->warning("[RedisClient][{$operation}] Error: {$message}");
    }

    private static function getLogger(): Logger
    {
        if (self::$logger === null) {
            self::$logger = Logger::getInstance();
        }
        return self::$logger;
    }
}