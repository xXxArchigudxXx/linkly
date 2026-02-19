<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\CreateLinkRequest;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateLinkRequest DTO.
 * Tests validation logic for URL shortening requests.
 */
final class CreateLinkRequestTest extends TestCase
{
    // ========== HAPPY PATH TESTS ==========

    public function testValidUrlPassesValidation(): void
    {
        $request = new CreateLinkRequest('https://example.com');

        $this->assertTrue($request->isValid());
        $this->assertEquals([], $request->getErrors());
    }

    public function testValidUrlWithCustomAliasPassesValidation(): void
    {
        $request = new CreateLinkRequest('https://example.com', 'my-link');

        $this->assertTrue($request->isValid());
    }

    public function testValidUrlWithTtlPassesValidation(): void
    {
        $request = new CreateLinkRequest('https://example.com', null, 3600);

        $this->assertTrue($request->isValid());
    }

    public function testValidUrlWithAllOptionsPassesValidation(): void
    {
        $request = new CreateLinkRequest('https://example.com/path?query=value', 'my-link', 86400);

        $this->assertTrue($request->isValid());
    }

    public function testCustomAliasWithUnderscoreAndHyphen(): void
    {
        $request = new CreateLinkRequest('https://example.com', 'my_link-123');

        $this->assertTrue($request->isValid());
    }

    public function testMinimumTtl(): void
    {
        $request = new CreateLinkRequest('https://example.com', null, 60);

        $this->assertTrue($request->isValid());
    }

    public function testMaximumTtl(): void
    {
        $request = new CreateLinkRequest('https://example.com', null, 31536000);

        $this->assertTrue($request->isValid());
    }

    public function testMinimumCustomAliasLength(): void
    {
        $request = new CreateLinkRequest('https://example.com', 'abc');

        $this->assertTrue($request->isValid());
    }

    public function testMaximumCustomAliasLength(): void
    {
        $request = new CreateLinkRequest('https://example.com', 'abcdefghijklmnopqrst');

        $this->assertTrue($request->isValid());
    }

    public function testFromArrayCreatesRequest(): void
    {
        $request = CreateLinkRequest::fromArray([
            'url' => 'https://example.com',
            'custom_alias' => 'my-link',
            'ttl' => 3600,
        ]);

        $this->assertEquals('https://example.com', $request->getUrl());
        $this->assertEquals('my-link', $request->getCustomAlias());
        $this->assertEquals(3600, $request->getTtl());
    }

    public function testFromArrayHandlesMissingFields(): void
    {
        $request = CreateLinkRequest::fromArray([]);

        $this->assertEquals('', $request->getUrl());
        $this->assertNull($request->getCustomAlias());
        $this->assertNull($request->getTtl());
    }

    // ========== ADVERSARIAL TESTS ==========

    public function testEmptyUrlFailsValidation(): void
    {
        $request = new CreateLinkRequest('');

        $this->assertFalse($request->isValid());
        $this->assertArrayHasKey('url', $request->getErrors());
        $this->assertEquals('URL is required', $request->getErrors()['url']);
    }

    public function testInvalidUrlFailsValidation(): void
    {
        $request = new CreateLinkRequest('not-a-url');

        $this->assertFalse($request->isValid());
        $this->assertArrayHasKey('url', $request->getErrors());
        // URL без схемы отклоняется проверкой безопасности
        $this->assertEquals('URL scheme not allowed', $request->getErrors()['url']);
    }

    public function testJavascriptSchemeFailsValidation(): void
    {
        $request = new CreateLinkRequest('javascript:alert(1)');

        $this->assertFalse($request->isValid());
        $this->assertArrayHasKey('url', $request->getErrors());
        $this->assertEquals('URL scheme not allowed', $request->getErrors()['url']);
    }

    public function testDataSchemeFailsValidation(): void
    {
        $request = new CreateLinkRequest('data:text/html,<script>alert(1)</script>');

        $this->assertFalse($request->isValid());
        $this->assertArrayHasKey('url', $request->getErrors());
        $this->assertEquals('URL scheme not allowed', $request->getErrors()['url']);
    }

