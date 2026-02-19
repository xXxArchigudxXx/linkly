<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;

/**
 * Сущность короткой ссылки.
 * Отслеживает активность и истечение срока.
 */
final class Link
{
    public function __construct(
        private readonly int $id,
        private readonly ?int $userId,
        private readonly string $shortCode,
        private readonly string $originalUrl,
        private readonly bool $isActive,
        private readonly ?DateTimeImmutable $expiresAt,
        private readonly DateTimeImmutable $createdAt
    ) {
    }

    public function getId(): int
    {
        // START_CONTRACT_getId
        // Intent: Получить ID ссылки
        // Input: None
        // Output: int - уникальный идентификатор
        // END_CONTRACT_getId
        return $this->id;
    }

    public function getUserId(): ?int
    {
        // START_CONTRACT_getUserId
        // Intent: Получить ID владельца (null для анонимных ссылок)
        // Input: None
        // Output: int|null - ID пользователя или null
        // END_CONTRACT_getUserId
        return $this->userId;
    }

    public function getShortCode(): string
    {
        return $this->shortCode;
    }

    public function getOriginalUrl(): string
    {
        return $this->originalUrl;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isExpired(): bool
    {
        // START_CONTRACT_isExpired
        // Intent: Проверить истёк ли срок действия ссылки
        // Input: None
        // Output: bool - true если срок истёк
        // END_CONTRACT_isExpired
        if ($this->expiresAt === null) {
            return false;
        }
        return $this->expiresAt < new DateTimeImmutable();
    }

    public function isValid(): bool
    {
        return $this->isActive && !$this->isExpired();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            shortCode: $data['short_code'],
            originalUrl: $data['original_url'],
            isActive: (bool) ($data['is_active'] ?? true),
            expiresAt: isset($data['expires_at']) ? new DateTimeImmutable($data['expires_at']) : null,
            createdAt: new DateTimeImmutable($data['created_at'])
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'short_code' => $this->shortCode,
            'original_url' => $this->originalUrl,
            'is_active' => $this->isActive,
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
