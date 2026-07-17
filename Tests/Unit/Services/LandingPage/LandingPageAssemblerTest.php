<?php

namespace EWW\Dpf\Tests\Unit\Services\LandingPage;

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
use EWW\Dpf\Services\LandingPage\LandingPageAssembler;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class LandingPageAssemblerTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // ContentObjectRenderer::__construct() reads this directly; getRelatedItems()
        // instantiates one even when the null-url branch never calls typoLink_URL().
        $GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['LOG'] = [];
    }

    /**
     * Empty MODS elements (e.g. <mods:subTitle/>) extract as empty strings;
     * joining them verbatim yields stray separators like "Das Lexikon : ,Jetzt
     * auch mit Untertitel" (seen live on ubl-26-5002). The join must skip
     * empty and whitespace-only values.
     */
    public function testJoinValuesSkipsEmptyAndWhitespaceOnlyValues()
    {
        $method = new \ReflectionMethod(LandingPageAssembler::class, 'joinValues');
        $method->setAccessible(true);

        $this->assertSame(
            'Jetzt auch mit Untertitel',
            $method->invoke(null, ['', 'Jetzt auch mit Untertitel'], ', ')
        );
        $this->assertSame(
            'a, b',
            $method->invoke(null, ['a', ' ', 'b', ''], ', ')
        );
        $this->assertSame('', $method->invoke(null, ['', '  '], ', '));
    }

    /**
     * Identifier type deliberately not "local"/"urn" so getRelatedItems()
     * takes the null-url branch and never calls typoLink_URL() (needs TSFE).
     */
    public function testGetRelatedItemsIncludesHostAndSeries()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mets:mets xmlns:mets="http://www.loc.gov/METS/"
           xmlns:mods="http://www.loc.gov/mods/v3"
           xmlns:slub="http://slub-dresden.de/"
           OBJID="qucosa:article">
    <mets:dmdSec ID="DMD_000">
        <mets:mdWrap MDTYPE="MODS">
            <mets:xmlData>
                <mods:mods>
                    <mods:relatedItem type="host">
                        <mods:titleInfo><mods:title>Journal</mods:title></mods:titleInfo>
                        <mods:identifier type="issn">1234-5678</mods:identifier>
                    </mods:relatedItem>
                    <mods:relatedItem type="series">
                        <mods:titleInfo><mods:title>Series</mods:title></mods:titleInfo>
                        <mods:identifier type="issn">8765-4321</mods:identifier>
                    </mods:relatedItem>
                </mods:mods>
            </mets:xmlData>
        </mets:mdWrap>
    </mets:dmdSec>
    <mets:structMap TYPE="LOGICAL">
        <mets:div ID="LOG_0000" DMDID="DMD_000" TYPE="article"/>
    </mets:structMap>
</mets:mets>
XML;
        $doc = MetsDocument::fromXmlString($xml);
        $assembler = new LandingPageAssembler();

        $items = $assembler->getRelatedItems($doc, []);

        $this->assertCount(2, $items);
        $titles = array_column($items, 'title');
        $this->assertContains('Journal', $titles);
        $this->assertContains('Series', $titles);
    }

    /**
     * Reproduces the qucosa-15470 migration artifact: 3 relatedItem[type=host]
     * blocks for the same journal (shared identifier), only one carries a title.
     */
    public function testGetRelatedItemsDedupesDuplicateHostByIdentifier()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mets:mets xmlns:mets="http://www.loc.gov/METS/"
           xmlns:mods="http://www.loc.gov/mods/v3"
           xmlns:slub="http://slub-dresden.de/"
           OBJID="qucosa:article">
    <mets:dmdSec ID="DMD_000">
        <mets:mdWrap MDTYPE="MODS">
            <mets:xmlData>
                <mods:mods>
                    <mods:relatedItem type="host">
                        <mods:identifier type="issn">1234-5678</mods:identifier>
                    </mods:relatedItem>
                    <mods:relatedItem type="host">
                        <mods:titleInfo><mods:title>Journal</mods:title></mods:titleInfo>
                        <mods:identifier type="issn">1234-5678</mods:identifier>
                    </mods:relatedItem>
                    <mods:relatedItem type="host">
                        <mods:identifier type="issn">1234-5678</mods:identifier>
                    </mods:relatedItem>
                </mods:mods>
            </mets:xmlData>
        </mets:mdWrap>
    </mets:dmdSec>
    <mets:structMap TYPE="LOGICAL">
        <mets:div ID="LOG_0000" DMDID="DMD_000" TYPE="article"/>
    </mets:structMap>
</mets:mets>
XML;
        $doc = MetsDocument::fromXmlString($xml);
        $assembler = new LandingPageAssembler();

        $items = $assembler->getRelatedItems($doc, []);

        $this->assertCount(1, $items);
        $this->assertEquals('Journal', $items[0]['title']);
    }

    /**
     * Reproduces qucosa-14455: a relatedItem carrying neither a title nor any
     * identifier rendered as a visible empty <li> in the relations list.
     */
    public function testGetRelatedItemsSkipsItemsWithoutTitleAndIdentifier()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mets:mets xmlns:mets="http://www.loc.gov/METS/"
           xmlns:mods="http://www.loc.gov/mods/v3"
           xmlns:slub="http://slub-dresden.de/"
           OBJID="qucosa:article">
    <mets:dmdSec ID="DMD_000">
        <mets:mdWrap MDTYPE="MODS">
            <mets:xmlData>
                <mods:mods>
                    <mods:relatedItem type="host">
                        <mods:part type="issue"><mods:detail><mods:number>4</mods:number></mods:detail></mods:part>
                    </mods:relatedItem>
                    <mods:relatedItem type="host">
                        <mods:titleInfo><mods:title>Journal</mods:title></mods:titleInfo>
                        <mods:identifier type="issn">1234-5678</mods:identifier>
                    </mods:relatedItem>
                </mods:mods>
            </mets:xmlData>
        </mets:mdWrap>
    </mets:dmdSec>
    <mets:structMap TYPE="LOGICAL">
        <mets:div ID="LOG_0000" DMDID="DMD_000" TYPE="article"/>
    </mets:structMap>
</mets:mets>
XML;
        $doc = MetsDocument::fromXmlString($xml);
        $assembler = new LandingPageAssembler();

        $items = $assembler->getRelatedItems($doc, []);

        $this->assertCount(1, $items);
        $this->assertEquals('Journal', $items[0]['title']);
    }

    /**
     * Reproduces qucosa-83142: identifier[@type=issn] precedes identifier[@type=urn]
     * in document order. Must still pick "urn" (produces a link), not "issn" (null url).
     * Exercised via reflection on the private selector directly — going through
     * getRelatedItems()'s "urn" branch would require a full TSFE bootstrap.
     */
    public function testPreferredIdentifierTypePrefersUrnOverEarlierNonLinkableType()
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

        $assembler = new LandingPageAssembler();
        $method = new \ReflectionMethod(LandingPageAssembler::class, 'preferredIdentifierType');
        $method->setAccessible(true);

        $this->assertEquals('urn', $method->invoke($assembler, $node));
    }
}
