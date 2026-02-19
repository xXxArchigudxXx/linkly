<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;

/**
 * Сущность пользователя.
 * Неизменяемая после создания (кроме временных меток).
 */
final class User
{
    public function __construct(
        private readonly int $id,
        private readonly string $email,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt
    ) {
    }

    public function getId(): int
    {
        // START_CONTRACT_getId
        // Intent: Получить ID пользователя
        // Input: None
        // Output: int - уникальный идентификатор
        // END_CONTRACT_getId
        return $this->id;
    }

    public function getEmail(): string
    {
        // START_CONTRACT_getEmail
        // Intent: Получить email пользователя
        // Input: None
        // Output: string - email адрес
        // END_CONTRACT_getEmail
        return $this->email;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            email: $data['email'],
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at'])
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
