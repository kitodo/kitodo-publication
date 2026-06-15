<?php
namespace EWW\Dpf\Tests\Unit\Services;

use EWW\Dpf\Services\PaginationBuilder;
use PHPUnit\Framework\TestCase;

class PaginationBuilderTest extends TestCase
{
    public function testSinglePage(): void
    {
        $p = PaginationBuilder::build(5, 10, 0);
        $this->assertSame(1, $p['totalPages']);
        $this->assertSame(1, $p['currentPage']);
        $this->assertFalse($p['hasPrev']);
        $this->assertFalse($p['hasNext']);
        $this->assertSame([1], $p['pages']);
    }

    public function testMultiplePages(): void
    {
        $p = PaginationBuilder::build(100, 10, 0);
        $this->assertSame(10, $p['totalPages']);
        $this->assertSame(1, $p['currentPage']);
        $this->assertFalse($p['hasPrev']);
        $this->assertTrue($p['hasNext']);
    }

    public function testCurrentPageFromOffset(): void
    {
        $p = PaginationBuilder::build(100, 10, 30);
        $this->assertSame(4, $p['currentPage']);
        $this->assertTrue($p['hasPrev']);
        $this->assertTrue($p['hasNext']);
    }

    public function testLastPage(): void
    {
        $p = PaginationBuilder::build(100, 10, 90);
        $this->assertSame(10, $p['currentPage']);
        $this->assertTrue($p['hasPrev']);
        $this->assertFalse($p['hasNext']);
    }

    public function testWindowCenteredOnCurrentPage(): void
    {
        $p = PaginationBuilder::build(200, 10, 90);
        $this->assertContains(10, $p['pages']);
        $this->assertLessThanOrEqual(7, count($p['pages']));
        $this->assertContains($p['currentPage'], $p['pages']);
    }

    public function testZeroResults(): void
    {
        $p = PaginationBuilder::build(0, 10, 0);
        $this->assertSame(0, $p['totalPages']);
        $this->assertSame(1, $p['currentPage']);
        $this->assertFalse($p['hasPrev']);
        $this->assertFalse($p['hasNext']);
    }

    public function testNextOffsetAndPrevOffset(): void
    {
        $p = PaginationBuilder::build(100, 10, 20);
        $this->assertSame(30, $p['nextOffset']);
        $this->assertSame(10, $p['prevOffset']);
    }

    public function testOffsetForPage(): void
    {
        $p = PaginationBuilder::build(100, 10, 0);
        $this->assertSame(20, $p['offsetForPage'][3]);
    }
}
