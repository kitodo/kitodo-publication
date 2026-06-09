<?php

namespace EWW\Dpf\Tests\Unit\Common;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use EWW\Dpf\Common\MetsDocument;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class MetsDocumentTest extends UnitTestCase
{
    protected function loadFixture(): MetsDocument
    {
        $xml = file_get_contents(__DIR__ . '/../Services/Metadata/Fixtures/mets_article.xml');
        return MetsDocument::fromXmlString($xml, 'http://example.org/mets');
    }

    public function testFromXmlStringParsesValidMets()
    {
        $document = $this->loadFixture();
        $this->assertTrue($document->ready);
        $this->assertEquals('qucosa:12345', $document->recordId);
        $this->assertEquals('http://example.org/mets', $document->uid);
        $this->assertInstanceOf(\SimpleXMLElement::class, $document->mets);
    }

    public function testFromXmlStringWithInvalidXmlIsNotReady()
    {
        $document = MetsDocument::fromXmlString('this is not xml');
        $this->assertFalse($document->ready);
    }

    public function testFromXmlStringWithoutMetsElementIsNotReady()
    {
        $document = MetsDocument::fromXmlString('<?xml version="1.0"?><root><foo/></root>');
        $this->assertFalse($document->ready);
    }

    public function testToplevelIdWithoutStructLinkUsesFirstDmdDiv()
    {
        $document = $this->loadFixture();
        $this->assertEquals('LOG_0000', $document->getToplevelId());
        $this->assertEquals('LOG_0000', $document->toplevelId);
    }

    public function testToplevelIdPrefersDivLinkedInStructLink()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mets:mets xmlns:mets="http://www.loc.gov/METS/" xmlns:xlink="http://www.w3.org/1999/xlink" OBJID="qucosa:1">
    <mets:structMap TYPE="LOGICAL">
        <mets:div ID="LOG_0000" DMDID="DMD_000" TYPE="periodical">
            <mets:div ID="LOG_0001" DMDID="DMD_001" TYPE="issue"/>
        </mets:div>
    </mets:structMap>
    <mets:structLink>
        <mets:smLink xlink:from="LOG_0001" xlink:to="PHYS_0001"/>
    </mets:structLink>
</mets:mets>
XML;
        $document = MetsDocument::fromXmlString($xml);
        $this->assertEquals('LOG_0001', $document->getToplevelId());
    }

    public function testToplevelIdSkipsDivsWithMptr()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mets:mets xmlns:mets="http://www.loc.gov/METS/" xmlns:xlink="http://www.w3.org/1999/xlink" OBJID="qucosa:1">
    <mets:structMap TYPE="LOGICAL">
        <mets:div ID="LOG_0000" DMDID="DMD_000" TYPE="multivolume_work">
            <mets:mptr xlink:href="http://example.org/parent"/>
            <mets:div ID="LOG_0001" DMDID="DMD_001" TYPE="volume"/>
        </mets:div>
    </mets:structMap>
</mets:mets>
XML;
        $document = MetsDocument::fromXmlString($xml);
        $this->assertEquals('LOG_0001', $document->getToplevelId());
    }

    public function testGetDmdSecResolvesSupportedFormats()
    {
        $document = $this->loadFixture();
        $dmdSecs = $document->getDmdSec();

        $this->assertArrayNotHasKey('DMD_TEI', $dmdSecs);

        $this->assertArrayHasKey('DMD_000', $dmdSecs);
        $this->assertEquals('MODS', $dmdSecs['DMD_000']['type']);
        $this->assertEquals('mods', $dmdSecs['DMD_000']['xml']->getName());

        $this->assertArrayHasKey('DMD_SLUB', $dmdSecs);
        $this->assertEquals('SLUB', $dmdSecs['DMD_SLUB']['type']);
        $this->assertEquals('info', $dmdSecs['DMD_SLUB']['xml']->getName());
    }

    public function testGetToplevelStructureInfo()
    {
        $document = $this->loadFixture();
        $info = $document->getToplevelStructureInfo();
        $this->assertEquals('1', $info['order']);
        $this->assertEquals('Testartikel', $info['label']);
        $this->assertEquals('', $info['orderlabel']);
    }
}
