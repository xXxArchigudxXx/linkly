<?php

declare(strict_types=1);

namespace App\DTO;

use App\Model\Link;

/**
 * DTO для ответа с информацией о короткой ссылке.
 */
final class LinkResponse
{
    public function __construct(
        private readonly string $shortCode,
        private readonly string $originalUrl,
        private readonly string $shortUrl,
        private readonly ?string $expiresAt = null
    ) {
    }

    public function getShortCode(): string
    {
        return $this->shortCode;
    }

    public function getOriginalUrl(): string
    {
        return $this->originalUrl;
    }

    public function getShortUrl(): string
    {
        return $this->shortUrl;
    }

    public function getExpiresAt(): ?string
    {
        return $this->expiresAt;
    }

    public function toArray(): array
    {
        return [
            'short_code' => $this->shortCode,
            'original_url' => $this->originalUrl,
            'short_url' => $this->shortUrl,
            'expires_at' => $this->expiresAt,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function fromLink(Link $link, string $baseUrl): self
    {
        return new self(
            shortCode: $link->getShortCode(),
            originalUrl: $link->getOriginalUrl(),
            shortUrl: rtrim($baseUrl, '/') . '/' . $link->getShortCode(),
            expiresAt: $link->getExpiresAt()?->format('Y-m-d H:i:s')
        );
    }
}
