<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\User;
use App\Utils\Logger;
use PDO;

/**
 * CRUD operations for user entities.
 * Handles password hashing and user authentication queries.
 */
final class UserRepository
{
    private PDO $pdo;
    private Logger $logger;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
        $this->logger = Logger::getInstance();
    }

    public function findById(int $id): ?User
    {
        // START_CONTRACT_findById
        // Intent: Find user by ID
        // Input: id (int)
        // Output: User|null - user entity or null if not found
        // END_CONTRACT_findById
        $this->logger->debug('[UserRepository][findById] Belief: Find user by ID | Input: id=' . $id . ' | Expected: User or null');

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        return $data ? User::fromArray($data) : null;
    }

    public function findByEmail(string $email): ?User
    {
        // START_CONTRACT_findByEmail
        // Intent: Find user by email address
        // Input: email (string)
        // Output: User|null - user entity or null if not found
        // END_CONTRACT_findByEmail
        $this->logger->debug('[UserRepository][findByEmail] Belief: Find user by email | Input: email=' . $email . ' | Expected: User or null');

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch();

        return $data ? User::fromArray($data) : null;
    }

    public function create(string $email, string $passwordHash): User
    {
        // START_CONTRACT_create
        // Intent: Create new user with hashed password
        // Input: email, passwordHash
        // Output: User - created user entity
        // END_CONTRACT_create
        $this->logger->debug('[UserRepository][create] Belief: Create new user | Input: email=' . $email . ' | Expected: User entity');

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, created_at, updated_at) 
             VALUES (:email, :password_hash, NOW(), NOW())'
        );
        $stmt->execute([
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->findById($id);
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function getPasswordHash(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result['password_hash'] ?? null;
    }
}