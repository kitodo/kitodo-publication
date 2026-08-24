<?php
namespace EWW\Dpf\Tests\Unit\Services\ProcessNumber;

use EWW\Dpf\Services\ElasticSearch\ElasticSearch;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

/**
 * Covers ProcessNumberGenerator::existsInBackofficeIndex() - the check added
 * to catch process-number collisions against bulk-migrated Fedora 6
 * documents, which never get a local DB row and so are invisible to the
 * pre-existing DocumentRepository::findByIdentifier() check.
 */
class ProcessNumberGeneratorTest extends TestCase
{
    private function callExistsInBackofficeIndex(ElasticSearch $es, string $candidate): bool
    {
        $method = new ReflectionMethod(ProcessNumberGenerator::class, 'existsInBackofficeIndex');
        $method->setAccessible(true);
        return $method->invoke(new ProcessNumberGenerator(), $es, $candidate);
    }

    public function testReturnsTrueWhenProcessNumberFoundInIndex(): void
    {
        $es = $this->createMock(ElasticSearch::class);
        $es->method('search')->willReturn(['hits' => ['total' => ['value' => 1]]]);

        $this->assertTrue($this->callExistsInBackofficeIndex($es, 'UBL-25-667'));
    }

    public function testReturnsFalseWhenProcessNumberNotFoundInIndex(): void
    {
        $es = $this->createMock(ElasticSearch::class);
        $es->method('search')->willReturn(['hits' => ['total' => ['value' => 0]]]);

        $this->assertFalse($this->callExistsInBackofficeIndex($es, 'UBL-25-999'));
    }

    public function testFailsOpenOnElasticsearchError(): void
    {
        $es = $this->createMock(ElasticSearch::class);
        $es->method('search')->willThrowException(new RuntimeException('connection refused'));

        $this->assertFalse($this->callExistsInBackofficeIndex($es, 'UBL-25-667'));
    }
}
