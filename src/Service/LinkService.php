<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\Config;
use App\DTO\LinkResponse;
use App\DTO\PaginatedResult;
use App\Model\Link;
use App\Repository\LinkRepository;
use App\Utils\Logger;
use App\Utils\ShortCodeGenerator;
use App\Utils\UrlValidator;
use DateTimeImmutable;
use RuntimeException;

/**
 * Creates short URLs, handles redirects, manages link lifecycle.
 */
final class LinkService
{
    private LinkRepository $linkRepository;
    private Config $config;
    private Logger $logger;

    public function __construct()
    {
        $this->linkRepository = new LinkRepository();
        $this->config = Config::getInstance();
        $this->logger = Logger::getInstance();
    }

    public function createShortUrl(?int $userId, string $originalUrl, ?string $customAlias = null, ?int $ttl = null): Link
    {
        // START_CONTRACT_createShortUrl
        // Intent: Create short URL with unique code
        // Input: userId, originalUrl, customAlias, ttl
        // Output: Link - created link entity
        // END_CONTRACT_createShortUrl
        $this->logger->debug('[LinkService][createShortUrl] Belief: Create short URL | Input: url=' . $originalUrl . ' | Expected: Link entity');

        // STEP 1: Validate URL format and safety
        if (!UrlValidator::isValid($originalUrl)) {
            throw new RuntimeException('Invalid URL format');
        }

        if (!UrlValidator::isSafe($originalUrl)) {
            throw new RuntimeException('URL scheme not allowed');
        }

        // STEP 2: Generate short code OR use custom alias
        $shortCode = $customAlias;
        if ($shortCode === null) {
            $shortCode = $this->generateUniqueShortCode();
        } else {
            // Validate custom alias
            if (!ShortCodeGenerator::isValidCode($shortCode)) {
                throw new RuntimeException('Invalid custom alias format');
            }
            if ($this->linkRepository->shortCodeExists($shortCode)) {
                throw new RuntimeException('Custom alias already in use');
            }
        }

        // STEP 3: Calculate expiration if TTL provided
        $expiresAt = null;
        if ($ttl !== null && $ttl > 0) {
            $expiresAt = (new DateTimeImmutable())->modify("+{$ttl} seconds")->format('Y-m-d H:i:s');
        }

        // STEP 4: Persist to database
        $link = $this->linkRepository->create($userId, $shortCode, $originalUrl, $expiresAt);

        // STEP 5: Return Link entity
        return $link;
    }

    public function getRedirectInfo(string $shortCode): ?Link
    {
        // START_CONTRACT_getRedirectInfo
        // Intent: Get link for redirect (must be valid and not expired)
        // Input: shortCode
        // Output: Link|null - link entity or null if invalid
        // END_CONTRACT_getRedirectInfo
        $this->logger->debug('[LinkService][getRedirectInfo] Belief: Get redirect info | Input: code=' . $shortCode . ' | Expected: Link or null');

        $link = $this->linkRepository->findByShortCode($shortCode);
        if ($link === null) {
            return null;
        }

        if ($link->isExpired()) {
            return null;
        }

        return $link;
    }

    public function getUserLinks(int $userId, int $page = 1, int $limit = 20): PaginatedResult
    {
        $offset = ($page - 1) * $limit;
        $links = $this->linkRepository->findByUserId($userId, $limit, $offset);
        $total = $this->linkRepository->countByUserId($userId);

        $baseUrl = $this->config->get('APP_URL', 'http://localhost:8080');
        $data = array_map(
            fn(Link $link) => LinkResponse::fromLink($link, $baseUrl)->toArray(),
            $links
        );

        return new PaginatedResult($data, $total, $page, $limit);
    }

    public function deleteLink(int $userId, int $linkId): bool
    {
        // START_CONTRACT_deleteLink
        // Intent: Delete link (only if owned by user)
        // Input: userId, linkId
        // Output: bool - true if deleted
        // END_CONTRACT_deleteLink
        $this->logger->debug('[LinkService][deleteLink] Belief: Delete link | Input: userId=' . $userId . ', linkId=' . $linkId . ' | Expected: bool');

        return $this->linkRepository->delete($linkId, $userId);
    }

    public function generateShortCode(int $length = 6): string
    {
        return ShortCodeGenerator::generate($length);
    }

    private function generateUniqueShortCode(int $maxAttempts = 3): string
    {
        $length = $this->config->getInt('SHORT_CODE_LENGTH', 6);

        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = ShortCodeGenerator::generate($length);
            if (!$this->linkRepository->shortCodeExists($code)) {
                return $code;
            }
        }

        // Fallback: increase length
        return ShortCodeGenerator::generate($length + 1);
    }
}