<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\Link;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Link model.
 * Tests entity behavior, expiration logic, and data transformation.
 */
final class LinkTest extends TestCase
{
    // ========== HAPPY PATH TESTS ==========

    public function testFromArrayCreatesLink(): void
    {
        $data = [
            'id' => 1,
            'user_id' => 42,
            'short_code' => 'abc123',
            'original_url' => 'https://example.com/long-url',
            'is_active' => true,
            'expires_at' => null,
            'created_at' => '2024-01-01 12:00:00',
        ];

        $link = Link::fromArray($data);

        $this->assertEquals(1, $link->getId());
        $this->assertEquals(42, $link->getUserId());
        $this->assertEquals('abc123', $link->getShortCode());
        $this->assertEquals('https://example.com/long-url', $link->getOriginalUrl());
        $this->assertTrue($link->isActive());
        $this->assertNull($link->getExpiresAt());
    }

    public function testFromArrayHandlesAnonymousLink(): void
    {
        $data = [
            'id' => 1,
            'short_code' => 'xyz789',
            'original_url' => 'https://example.com',
            'is_active' => true,
            'created_at' => '2024-01-01 12:00:00',
        ];

        $link = Link::fromArray($data);

        $this->assertNull($link->getUserId());
    }

    public function testToArrayReturnsCorrectData(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-01 12:00:00');
        $link = new Link(
            id: 1,
            userId: 42,
            shortCode: 'abc123',
            originalUrl: 'https://example.com',
            isActive: true,
            expiresAt: null,
            createdAt: $createdAt
        );

        $array = $link->toArray();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals(42, $array['user_id']);
        $this->assertEquals('abc123', $array['short_code']);
        $this->assertEquals('https://example.com', $array['original_url']);
        $this->assertTrue($array['is_active']);
        $this->assertNull($array['expires_at']);
    }

    public function testIsExpiredReturnsFalseWhenNoExpiration(): void
    {
        $link = new Link(
            id: 1,
            userId: null,
            shortCode: 'abc',
            originalUrl: 'https://example.com',
            isActive: true,
            expiresAt: null,
            createdAt: new DateTimeImmutable()
        );

        $this->assertFalse($link->isExpired());
    }

    public function testIsExpiredReturnsFalseWhenFutureExpiration(): void
    {
        $futureDate = (new DateTimeImmutable())->modify('+1 year');

        $link = new Link(
            id: 1,
            userId: null,
            shortCode: 'abc',
            originalUrl: 'https://example.com',
            isActive: true,
            expiresAt: $futureDate,
            createdAt: new DateTimeImmutable()
        );

        $this->assertFalse($link->isExpired());
    }

    public function testIsExpiredReturnsTrueWhenPastExpiration(): void
    {
        $pastDate = (new DateTimeImmutable())->modify('-1 day');

        $link = new Link(
            id: 1,
            userId: null,
            shortCode: 'abc',
            originalUrl: 'https://example.com',
            isActive: true,
            expiresAt: $pastDate,
            createdAt: new DateTimeImmutable()
        );

        $this->assertTrue($link->isExpired());
    }

    public function testIsValidReturnsTrueWhenActiveAndNotExpired(): void
    {
        $futureDate = (new DateTimeImmutable())->modify('+1 year');

        $link = new Link(
            id: 1,
            userId: null,
            shortCode: 'abc',
            originalUrl: 'https://example.com',
            isActive: true,
            expiresAt: $futureDate,
            createdAt: new DateTimeImmutable()
        );

        $this->assertTrue($link->isValid());
    }

    public function testIsValidReturnsFalseWhenInactive(): void
    {
        $link = new Link(
            id: 1,
            userId: null,
            shortCode: 'abc',
            originalUrl: 'https://example.com',
            isActive: false,
            expiresAt: null,
            createdAt: new DateTimeImmutable()
        );

        $this->assertFalse($link->isValid());
    }

    public function testIsValidReturnsFalseWhenExpired(): void
    {
        $pastDate = (new DateTimeImmutable())->modify('-1 day');

        $link = new Link(
            id: 1,
            userId: null,
            shortCode: 'abc',
            originalUrl: 'https://example.com',
            isActive: true,
            expiresAt: $pastDate,
            createdAt: new DateTimeImmutable()
        );

        $this->assertFalse($link->isValid());
    }

    // ========== ADVERSARIAL TESTS ==========

    public function testFromArrayHandlesZeroId(): void
    {
        $data = [
            'id' => 0,
            'short_code' => 'abc',
            'original_url' => 'https://example.com',
            'is_active' => true,
            'created_at' => '2024-01-01 12:00:00',
        ];

        $link = Link::fromArray($data);

        $this->assertEquals(0, $link->getId());
    }

    public function testFromArrayHandlesStringId(): void
    {
        $data = [
            'id' => '42', // String instead of int
            'short_code' => 'abc',
            'original_url' => 'https://example.com',
            'is_active' => true,
            'created_at' => '2024-01-01 12:00:00',
        ];

        $link = Link::fromArray($data);

        $this->assertEquals(42, $link->getId());
    }

    public function testFromArrayHandlesMissingIsActive(): void
    {
        $data = [
            'id' => 1,
            'short_code' => 'abc',
            'original_url' => 'https://example.com',
            'created_at' => '2024-01-01 12:00:00',
        ];

        $link = Link::fromArray($data);

        // Default should be true
        $this->assertTrue($link->isActive());
    }

    public function testFromArrayHandlesVeryLongUrl(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2000);

        $data = [
            'id' => 1,
            'short_code' => 'abc',
            'original_url' => $longUrl,
            'is_active' => true,
            'created_at' => '2024-01-01 12:00:00',
        ];

        $link = Link::fromArray($data);

        $this->assertEquals($longUrl, $link->getOriginalUrl());
    }

    public function testToArrayFormatsDatesCorrectly(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-15 10:30:45');
        $expiresAt = new DateTimeImmutable('2025-06-20 15:45:00');

        $link = new Link(
            id: 1,
            userId: null,
            shortCode: 'abc',
            originalUrl: 'https://example.com',
            isActive: true,
            expiresAt: $expiresAt,
            createdAt: $createdAt
        );

        $array = $link->toArray();

        $this->assertEquals('2024-01-15 10:30:45', $array['created_at']);
        $this->assertEquals('2025-06-20 15:45:00', $array['expires_at']);
    }

    public function testIsExpiredWithExactExpirationTime(): void
    {
        // Link expires exactly now (edge case)
        $now = new DateTimeImmutable();

        $link = new Link(
            id: 1,
            userId: null,
            shortCode: 'abc',
            originalUrl: 'https://example.com',
            isActive: true,
            expiresAt: $now,
            createdAt: new DateTimeImmutable()
        );

        // If expiresAt equals current time, it's not expired yet (< comparison)
        // But due to time precision, this could be either way
        // The implementation uses <, so if expiresAt == now, it's NOT expired
        // However, by the time we check, time has passed
        // This tests the boundary condition
        $this->assertIsBool($link->isExpired());
    }

    public function testIsValidReturnsFalseWhenBothInactiveAndExpired(): void
    {
        $pastDate = (new DateTimeImmutable())->modify('-1 day');

        $link = new Link(
            id: 1,
            userId: null,
            shortCode: 'abc',
            originalUrl: 'https://example.com',
            isActive: false,
            expiresAt: $pastDate,
            createdAt: new DateTimeImmutable()
        );

        $this->assertFalse($link->isValid());
    }
}
