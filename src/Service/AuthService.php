<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\Config;
use App\Model\User;
use App\Repository\UserRepository;
use App\Utils\Logger;
use RuntimeException;

/**
 * Handles user registration, login, logout.
 * Manages PHP sessions securely.
 */
final class AuthService
{
    private UserRepository $userRepository;
    private Logger $logger;
    private Config $config;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->logger = Logger::getInstance();
        $this->config = Config::getInstance();
    }

    public function register(string $email, string $password): User
    {
        // START_CONTRACT_register
        // Intent: Register new user with hashed password
        // Input: email, password
        // Output: User - created user entity
        // END_CONTRACT_register
        $this->logger->debug('[AuthService][register] Belief: Register user | Input: email=' . $email . ' | Expected: User entity');

        // STEP 1: Validate email format and password strength
        $this->validateEmail($email);
        $this->validatePassword($password);

        // STEP 2: Check if email already exists
        if ($this->userRepository->emailExists($email)) {
            throw new RuntimeException('Email already registered');
        }

        // STEP 3: Hash password with PASSWORD_BCRYPT
        $passwordHash = $this->hashPassword($password);

        // STEP 4: Create user record
        $user = $this->userRepository->create($email, $passwordHash);

        // STEP 5: Return User entity
        return $user;
    }

    public function login(string $email, string $password): bool
    {
        // START_CONTRACT_login
        // Intent: Authenticate user and create session
        // Input: email, password
        // Output: bool - true if login successful
        // END_CONTRACT_login
        $this->logger->debug('[AuthService][login] Belief: Login user | Input: email=' . $email . ' | Expected: bool');

        $user = $this->userRepository->findByEmail($email);
        if ($user === null) {
            return false;
        }

        $storedHash = $this->userRepository->getPasswordHash($user->getId());
        if ($storedHash === null) {
            return false;
        }

        if (!$this->verifyPassword($password, $storedHash)) {
            return false;
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_email'] = $user->getEmail();

        $this->userRepository->updateLastLogin($user->getId());

        return true;
    }

    public function logout(): void
    {
        // START_CONTRACT_logout
        // Intent: Destroy user session
        // Input: None
        // Output: void
        // END_CONTRACT_logout
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }

    public function getCurrentUser(): ?User
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return $this->userRepository->findById((int) $_SESSION['user_id']);
    }

    public function getCurrentUserId(): ?int
    {
        return $this->isLoggedIn() ? (int) $_SESSION['user_id'] : null;
    }

    public function hashPassword(string $password): string
    {
        $cost = $this->config->getInt('BCRYPT_COST', 12);
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email format');
        }
    }

    private function validatePassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new RuntimeException('Password must be at least 8 characters');
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new RuntimeException('Password must contain at least one uppercase letter');
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new RuntimeException('Password must contain at least one lowercase letter');
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new RuntimeException('Password must contain at least one number');
        }
    }
}