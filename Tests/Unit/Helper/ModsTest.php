<?php
namespace EWW\Dpf\Tests\Unit\Helper;

use EWW\Dpf\Helper\Mods;
use PHPUnit\Framework\TestCase;

class ModsTest extends TestCase
{
    private function modsWithHost(string $hostPid): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mods:mods xmlns:mods="http://www.loc.gov/mods/v3">
    <mods:titleInfo>
        <mods:title>Test Article</mods:title>
    </mods:titleInfo>
    <mods:relatedItem type="host">
        <mods:identifier type="local">{$hostPid}</mods:identifier>
        <mods:identifier type="urn">urn:nbn:de:bsz:14-qucosa-12345</mods:identifier>
    </mods:relatedItem>
</mods:mods>
XML;
    }

    private function modsWithoutHost(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mods:mods xmlns:mods="http://www.loc.gov/mods/v3">
    <mods:titleInfo>
        <mods:title>Standalone Document</mods:title>
    </mods:titleInfo>
</mods:mods>
XML;
    }

    private function modsWithConstituent(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mods:mods xmlns:mods="http://www.loc.gov/mods/v3">
    <mods:titleInfo>
        <mods:title>Parent Document</mods:title>
    </mods:titleInfo>
    <mods:relatedItem type="constituent">
        <mods:identifier type="local">qucosa:99999</mods:identifier>
        <mods:titleInfo><mods:title>Child Article</mods:title></mods:titleInfo>
    </mods:relatedItem>
</mods:mods>
XML;
    }

    public function testGetHostPidReturnsParentPid()
    {
        $mods = new Mods($this->modsWithHost('qucosa:12345'));
        $this->assertSame('qucosa:12345', $mods->getHostPid());
    }

    public function testGetHostPidReturnsNullWhenNoHostRelation()
    {
        $mods = new Mods($this->modsWithoutHost());
        $this->assertNull($mods->getHostPid());
    }

    public function testGetHostPidIgnoresConstituentRelation()
    {
        $mods = new Mods($this->modsWithConstituent());
        $this->assertNull($mods->getHostPid());
    }

    public function testGetHostPidReturnsNullForEmptyIdentifier()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mods:mods xmlns:mods="http://www.loc.gov/mods/v3">
    <mods:relatedItem type="host">
        <mods:identifier type="local"></mods:identifier>
    </mods:relatedItem>
</mods:mods>
XML;
        $mods = new Mods($xml);
        $this->assertNull($mods->getHostPid());
    }
}
