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

    private function exporterWithSlubData(): MetsExporter
    {
        $slubData = new \DOMDocument();
        $slubData->loadXML('<slub:info xmlns:slub="http://slub-dresden.de/"/>');

        $exporter = new MetsExporter();
        $property = new \ReflectionProperty(MetsExporter::class, 'xmlData');
        $property->setAccessible(true);
        $property->setValue($exporter, $slubData);

        return $exporter;
    }

    public function testCustomXPathSlubThrowsOnMalformedFirstPart()
    {
        $this->expectException(\Exception::class);
        $this->exporterWithSlubData()->customXPathSlub('a]%b', true);
    }

    public function testCustomXPathSlubThrowsOnMalformedSecondPart()
    {
        $this->expectException(\Exception::class);
        $this->exporterWithSlubData()->customXPathSlub('a%b]', true);
    }

    public function testSetModsAcceptsValidXml()
    {
        $exporter = new MetsExporter();
        $exporter->setMods('<mods:mods xmlns:mods="http://www.loc.gov/mods/v3"/>');
        $this->assertInstanceOf(MetsExporter::class, $exporter);
    }
}
