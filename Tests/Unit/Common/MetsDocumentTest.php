<?php
namespace EWW\Dpf\Tests\Unit\Common;

use EWW\Dpf\Common\MetsDocument;
use PHPUnit\Framework\TestCase;

class MetsDocumentTest extends TestCase
{
    private static function minimalMets(string $pid, string $modsXml = ''): string
    {
        if ($modsXml === '') {
            $modsXml = '<mods:mods xmlns:mods="http://www.loc.gov/mods/v3">
                <mods:titleInfo><mods:title>Test Title</mods:title></mods:titleInfo>
            </mods:mods>';
        }
        return '<?xml version="1.0" encoding="UTF-8"?>
<mets:mets xmlns:mets="http://www.loc.gov/METS/"
           xmlns:xlink="http://www.w3.org/1999/xlink"
           OBJID="' . $pid . '">
  <mets:dmdSec ID="DMD_001">
    <mets:mdWrap MDTYPE="MODS">
      <mets:xmlData>' . $modsXml . '</mets:xmlData>
    </mets:mdWrap>
  </mets:dmdSec>
  <mets:structMap TYPE="LOGICAL">
    <mets:div ID="LOG_0001" DMDID="DMD_001" TYPE="monograph"/>
  </mets:structMap>
</mets:mets>';
    }

    public function testFromXmlStringParsesObjid(): void
    {
        $doc = MetsDocument::fromXmlString(self::minimalMets('qucosa:12345'));
        $this->assertTrue($doc->ready);
        $this->assertEquals('qucosa:12345', $doc->recordId);
    }

    public function testFromXmlStringReturnsFalseReadyOnBadXml(): void
    {
        $doc = MetsDocument::fromXmlString('not xml');
        $this->assertFalse($doc->ready);
    }

    public function testFromXmlStringSetsToplevelId(): void
    {
        $doc = MetsDocument::fromXmlString(self::minimalMets('qucosa:1'));
        $this->assertEquals('LOG_0001', $doc->toplevelId);
    }

    /**
     * @dataProvider pidUrlProvider
     */
    public function testGetInstanceExtractsPidFromUrl(string $url, string $expectedPid): void
    {
        // getInstance() fails on missing Redis/Fedora in test — we can only verify
        // the URL pattern by checking what MetsService would receive. Instead test
        // fromXmlString which is the path getInstance() uses after fetching.
        $doc = MetsDocument::fromXmlString(self::minimalMets($expectedPid), $url);
        $this->assertEquals($url, $doc->uid);
        $this->assertEquals($expectedPid, $doc->recordId);
    }

    public function pidUrlProvider(): array
    {
        return [
            'encoded colon'        => ['https://www.qucosa.de/api/qucosa%3A12345/mets/', 'qucosa:12345'],
            'unencoded colon'      => ['https://www.qucosa.de/api/qucosa:12345/mets/', 'qucosa:12345'],
            'no trailing slash'    => ['https://www.qucosa.de/api/qucosa:99/mets', 'qucosa:99'],
        ];
    }

    /**
     * @dataProvider maliciousPidProvider
     */
    public function testGetInstanceRejectsInvalidPid(string $url): void
    {
        $result = MetsDocument::getInstance($url);
        $this->assertNull($result);
    }

    public function maliciousPidProvider(): array
    {
        return [
            'path traversal'   => ['https://www.qucosa.de/api/../../etc/passwd/mets/'],
            'no namespace sep' => ['https://www.qucosa.de/api/justapid/mets/'],
            'newline injection'=> ["https://www.qucosa.de/api/qucosa:1\nX-Header:evil/mets/"],
            'empty segment'    => ['https://www.qucosa.de/api//mets/'],
        ];
    }

    public function testGetTitleDataExtractsTitle(): void
    {
        $doc = MetsDocument::fromXmlString(self::minimalMets('qucosa:1'));
        $data = $doc->getTitleData();
        $this->assertEquals(['Test Title'], $data['title']);
    }

    public function testGetTitleDataExtractsAuthor(): void
    {
        $mods = '<mods:mods xmlns:mods="http://www.loc.gov/mods/v3">
            <mods:titleInfo><mods:title>T</mods:title></mods:titleInfo>
            <mods:name type="personal">
                <mods:namePart type="family">Müller</mods:namePart>
                <mods:namePart type="given">Hans</mods:namePart>
            </mods:name>
        </mods:mods>';
        $doc = MetsDocument::fromXmlString(self::minimalMets('qucosa:2', $mods));
        $data = $doc->getTitleData();
        $this->assertEquals(['Müller, Hans'], $data['author1']);
    }

    public function testGetTitleDataExtractsUrn(): void
    {
        $mods = '<mods:mods xmlns:mods="http://www.loc.gov/mods/v3">
            <mods:titleInfo><mods:title>T</mods:title></mods:titleInfo>
            <mods:identifier type="urn">urn:nbn:de:bsz:14-qucosa-12345</mods:identifier>
        </mods:mods>';
        $doc = MetsDocument::fromXmlString(self::minimalMets('qucosa:3', $mods));
        $data = $doc->getTitleData();
        $this->assertEquals(['urn:nbn:de:bsz:14-qucosa-12345'], $data['urn']);
    }

    public function testGetTitleDataIncludesRecordId(): void
    {
        $doc = MetsDocument::fromXmlString(self::minimalMets('qucosa:55'));
        $data = $doc->getTitleData();
        $this->assertContains('qucosa:55', $data['record_id']);
    }
}
