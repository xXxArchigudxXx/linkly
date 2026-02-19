<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\PaginatedResult;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PaginatedResult DTO.
 * Tests pagination logic and serialization.
 */
final class PaginatedResultTest extends TestCase
{
    // ========== HAPPY PATH TESTS ==========

    public function testGetTotalPagesCalculatesCorrectly(): void
    {
        $result = new PaginatedResult(['a', 'b', 'c'], 25, 1, 10);

        $this->assertEquals(3, $result->getTotalPages());
    }

    public function testGetTotalPagesWithExactMultiple(): void
    {
        $result = new PaginatedResult(['a', 'b'], 20, 1, 10);

        $this->assertEquals(2, $result->getTotalPages());
    }

    public function testGetTotalPagesWithSingleItem(): void
    {
        $result = new PaginatedResult(['a'], 1, 1, 10);

        $this->assertEquals(1, $result->getTotalPages());
    }

    public function testHasNextPageReturnsTrueWhenMorePages(): void
    {
        $result = new PaginatedResult(['a'], 25, 1, 10);

        $this->assertTrue($result->hasNextPage());
    }

    public function testHasNextPageReturnsFalseOnLastPage(): void
    {
        $result = new PaginatedResult(['a'], 25, 3, 10);

        $this->assertFalse($result->hasNextPage());
    }

    public function testHasPrevPageReturnsTrueWhenNotFirstPage(): void
    {
        $result = new PaginatedResult(['a'], 25, 2, 10);

        $this->assertTrue($result->hasPrevPage());
    }

    public function testHasPrevPageReturnsFalseOnFirstPage(): void
    {
        $result = new PaginatedResult(['a'], 25, 1, 10);

        $this->assertFalse($result->hasPrevPage());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $result = new PaginatedResult(['item1', 'item2'], 50, 2, 10);

        $array = $result->toArray();

        $this->assertEquals(['item1', 'item2'], $array['data']);
        $this->assertEquals(50, $array['pagination']['total']);
        $this->assertEquals(2, $array['pagination']['page']);
        $this->assertEquals(10, $array['pagination']['per_page']);
        $this->assertEquals(5, $array['pagination']['total_pages']);
    }

    public function testToJsonReturnsValidJson(): void
    {
        $result = new PaginatedResult(['item1', 'item2'], 50, 2, 10);

        $json = $result->toJson();
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertEquals(['item1', 'item2'], $decoded['data']);
    }

    public function testToJsonHandlesUnicode(): void
    {
        $result = new PaginatedResult(['привет', 'мир'], 2, 1, 10);

        $json = $result->toJson();
        $decoded = json_decode($json, true);

        $this->assertEquals(['привет', 'мир'], $decoded['data']);
    }

    public function testGettersReturnCorrectValues(): void
    {
        $data = ['a', 'b', 'c'];
        $result = new PaginatedResult($data, 100, 5, 20);

        $this->assertEquals($data, $result->getData());
        $this->assertEquals(100, $result->getTotal());
        $this->assertEquals(5, $result->getPage());
        $this->assertEquals(20, $result->getPerPage());
    }

    // ========== ADVERSARIAL TESTS ==========

    public function testGetTotalPagesWithZeroTotal(): void
    {
        $result = new PaginatedResult([], 0, 1, 10);

        // ceil(0/10) = 0
        $this->assertEquals(0, $result->getTotalPages());
    }

    public function testHasNextPageWithZeroTotal(): void
    {
        $result = new PaginatedResult([], 0, 1, 10);

        // page 1 < total_pages 0 = false
        $this->assertFalse($result->hasNextPage());
    }

    public function testHasPrevPageWithPageOne(): void
    {
        $result = new PaginatedResult([], 0, 1, 10);

        $this->assertFalse($result->hasPrevPage());
    }

    public function testEmptyDataArray(): void
    {
        $result = new PaginatedResult([], 0, 1, 10);

        $this->assertEquals([], $result->getData());
        $this->assertEquals(0, $result->getTotal());
    }

    public function testLargePageNumber(): void
    {
        $result = new PaginatedResult([], 10, 999999, 10);

        $this->assertEquals(999999, $result->getPage());
        $this->assertFalse($result->hasNextPage());
        $this->assertTrue($result->hasPrevPage());
    }

    public function testLargePerPageValue(): void
    {
        $result = new PaginatedResult(['a'], 1, 1, 1000000);

        $this->assertEquals(1, $result->getTotalPages());
    }

    public function testGetTotalPagesWithOneItemOverExactMultiple(): void
    {
        $result = new PaginatedResult([], 11, 1, 10);

        $this->assertEquals(2, $result->getTotalPages());
    }

    public function testToJsonWithNestedData(): void
    {
        $nestedData = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ];
        $result = new PaginatedResult($nestedData, 2, 1, 10);

        $json = $result->toJson();
        $decoded = json_decode($json, true);

        $this->assertEquals($nestedData, $decoded['data']);
    }

    public function testToJsonWithSpecialCharacters(): void
    {
        $data = ['item with "quotes"', 'item with \\ backslash'];
        $result = new PaginatedResult($data, 2, 1, 10);

        $json = $result->toJson();
        $decoded = json_decode($json, true);

        $this->assertEquals($data, $decoded['data']);
    }
}
