<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\ResponseHelper;

/**
 * Validates CSRF tokens for state-changing operations.
 */
final class CsrfMiddleware
{
    public function handle(array $serverData, callable $next): void
    {
        // START_CONTRACT_handle
        // Intent: Validate CSRF token for POST/DELETE requests
        // Input: serverData, next (callable)
        // Output: void (calls next or returns 403)
        // END_CONTRACT_handle
        $method = $serverData['REQUEST_METHOD'] ?? 'GET';

        // Only check state-changing methods
        if (!in_array($method, ['POST', 'DELETE', 'PUT', 'PATCH'], true)) {
            $next();
            return;
        }

        // Get token from header
        $token = $serverData['HTTP_X_CSRF_TOKEN'] ?? null;
        $sessionToken = $_SESSION['csrf_token'] ?? null;

        if ($token === null || $sessionToken === null) {
            ResponseHelper::error('CSRF token missing', 403);
            return;
        }

        if (!hash_equals($sessionToken, $token)) {
            ResponseHelper::error('Invalid CSRF token', 403);
            return;
        }

        $next();
    }

    public function generateToken(): string
    {
        // START_CONTRACT_generateToken
        // Intent: Generate new CSRF token and store in session
        // Input: None
        // Output: string - CSRF token
        // END_CONTRACT_generateToken
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    public function getToken(): ?string
    {
        return $_SESSION['csrf_token'] ?? null;
    }
}