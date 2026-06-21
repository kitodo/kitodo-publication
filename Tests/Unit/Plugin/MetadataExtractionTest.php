<?php
namespace EWW\Dpf\Tests\Unit\Plugin;

use EWW\Dpf\Helper\MetadataExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tests for MetadataExtractor::extract() — XPath-driven extraction from MODS.
 *
 * Pure PHP, no TYPO3 bootstrap needed.
 */
class MetadataExtractionTest extends TestCase
{
    /** @var \SimpleXMLElement */
    private $mods;

    protected function setUp(): void
    {
        $xml = <<<'XML'
<mods:mods xmlns:mods="http://www.loc.gov/mods/v3"
           xmlns:xlink="http://www.w3.org/1999/xlink">
    <mods:titleInfo>
        <mods:title>Test Title</mods:title>
    </mods:titleInfo>
    <mods:titleInfo type="alternative">
        <mods:nonSort>Der </mods:nonSort>
        <mods:title>Alternative</mods:title>
    </mods:titleInfo>
    <mods:name type="personal">
        <mods:namePart type="family">Smith</mods:namePart>
        <mods:namePart type="given">John</mods:namePart>
        <mods:role><mods:roleTerm>aut</mods:roleTerm></mods:role>
    </mods:name>
    <mods:name type="personal">
        <mods:namePart type="family">Doe</mods:namePart>
        <mods:namePart type="given">Jane</mods:namePart>
        <mods:role><mods:roleTerm>aut</mods:roleTerm></mods:role>
    </mods:name>
    <mods:identifier type="urn">urn:nbn:de:example-123</mods:identifier>
    <mods:identifier type="doi">10.12345/test</mods:identifier>
    <mods:originInfo>
        <mods:dateIssued>2023</mods:dateIssued>
    </mods:originInfo>
    <mods:accessCondition type="use and reproduction"
        xlink:href="https://creativecommons.org/licenses/by/4.0/"/>
    <mods:language>
        <mods:languageTerm>ger</mods:languageTerm>
    </mods:language>
</mods:mods>
XML;

        $this->mods = simplexml_load_string($xml);
    }

    public function testSimpleElementXPath()
    {
        $result = MetadataExtractor::extract($this->mods, [
            'title' => ['xpath' => './mods:titleInfo[not(@type)]/mods:title', 'default_value' => ''],
        ]);
        $this->assertSame(['Test Title'], $result['title']);
    }

    public function testConcatXPath()
    {
        $result = MetadataExtractor::extract($this->mods, [
            'alt_title' => [
                'xpath' => 'concat(./mods:titleInfo[@type="alternative"]/mods:nonSort," ",./mods:titleInfo[@type="alternative"]/mods:title)',
                'default_value' => '',
            ],
        ]);
        $this->assertSame(['Der  Alternative'], $result['alt_title']);
    }

    public function testXlinkAttributeXPath()
    {
        $result = MetadataExtractor::extract($this->mods, [
            'licence' => [
                'xpath' => './mods:accessCondition[(@type="use and reproduction")]/@xlink:href',
                'default_value' => '',
            ],
        ]);
        $this->assertSame(['https://creativecommons.org/licenses/by/4.0/'], $result['licence']);
    }

    public function testIndexedXPath()
    {
        $result = MetadataExtractor::extract($this->mods, [
            'author1' => [
                'xpath' => './mods:name[(@type="personal")and mods:role/mods:roleTerm="aut"][1]/mods:namePart[@type="family"]',
                'default_value' => '',
            ],
            'author2' => [
                'xpath' => './mods:name[(@type="personal")and mods:role/mods:roleTerm="aut"][2]/mods:namePart[@type="family"]',
                'default_value' => '',
            ],
        ]);
        $this->assertSame(['Smith'], $result['author1']);
        $this->assertSame(['Doe'], $result['author2']);
    }

    public function testDefaultValueWhenXPathEmpty()
    {
        $result = MetadataExtractor::extract($this->mods, [
            'missing' => ['xpath' => './mods:nonexistent', 'default_value' => 'fallback'],
        ]);
        $this->assertSame(['fallback'], $result['missing']);
    }

    public function testEmptyXPathUsesDefaultValue()
    {
        $result = MetadataExtractor::extract($this->mods, [
            'nopath' => ['xpath' => '', 'default_value' => 'static'],
        ]);
        $this->assertSame(['static'], $result['nopath']);
    }

    public function testEmptyXPathNoDefaultOmitted()
    {
        $result = MetadataExtractor::extract($this->mods, [
            'nothing' => ['xpath' => '', 'default_value' => ''],
        ]);
        $this->assertArrayNotHasKey('nothing', $result);
    }

    public function testInvalidXPathDoesNotThrow()
    {
        $result = MetadataExtractor::extract($this->mods, [
            'bad' => ['xpath' => '!!!invalid!!!', 'default_value' => ''],
        ]);
        $this->assertArrayNotHasKey('bad', $result);
    }
}
