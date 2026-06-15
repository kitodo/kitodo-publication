<?php
namespace EWW\Dpf\Tests\Unit\Services\ElasticSearch;

use EWW\Dpf\Services\ElasticSearch\PublicQueryBuilder;
use PHPUnit\Framework\TestCase;

class PublicQueryBuilderTest extends TestCase
{
    /** @var PublicQueryBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new PublicQueryBuilder();
    }

    public function testStateFilterAlwaysPresent(): void
    {
        $query = $this->builder->buildQuery([]);
        $filter = $this->extractMustFilters($query);
        $this->assertContains(['term' => ['state' => 'NONE:ACTIVE']], $filter);
    }

    public function testStateFilterPresentWithSearchTerm(): void
    {
        $query = $this->builder->buildQuery(['q' => 'test']);
        $filter = $this->extractMustFilters($query);
        $this->assertContains(['term' => ['state' => 'NONE:ACTIVE']], $filter);
    }

    public function testStateFilterPresentWithDoctype(): void
    {
        $query = $this->builder->buildQuery(['doctype' => 'article']);
        $filter = $this->extractMustFilters($query);
        $this->assertContains(['term' => ['state' => 'NONE:ACTIVE']], $filter);
    }

    public function testStateFilterCannotBeOverridden(): void
    {
        $query = $this->builder->buildQuery(['state' => 'NONE:NONE']);
        $filter = $this->extractMustFilters($query);
        $this->assertContains(['term' => ['state' => 'NONE:ACTIVE']], $filter);
        foreach ($filter as $clause) {
            if (isset($clause['term']['state'])) {
                $this->assertSame('NONE:ACTIVE', $clause['term']['state']);
            }
        }
    }

    public function testSearchTermBuildsQueryString(): void
    {
        $query = $this->builder->buildQuery(['q' => 'Leipzig']);
        $this->assertArrayHasKey('query', $query['body']);
        $queryNode = $query['body']['query'];
        $this->assertArrayHasKey('bool', $queryNode);
        $this->assertArrayHasKey('must', $queryNode['bool']);
    }

    public function testEmptyQueryMatchesAll(): void
    {
        $query = $this->builder->buildQuery([]);
        $queryNode = $query['body']['query'];
        $this->assertArrayHasKey('bool', $queryNode);
        $this->assertArrayNotHasKey('must', $queryNode['bool']);
    }

    public function testLuceneCharsEscaped(): void
    {
        $query = $this->builder->buildQuery(['q' => 'test+value']);
        $queryNode = $query['body']['query'];
        $qs = $queryNode['bool']['must']['query_string']['query'];
        $this->assertStringContainsString('\\+', $qs);
        $this->assertStringNotContainsString('test+value', $qs);
    }

    public function testDoctypeFilterApplied(): void
    {
        $query = $this->builder->buildQuery(['doctype' => 'article']);
        $filter = $this->extractMustFilters($query);
        $this->assertContains(['term' => ['doctype' => 'article']], $filter);
    }

    public function testYearFilterApplied(): void
    {
        $query = $this->builder->buildQuery(['year' => '2022']);
        $filter = $this->extractMustFilters($query);
        $this->assertContains(['term' => ['year' => '2022']], $filter);
    }

    public function testYearRangeFilterApplied(): void
    {
        $query = $this->builder->buildQuery(['yearFrom' => '2020', 'yearTo' => '2023']);
        $filter = $this->extractMustFilters($query);
        $rangeFound = false;
        foreach ($filter as $clause) {
            if (isset($clause['range']['year'])) {
                $this->assertSame('2020', $clause['range']['year']['gte']);
                $this->assertSame('2023', $clause['range']['year']['lte']);
                $rangeFound = true;
            }
        }
        $this->assertTrue($rangeFound, 'year range filter not found');
    }

    public function testPaginationFromSize(): void
    {
        $query = $this->builder->buildQuery([], 20, 40);
        $this->assertSame(20, $query['body']['size']);
        $this->assertSame(40, $query['body']['from']);
    }

    public function testDefaultPagination(): void
    {
        $query = $this->builder->buildQuery([]);
        $this->assertSame(10, $query['body']['size']);
        $this->assertSame(0, $query['body']['from']);
    }

    public function testSizeClamped(): void
    {
        $query = $this->builder->buildQuery([], 9999);
        $this->assertLessThanOrEqual(100, $query['body']['size']);
    }

    public function testFromClamped(): void
    {
        $query = $this->builder->buildQuery([], 10, -5);
        $this->assertSame(0, $query['body']['from']);
    }

    public function testDefaultSortIsScore(): void
    {
        $query = $this->builder->buildQuery([]);
        $this->assertSame('_score', $query['body']['sort'][0]);
    }

    public function testSortByTitleKeyword(): void
    {
        $query = $this->builder->buildQuery(['sort' => 'title']);
        $sort = $query['body']['sort'];
        $this->assertTrue(
            isset($sort[0]['title.keyword']) || (is_array($sort[0]) && array_key_exists('title.keyword', $sort[0])),
            'title.keyword sort not found'
        );
    }

    public function testUnknownSortFallsBackToScore(): void
    {
        $query = $this->builder->buildQuery(['sort' => 'injectedField']);
        $this->assertSame('_score', $query['body']['sort'][0]);
    }

    public function testAggregationsAlwaysPresent(): void
    {
        $query = $this->builder->buildQuery([]);
        $this->assertArrayHasKey('aggs', $query['body']);
        $aggs = $query['body']['aggs'];
        $this->assertArrayHasKey('doctype', $aggs);
        $this->assertArrayHasKey('year', $aggs);
        $this->assertArrayHasKey('openAccess', $aggs);
        $this->assertArrayHasKey('hasFiles', $aggs);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function extractMustFilters(array $query): array
    {
        $queryNode = $query['body']['query'];
        if (isset($queryNode['bool']['must'])) {
            $filterSection = $queryNode['bool']['filter'] ?? [];
        } else {
            $filterSection = $queryNode['bool']['filter'] ?? [];
        }
        if (isset($filterSection['bool']['must'])) {
            return $filterSection['bool']['must'];
        }
        return $filterSection;
    }
}
