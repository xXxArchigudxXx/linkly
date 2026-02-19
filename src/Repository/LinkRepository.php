<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Link;
use App\Utils\Logger;
use PDO;

/**
 * CRUD operations for link entities.
 * Handles short code uniqueness checks and expiration queries.
 */
final class LinkRepository
{
    private PDO $pdo;
    private Logger $logger;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
        $this->logger = Logger::getInstance();
    }

    public function findById(int $id): ?Link
    {
        // START_CONTRACT_findById
        // Intent: Find link by ID
        // Input: id (int)
        // Output: Link|null - link entity or null
        // END_CONTRACT_findById
        $this->logger->debug('[LinkRepository][findById] Belief: Find link by ID | Input: id=' . $id . ' | Expected: Link or null');

        $stmt = $this->pdo->prepare('SELECT * FROM links WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        return $data ? Link::fromArray($data) : null;
    }

    public function findByShortCode(string $code): ?Link
    {
        // START_CONTRACT_findByShortCode
        // Intent: Find active link by short code
        // Input: code (string)
        // Output: Link|null - active link entity or null
        // END_CONTRACT_findByShortCode
        $this->logger->debug('[LinkRepository][findByShortCode] Belief: Find link by code | Input: code=' . $code . ' | Expected: Link or null');

        $stmt = $this->pdo->prepare(
            'SELECT * FROM links WHERE short_code = :code AND is_active = 1'
        );
        $stmt->execute(['code' => $code]);
        $data = $stmt->fetch();

        return $data ? Link::fromArray($data) : null;
    }

    public function findByUserId(int $userId, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM links WHERE user_id = :user_id 
             ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn($row) => Link::fromArray($row), $stmt->fetchAll());
    }

    public function create(?int $userId, string $shortCode, string $originalUrl, ?string $expiresAt = null): Link
    {
        // START_CONTRACT_create
        // Intent: Create new short link
        // Input: userId, shortCode, originalUrl, expiresAt
        // Output: Link - created link entity
        // END_CONTRACT_create
        $this->logger->debug('[LinkRepository][create] Belief: Create link | Input: code=' . $shortCode . ' | Expected: Link entity');

        $stmt = $this->pdo->prepare(
            'INSERT INTO links (user_id, short_code, original_url, expires_at, is_active, created_at) 
             VALUES (:user_id, :short_code, :original_url, :expires_at, 1, NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'short_code' => $shortCode,
            'original_url' => $originalUrl,
            'expires_at' => $expiresAt,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->findById($id);
    }

    public function deactivate(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE links SET is_active = 0 WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM links WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function countByUserId(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM links WHERE user_id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function shortCodeExists(string $code): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM links WHERE short_code = :code'
        );
        $stmt->execute(['code' => $code]);
        return ((int) $stmt->fetchColumn()) > 0;
    }
}