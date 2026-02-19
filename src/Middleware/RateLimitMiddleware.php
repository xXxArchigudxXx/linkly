<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\RateLimiter;
use App\Utils\IpExtractor;
use App\Utils\ResponseHelper;

/**
 * Enforces rate limiting on API endpoints.
 */
final class RateLimitMiddleware
{
    private RateLimiter $rateLimiter;

    public function __construct()
    {
        $this->rateLimiter = new RateLimiter();
    }

    public function handle(array $serverData, callable $next, ?int $maxRequests = null, ?int $windowSeconds = null): void
    {
        // START_CONTRACT_handle
        // Intent: Check rate limit, return 429 if exceeded
        // Input: serverData, next, maxRequests, windowSeconds
        // Output: void (calls next or returns 429)
        // END_CONTRACT_handle
        $ip = IpExtractor::fromServerData($serverData);

        if (!$this->rateLimiter->checkLimit($ip, $maxRequests, $windowSeconds)) {
            $retryAfter = $this->rateLimiter->getRetryAfter($ip);
            ResponseHelper::tooManyRequests($retryAfter);
            return;
        }

        // Add rate limit headers
        $remaining = $this->rateLimiter->getRemainingRequests($ip, $maxRequests);
        header("X-RateLimit-Remaining: {$remaining}");

        $next();
    }
}