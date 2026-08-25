<?php
namespace EWW\Dpf\Tests\Unit\Common;

use EWW\Dpf\Common\ModsIdentifier;
use PHPUnit\Framework\TestCase;

/**
 * Covers ModsIdentifier::preferredType(), shared between RelatedListTool
 * (legacy plugin) and LandingPageAssembler (bug_004: was duplicated
 * byte-identical in both before being extracted here).
 */
class ModsIdentifierTest extends TestCase
{
    /**
     * Reproduces qucosa-83142: identifier[@type=issn] precedes identifier[@type=urn]
     * in document order. Must still pick "urn" (produces a link), not "issn" (null url).
     */
    public function testPrefersUrnOverEarlierNonLinkableType()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mods:relatedItem xmlns:mods="http://www.loc.gov/mods/v3" type="host">
    <mods:identifier type="issn">2748-8489</mods:identifier>
    <mods:identifier type="urn">urn:nbn:de:bsz:14-qucosa2-83142</mods:identifier>
</mods:relatedItem>
XML;
        $node = new \SimpleXMLElement($xml);
        $node->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');

        $this->assertEquals('urn', ModsIdentifier::preferredType($node));
    }

    public function testReturnsEmptyStringWhenNoIdentifierPresent()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mods:relatedItem xmlns:mods="http://www.loc.gov/mods/v3" type="host">
    <mods:titleInfo><mods:title>Some Title</mods:title></mods:titleInfo>
</mods:relatedItem>
XML;
        $node = new \SimpleXMLElement($xml);
        $node->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');

        $this->assertSame('', ModsIdentifier::preferredType($node));
    }

    public function testFallsBackToFirstTypeWhenNeitherUrnNorLocalPresent()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mods:relatedItem xmlns:mods="http://www.loc.gov/mods/v3" type="host">
    <mods:identifier type="issn">2748-8489</mods:identifier>
</mods:relatedItem>
XML;
        $node = new \SimpleXMLElement($xml);
        $node->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');

        $this->assertSame('issn', ModsIdentifier::preferredType($node));
    }
}
