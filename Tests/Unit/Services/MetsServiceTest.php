<?php
namespace EWW\Dpf\Tests\Unit\Services;

use EWW\Dpf\Services\MetsService;
use PHPUnit\Framework\TestCase;

class MetsServiceTest extends TestCase
{
    private function makeMockRedis(bool $connectOk, $cached): object
    {
        $redis = $this->createMock(\Redis::class);
        $redis->method('connect')->willReturn($connectOk);
        $redis->method('select')->willReturn(true);
        $redis->method('get')->willReturn($cached);
        return $redis;
    }

    public function testGetXmlReturnsNullWhenFedoraFails(): void
    {
        $settings = ['fedoraHost' => 'invalid.host.local'];
        $service = new MetsService($settings);
        $result = $service->getXml('qucosa:99999');
        $this->assertNull($result);
    }

    public function testDefaultsAppliedFromEmptySettings(): void
    {
        // Verifies constructor does not throw on empty settings array.
        $service = new MetsService([]);
        $this->assertInstanceOf(MetsService::class, $service);
    }
}
