<?php
namespace EWW\Dpf\Tests\Unit\Services;

use EWW\Dpf\Services\MetsExporter;
use PHPUnit\Framework\TestCase;

class MetsExporterTest extends TestCase
{
    public function testSetModsThrowsOnMalformedXml()
    {
        $this->expectException(\Exception::class);
        (new MetsExporter())->setMods('not valid xml <<<');
    }

    public function testSetModsAcceptsEmptyXml()
    {
        $exporter = new MetsExporter();
        $exporter->setMods('');
        $this->assertInstanceOf(MetsExporter::class, $exporter);
    }

    public function testSetModsAcceptsValidXml()
    {
        $exporter = new MetsExporter();
        $exporter->setMods('<mods:mods xmlns:mods="http://www.loc.gov/mods/v3"/>');
        $this->assertInstanceOf(MetsExporter::class, $exporter);
    }
}
