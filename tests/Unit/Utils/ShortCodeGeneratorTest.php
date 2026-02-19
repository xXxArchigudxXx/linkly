<?php

declare(strict_types=1);

namespace App\Tests\Unit\Utils;

use App\Utils\ShortCodeGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ShortCodeGenerator class.
 * Tests short code generation and validation.
 */
final class ShortCodeGeneratorTest extends TestCase
{
    // ========== HAPPY PATH TESTS ==========

    public function testGenerateReturnsStringOfCorrectLength(): void
    {
        $code = ShortCodeGenerator::generate(6);

        $this->assertEquals(6, strlen($code));
    }

    public function testGenerateReturnsStringOfCustomLength(): void
    {
        $code = ShortCodeGenerator::generate(10);

        $this->assertEquals(10, strlen($code));
    }

    public function testGenerateReturnsStringOfLengthOne(): void
    {
        $code = ShortCodeGenerator::generate(1);

        $this->assertEquals(1, strlen($code));
    }

    public function testGenerateUsesBase62Charset(): void
    {
        $code = ShortCodeGenerator::generate(100);

        // Should only contain 0-9, a-z, A-Z
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $code);
    }

    public function testGenerateReturnsDifferentCodesOnMultipleCalls(): void
    {
        $codes = [];
        for ($i = 0; $i < 100; $i++) {
            $codes[] = ShortCodeGenerator::generate(6);
        }

        $uniqueCodes = array_unique($codes);

        // With 100 calls, we should have at least 99 unique codes (statistically)
        $this->assertGreaterThan(99, count($uniqueCodes));
    }

    public function testIsValidCodeReturnsTrueForValidBase62(): void
    {
        $this->assertTrue(ShortCodeGenerator::isValidCode('abc123'));
        $this->assertTrue(ShortCodeGenerator::isValidCode('ABC123'));
        $this->assertTrue(ShortCodeGenerator::isValidCode('AbC123xYz'));
        $this->assertTrue(ShortCodeGenerator::isValidCode('0123456789'));
        $this->assertTrue(ShortCodeGenerator::isValidCode('abcdefghijklmnopqrstuvwxyz'));
        $this->assertTrue(ShortCodeGenerator::isValidCode('ABCDEFGHIJKLMNOPQRSTUVWXYZ'));
    }

    // ========== ADVERSARIAL TESTS ==========

    public function testIsValidCodeReturnsFalseForEmptyString(): void
    {
        $this->assertFalse(ShortCodeGenerator::isValidCode(''));
    }

    public function testIsValidCodeReturnsFalseForSpecialCharacters(): void
    {
        $this->assertFalse(ShortCodeGenerator::isValidCode('abc-123'));
        $this->assertFalse(ShortCodeGenerator::isValidCode('abc_123'));
        $this->assertFalse(ShortCodeGenerator::isValidCode('abc 123'));
        $this->assertFalse(ShortCodeGenerator::isValidCode('abc@123'));
        $this->assertFalse(ShortCodeGenerator::isValidCode('abc!123'));
    }

    public function testIsValidCodeReturnsFalseForUnicodeCharacters(): void
    {
        $this->assertFalse(ShortCodeGenerator::isValidCode('абц123'));
        $this->assertFalse(ShortCodeGenerator::isValidCode('abc中文'));
        $this->assertFalse(ShortCodeGenerator::isValidCode('abc🎉'));
    }

    public function testIsValidCodeReturnsFalseForNewlines(): void
    {
        $this->assertFalse(ShortCodeGenerator::isValidCode("abc\n123"));
        $this->assertFalse(ShortCodeGenerator::isValidCode("abc\r123"));
    }

    public function testIsValidCodeReturnsFalseForNullBytes(): void
    {
        $this->assertFalse(ShortCodeGenerator::isValidCode("abc\0123"));
    }

    public function testGenerateWithZeroLengthReturnsEmptyString(): void
    {
        $code = ShortCodeGenerator::generate(0);

        $this->assertEquals('', $code);
    }

    public function testGenerateDefaultLengthIsSix(): void
    {
        // Default parameter should be 6
        $code = ShortCodeGenerator::generate();

        $this->assertEquals(6, strlen($code));
    }
}
