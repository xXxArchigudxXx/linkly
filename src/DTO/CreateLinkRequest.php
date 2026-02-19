<?php

declare(strict_types=1);

namespace App\DTO;

use App\Config\Config;
use App\Utils\UrlValidator;

/**
 * DTO dla requestu utworzenia krótkiego linku.
 * Zawiera walidacj danych wejciowych.
 */
final class CreateLinkRequest
{
    private array $errors = [];
    private Config $config;

    public function __construct(
        private readonly string $url,
        private readonly ?string $customAlias = null,
        private readonly ?int $ttl = null
    ) {
        $this->config = Config::getInstance();
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getCustomAlias(): ?string
    {
        return $this->customAlias;
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    public function validate(): array
    {
        // START_CONTRACT_validate
        // Intent: Walidowa dane wejciowe
        // Input: None (uywa wewntrznych pl)
        // Output: array - lista bdw (pusty jeli walidne)
        // END_CONTRACT_validate
        $this->errors = [];

        // Walidacja URL (najpierw sprawdzenie bezpieczestwa!)
        if (empty($this->url)) {
            $this->errors['url'] = 'URL is required';
        } elseif (!UrlValidator::isSafe($this->url)) {
            $this->errors['url'] = 'URL scheme not allowed';
        } elseif (!UrlValidator::isValid($this->url)) {
            $this->errors['url'] = 'Invalid URL format';
        }

        // Walidacja custom alias
        if ($this->customAlias !== null) {
            if (strlen($this->customAlias) < 3 || strlen($this->customAlias) > 20) {
                $this->errors['custom_alias'] = 'Alias must be 3-20 characters';
            } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $this->customAlias)) {
                $this->errors['custom_alias'] = 'Alias can only contain letters, numbers, underscore and hyphen';
            }
        }

        // Walidacja TTL (z konfiguracji)
        $ttlMin = $this->config->getInt('LINK_TTL_MIN', 60);
        $ttlMax = $this->config->getInt('LINK_TTL_MAX', 31536000);
        if ($this->ttl !== null && ($this->ttl < $ttlMin || $this->ttl > $ttlMax)) {
            $this->errors['ttl'] = "TTL must be between {$ttlMin} seconds and 1 year";
        }

        return $this->errors;
    }

    public function isValid(): bool
    {
        return $this->validate() === [];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: $data['url'] ?? '',
            customAlias: $data['custom_alias'] ?? null,
            ttl: isset($data['ttl']) ? (int) $data['ttl'] : null
        );
    }
}
