<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * DTO для пагинированных результатов.
 */
final class PaginatedResult
{
    public function __construct(
        private readonly array $data,
        private readonly int $total,
        private readonly int $page,
        private readonly int $perPage
    ) {
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getTotalPages(): int
    {
        return (int) ceil($this->total / $this->perPage);
    }

    public function hasNextPage(): bool
    {
        return $this->page < $this->getTotalPages();
    }

    public function hasPrevPage(): bool
    {
        return $this->page > 1;
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'pagination' => [
                'total' => $this->total,
                'page' => $this->page,
                'per_page' => $this->perPage,
                'total_pages' => $this->getTotalPages(),
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
