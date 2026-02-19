<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\Config;
use App\Repository\RedisClient;
use App\Utils\Logger;

/**
 * Implements sliding window rate limiting using Redis.
 * Falls back to no-limit when Redis unavailable.
 */
final class RateLimiter
{
    private Config $config;
    private Logger $logger;

    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->logger = Logger::getInstance();
    }

    public function checkLimit(string $identifier, ?int $maxRequests = null, ?int $windowSeconds = null): bool
    {
        // START_CONTRACT_checkLimit
        // Intent: Check if request is within rate limit
        // Input: identifier, maxRequests, windowSeconds
        // Output: bool - true if allowed, false if limit exceeded
        // END_CONTRACT_checkLimit
        $this->logger->debug('[RateLimiter][checkLimit] Belief: Check rate limit | Input: id=' . $identifier . ' | Expected: bool');

        // Fallback: no limit when Redis unavailable
        if (!RedisClient::isAvailable()) {
            return true;
        }

        $maxRequests = $maxRequests ?? $this->config->getInt('RATE_LIMIT_REQUESTS', 100);
        $windowSeconds = $windowSeconds ?? $this->config->getInt('RATE_LIMIT_WINDOW', 60);

        $key = "rate_limit:{$identifier}";

        // STEP 1: Get current count from Redis
        $current = RedisClient::get($key);
        $count = $current !== null ? (int) $current : 0;

        // STEP 2: IF count >= maxRequests AND key not expired: RETURN false
        if ($count >= $maxRequests) {
            $ttl = RedisClient::ttl($key);
            if ($ttl > 0) {
                return false;
            }
            // Key expired, reset
            RedisClient::del($key);
        }

        // STEP 3: INCR counter
        $newCount = RedisClient::incr($key);

        // STEP 4: IF new key, SET expiry to windowSeconds
        if ($newCount === 1) {
            RedisClient::expire($key, $windowSeconds);
        }

        // STEP 5: RETURN true (allowed)
        return true;
    }

    public function getRemainingRequests(string $identifier, ?int $maxRequests = null): int
    {
        if (!RedisClient::isAvailable()) {
            return PHP_INT_MAX;
        }

        $maxRequests = $maxRequests ?? $this->config->getInt('RATE_LIMIT_REQUESTS', 100);
        $key = "rate_limit:{$identifier}";

        $current = RedisClient::get($key);
        $count = $current !== null ? (int) $current : 0;

        return max(0, $maxRequests - $count);
    }

    public function getRetryAfter(string $identifier): int
    {
        if (!RedisClient::isAvailable()) {
            return 0;
        }

        $key = "rate_limit:{$identifier}";
        return max(0, RedisClient::ttl($key));
    }

    public function reset(string $identifier): void
    {
        $key = "rate_limit:{$identifier}";
        RedisClient::del($key);
    }
}