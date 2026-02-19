<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Config;
use App\Service\AuthService;
use App\Service\RateLimiter;
use App\Utils\IpExtractor;
use App\Utils\JsonRequestParser;
use App\Utils\ResponseHelper;

/**
 * Handles user authentication operations.
 */
final class AuthController
{
    use JsonRequestParser;

    private AuthService $authService;
    private RateLimiter $rateLimiter;
    private Config $config;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->rateLimiter = new RateLimiter();
        $this->config = Config::getInstance();
    }

    public function register(array $params, array $serverData): void
    {
        // START_CONTRACT_register
        // Intent: Register new user
        // Input: params (route params), serverData ($_SERVER, php://input)
        // Output: JSON response with user data
        // END_CONTRACT_register
        $ip = IpExtractor::fromServerData($serverData);
        $maxRequests = $this->config->getInt('RATE_LIMIT_REGISTER_MAX', 5);
        $window = $this->config->getInt('RATE_LIMIT_REGISTER_WINDOW', 300);
        
        if (!$this->rateLimiter->checkLimit($ip . ':register', $maxRequests, $window)) {
            ResponseHelper::tooManyRequests($window);
            return;
        }

        $input = $this->parseJsonInput();
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            ResponseHelper::error('Email and password are required', 422);
            return;
        }

        try {
            $user = $this->authService->register($email, $password);
            ResponseHelper::success([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ]);
        } catch (\Exception $e) {
            ResponseHelper::error($e->getMessage(), 400);
        }
    }

    public function login(array $params, array $serverData): void
    {
        // START_CONTRACT_login
        // Intent: Authenticate user and create session
        // Input: params (route params), serverData ($_SERVER, php://input)
        // Output: JSON response with success status
        // END_CONTRACT_login
        $ip = IpExtractor::fromServerData($serverData);
        $maxRequests = $this->config->getInt('RATE_LIMIT_LOGIN_MAX', 10);
        $window = $this->config->getInt('RATE_LIMIT_LOGIN_WINDOW', 300);
        
        if (!$this->rateLimiter->checkLimit($ip . ':login', $maxRequests, $window)) {
            ResponseHelper::tooManyRequests($window);
            return;
        }

        $input = $this->parseJsonInput();
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            ResponseHelper::error('Email and password are required', 422);
            return;
        }

        if ($this->authService->login($email, $password)) {
            ResponseHelper::success(['message' => 'Login successful']);
        } else {
            ResponseHelper::error('Invalid credentials', 401);
        }
    }

    public function logout(array $params, array $serverData): void
    {
        // START_CONTRACT_logout
        // Intent: Destroy user session
        // Input: params (route params), serverData
        // Output: JSON response with success status
        // END_CONTRACT_logout
        $this->authService->logout();
        ResponseHelper::success(['message' => 'Logout successful']);
    }

    public function me(array $params, array $serverData): void
    {
        $user = $this->authService->getCurrentUser();
        if ($user === null) {
            ResponseHelper::unauthorized();
            return;
        }

        ResponseHelper::success([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
        ]);
    }
}