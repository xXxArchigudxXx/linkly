<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\AuthService;
use App\Utils\ResponseHelper;

/**
 * Validates user authentication for protected routes.
 */
final class AuthMiddleware
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function handle(array $serverData, callable $next): void
    {
        // START_CONTRACT_handle
        // Intent: Check if user is authenticated, return 401 if not
        // Input: serverData, next (callable)
        // Output: void (calls next or returns 401)
        // END_CONTRACT_handle
        if (!$this->authService->isLoggedIn()) {
            ResponseHelper::unauthorized('Authentication required');
            return;
        }

        $next();
    }
}