<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\Config;

/**
 * Handles CORS headers for API requests.
 */
final class CorsMiddleware
{
    public function handle(array $serverData, callable $next): void
    {
        // START_CONTRACT_handle
        // Intent: Add CORS headers and handle OPTIONS preflight requests
        // Input: serverData (HTTP headers), next (callable)
        // Output: void (adds headers); exits with 200 for OPTIONS preflight
        // END_CONTRACT_handle
        $config = Config::getInstance();
        $allowedOrigins = $config->get('CORS_ORIGINS', '*');
        $allowedMethods = 'GET, POST, DELETE, OPTIONS';
        $allowedHeaders = 'Content-Type, Authorization, X-CSRF-Token';

        // Handle preflight
        $origin = $serverData['HTTP_ORIGIN'] ?? '*';
        if ($allowedOrigins !== '*') {
            $origins = explode(',', $allowedOrigins);
            if (in_array($origin, $origins, true)) {
                header("Access-Control-Allow-Origin: {$origin}");
            }
        } else {
            header('Access-Control-Allow-Origin: *');
        }

        header("Access-Control-Allow-Methods: {$allowedMethods}");
        header("Access-Control-Allow-Headers: {$allowedHeaders}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');

        // Handle OPTIONS preflight request
        if (($serverData['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(200);
            exit; // Critical: stop execution after preflight response
        }

        $next();
    }
}