    public function testFileSchemeFailsValidation(): void
    {
        $request = new CreateLinkRequest('file:///etc/passwd');

        $this->assertFalse($request->isValid());
        $this->assertArrayHasKey('url', $request->getErrors());
        $this->assertEquals('URL scheme not allowed', $request->getErrors()['url']);
    }

    public function testFtpSchemeFailsValidation(): void
    {
        $request = new CreateLinkRequest('ftp://example.com/file');

        $this->assertFalse($request->isValid());
        $this->assertArrayHasKey('url', $request->getErrors());
        $this->assertEquals('URL scheme not allowed', $request->getErrors()['url']);
    }

    public function testCustomAliasTooShortFailsValidation(): void
    {
        $request = new CreateLinkRequest('https://example.com', 'ab');

        $this->assertFalse($request->isValid());
        $this->assertArrayHasKey('custom_alias', $request->getErrors());
        $this->assertEquals('Alias must be 3-20 characters', $request->getErrors()['custom_alias']);
    }

    public function testCustomAliasTooLongFailsValidation(): void
    {
        $request = new CreateLinkRequest('https://example.com', 'abcdefghijklmnopqrstu');

        $this->assertFalse($request->isValid());
        $this->assertArrayHasKey('custom_alias', $request->getErrors());
        $this->assertEquals('Alias must be 3-20 characters', $request->getErrors()['custom_alias']);
    }

    public function testCustomAliasWithInvalidCharactersFailsValidation(): void
    {
        $request = new CreateLinkRequest('https://example.com', 'my link!');

        $this->assertFalse($request->isValid());
        $this->assertArrayHasKey('custom_alias', $request->getErrors());
        $this->assertEquals('Alias can only contain letters, numbers, underscore and hyphen', $request->getErrors()['custom_alias']);
    }

    public function testCustomAliasWithDotFailsValidation(): void
    {
        $request = new CreateLinkRequest('https://example.com', 'my.link');

        $this->assertFalse($request->isValid());
        $this->assertArrayHasKey('custom_alias', $request->getErrors());
    }

    public function testTtlTooSmallFailsValidation(): void
    {
        $request = new CreateLinkRequest('https://example.com', null, 59);

        $this->assertFalse($request->isValid());
        $this->assertArrayHasKey('ttl', $request->getErrors());
        $this->assertEquals('TTL must be between 60 seconds and 1 year', $request->getErrors()['ttl']);
    }

    public function testTtlTooLargeFailsValidation(): void
    {
        $request = new CreateLinkRequest('https://example.com', null, 31536001);

        $this->assertFalse($request->isValid());
        $this->assertArrayHasKey('ttl', $request->getErrors());
        $this->assertEquals('TTL must be between 60 seconds and 1 year', $request->getErrors()['ttl']);
    }

    public function testMultipleValidationErrors(): void
    {
        $request = new CreateLinkRequest('', 'ab', 30);

        $this->assertFalse($request->isValid());
        $this->assertCount(3, $request->getErrors());
        $this->assertArrayHasKey('url', $request->getErrors());
        $this->assertArrayHasKey('custom_alias', $request->getErrors());
        $this->assertArrayHasKey('ttl', $request->getErrors());
    }

    public function testHttpSchemeIsAllowed(): void
    {
        $request = new CreateLinkRequest('http://example.com');

        $this->assertTrue($request->isValid());
    }

    public function testHttpsSchemeIsAllowed(): void
    {
        $request = new CreateLinkRequest('https://example.com');

        $this->assertTrue($request->isValid());
    }

    public function testUrlWithoutPathIsValid(): void
    {
        $request = new CreateLinkRequest('https://example.com');

        $this->assertTrue($request->isValid());
    }

    public function testUrlWithPortIsValid(): void
    {
        $request = new CreateLinkRequest('https://example.com:8080/path');

        $this->assertTrue($request->isValid());
    }

    public function testUrlWithFragmentIsValid(): void
    {
        $request = new CreateLinkRequest('https://example.com/path#section');

        $this->assertTrue($request->isValid());
    }
}
