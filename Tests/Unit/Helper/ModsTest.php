<?php
namespace EWW\Dpf\Tests\Unit\Helper;

use EWW\Dpf\Helper\Mods;
use PHPUnit\Framework\TestCase;

class ModsTest extends TestCase
{
    private function modsWithHost(string $hostUrn): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mods:mods xmlns:mods="http://www.loc.gov/mods/v3">
    <mods:titleInfo>
        <mods:title>Test Article</mods:title>
    </mods:titleInfo>
    <mods:relatedItem type="series">
        <mods:identifier type="urn">{$hostUrn}</mods:identifier>
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

    public function testGetHostUrnReturnsParentUrn()
    {
        $mods = new Mods($this->modsWithHost('urn:nbn:de:bsz:14-qucosa2-78923'));
        $this->assertSame('urn:nbn:de:bsz:14-qucosa2-78923', $mods->getHostUrn());
    }

    public function testGetHostUrnIgnoresUnrelatedIdentifiers()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mods:mods xmlns:mods="http://www.loc.gov/mods/v3">
    <mods:relatedItem type="series">
        <mods:titleInfo>
            <mods:title>Series Title</mods:title>
        </mods:titleInfo>
        <mods:identifier type="issn">2199-5362</mods:identifier>
        <mods:identifier type="urn">urn:nbn:de:bsz:ch1-qucosa-153040</mods:identifier>
        <mods:identifier type="isbn">978-3-96100-262-7</mods:identifier>
    </mods:relatedItem>
</mods:mods>
XML;
        $mods = new Mods($xml);
        $this->assertSame('urn:nbn:de:bsz:ch1-qucosa-153040', $mods->getHostUrn());
    }

    public function testGetHostUrnReturnsNullWhenNoHostRelation()
    {
        $mods = new Mods($this->modsWithoutHost());
        $this->assertNull($mods->getHostUrn());
    }

    public function testGetHostUrnIgnoresConstituentRelation()
    {
        $mods = new Mods($this->modsWithConstituent());
        $this->assertNull($mods->getHostUrn());
    }

    public function testGetHostUrnReturnsNullForEmptyIdentifier()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mods:mods xmlns:mods="http://www.loc.gov/mods/v3">
    <mods:relatedItem type="series">
        <mods:identifier type="urn"></mods:identifier>
    </mods:relatedItem>
</mods:mods>
XML;
        $mods = new Mods($xml);
        $this->assertNull($mods->getHostUrn());
    }

    public function testGetHostUrnReturnsNullWhenIdentifierTypeMismatched()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mods:mods xmlns:mods="http://www.loc.gov/mods/v3">
    <mods:relatedItem type="series">
        <mods:identifier type="local">qucosa:12345</mods:identifier>
    </mods:relatedItem>
</mods:mods>
XML;
        $mods = new Mods($xml);
        $this->assertNull($mods->getHostUrn());
    }
}
