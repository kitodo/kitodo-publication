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
        // ContentObjectRenderer::__construct() reads this directly; getRelatedItems()/getParentItems()
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
     * "original_date" (Erscheinungsjahr) is extracted via an unanchored xpath
     * matching every mods:relatedItem[@type="host"] block in a document
     * (tx_dpf_metadata uid 299); when two host blocks legitimately share the
     * same dateIssued (e.g. qucosa-14455, qucosa-15470), the join must not
     * render the value twice as "2016,2016". Distinct values must still both
     * appear (a doc can have genuinely different host years).
     */
    public function testJoinValuesSkipsDuplicateNonEmptyValues()
    {
        $method = new \ReflectionMethod(LandingPageAssembler::class, 'joinValues');
        $method->setAccessible(true);

        $this->assertSame(
            '2016',
            $method->invoke(null, ['2016', '2016'], ', ')
        );
        $this->assertSame(
            '2016, 2020',
            $method->invoke(null, ['2016', '2020', '2016'], ', ')
        );
    }

    /**
     * Identifier type deliberately not "local"/"urn" so getParentItems()
     * takes the null-url branch and never calls typoLink_URL() (needs TSFE).
     */
    public function testGetParentItemsIncludesHostAndSeries()
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

        $items = $assembler->getParentItems($doc, []);

        $this->assertCount(2, $items);
        $titles = array_column($items, 'title');
        $this->assertContains('Journal', $titles);
        $this->assertContains('Series', $titles);

        $byTitle = array_column($items, 'relationLabel', 'title');
        $this->assertSame('Erschienen in', $byTitle['Journal']);
        $this->assertSame('Schriftenreihe', $byTitle['Series']);
    }

    /**
     * Reproduces the qucosa-15470 migration artifact: 3 relatedItem[type=host]
     * blocks for the same journal (shared identifier), only one carries a title.
     */
    public function testGetParentItemsDedupesDuplicateHostByIdentifier()
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

        $items = $assembler->getParentItems($doc, []);

        $this->assertCount(1, $items);
        $this->assertEquals('Journal', $items[0]['title']);
    }

    /**
     * Reproduces qucosa-14455: a relatedItem carrying neither a title nor any
     * identifier rendered as a visible empty <li> in the relations list.
     */
    public function testGetParentItemsSkipsItemsWithoutTitleAndIdentifier()
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

        $items = $assembler->getParentItems($doc, []);

        $this->assertCount(1, $items);
        $this->assertEquals('Journal', $items[0]['title']);
    }

    /**
     * Reproduces #2039: slub:sortingKey is meant only to order relatedItems
     * (via usort() above), but was also being appended to the visible label
     * as " - <sortingKey>" — leaking the internal sort key into the public
     * landing page (seen live on qucosa-33168's "Erschienen in" link).
     */
    public function testGetParentItemsDoesNotAppendSortingKeyToTitle()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mets:mets xmlns:mets="http://www.loc.gov/METS/"
           xmlns:mods="http://www.loc.gov/mods/v3"
           xmlns:slub="http://slub-dresden.de/"
           OBJID="qucosa:book">
    <mets:dmdSec ID="DMD_000">
        <mets:mdWrap MDTYPE="MODS">
            <mets:xmlData>
                <mods:mods>
                    <mods:relatedItem type="series">
                        <mods:titleInfo><mods:title>Schriftenreihe</mods:title></mods:titleInfo>
                        <mods:identifier type="issn">1234-5678</mods:identifier>
                        <mods:extension><slub:info><slub:sortingKey>042</slub:sortingKey></slub:info></mods:extension>
                    </mods:relatedItem>
                </mods:mods>
            </mets:xmlData>
        </mets:mdWrap>
    </mets:dmdSec>
    <mets:structMap TYPE="LOGICAL">
        <mets:div ID="LOG_0000" DMDID="DMD_000" TYPE="book"/>
    </mets:structMap>
</mets:mets>
XML;
        $doc = MetsDocument::fromXmlString($xml);
        $assembler = new LandingPageAssembler();

        $items = $assembler->getParentItems($doc, []);

        $this->assertCount(1, $items);
        $this->assertEquals('Schriftenreihe', $items[0]['title']);
    }

    /**
     * Reproduces #2039: parent (host/series) and child (constituent) links
     * were rendered mixed into one list at the bottom of the page. They must
     * now come from two distinct methods so the template can place the
     * parent link separately near the top.
     */
    public function testGetRelatedItemsAndGetParentItemsAreDisjoint()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mets:mets xmlns:mets="http://www.loc.gov/METS/"
           xmlns:mods="http://www.loc.gov/mods/v3"
           xmlns:slub="http://slub-dresden.de/"
           OBJID="qucosa:issue">
    <mets:dmdSec ID="DMD_000">
        <mets:mdWrap MDTYPE="MODS">
            <mets:xmlData>
                <mods:mods>
                    <mods:relatedItem type="host">
                        <mods:titleInfo><mods:title>Journal</mods:title></mods:titleInfo>
                        <mods:identifier type="issn">1234-5678</mods:identifier>
                    </mods:relatedItem>
                    <mods:relatedItem type="constituent">
                        <mods:titleInfo><mods:title>Article One</mods:title></mods:titleInfo>
                        <mods:identifier type="issn">1111-1111</mods:identifier>
                    </mods:relatedItem>
                </mods:mods>
            </mets:xmlData>
        </mets:mdWrap>
    </mets:dmdSec>
    <mets:structMap TYPE="LOGICAL">
        <mets:div ID="LOG_0000" DMDID="DMD_000" TYPE="periodical_issue"/>
    </mets:structMap>
</mets:mets>
XML;
        $doc = MetsDocument::fromXmlString($xml);
        $assembler = new LandingPageAssembler();

        $children = $assembler->getRelatedItems($doc, []);
        $parents  = $assembler->getParentItems($doc, []);

        $this->assertCount(1, $children);
        $this->assertEquals('Article One', $children[0]['title']);
        $this->assertCount(1, $parents);
        $this->assertEquals('Journal', $parents[0]['title']);
    }

    /**
     * Reproduces #2039 / qucosa-80960: a host relatedItem's URN
     * ("urn:...-752899", the journal "Archiv für Epigraphik") is also
     * present in every issue of that journal in the public index, because
     * getSearchIdentifiers() flattens inherited host links into the same
     * field. Only the journal's own record carries that URN exactly once;
     * the 7 issue siblings each carry it twice (their own URN + the
     * inherited one). Fixture is a trimmed copy of the real ES response.
     */
    public function testPickOwnTitleFromSearchHitsSkipsSiblingsWithInheritedUrn()
    {
        $assembler = new LandingPageAssembler();
        $urn = 'urn:nbn:de:bsz:15-qucosa2-752899';

        $hits = [
            [
                '_source' => [
                    'title' => ['Archiv für Epigraphik'],
                    'identifier' => ['qucosa-75222', 'UBL-21-863', $urn, 'urn:nbn:de:bsz:15-qucosa2-752220'],
                ],
            ],
            [
                '_source' => [
                    'title' => ['Archiv für Epigraphik', 'AfE'],
                    'identifier' => ['qucosa-75289', 'UBL-21-880', '2748-8489', $urn],
                ],
            ],
            [
                '_source' => [
                    'title' => ['Archiv für Epigraphik'],
                    'identifier' => ['qucosa-80960', 'UBL-22-875', $urn, 'urn:nbn:de:bsz:15-qucosa2-809604'],
                ],
            ],
        ];

        $this->assertSame('Archiv für Epigraphik', $assembler->pickOwnTitleFromSearchHits($hits, $urn));
    }

    public function testPickOwnTitleFromSearchHitsReturnsEmptyWhenNoOwnMatch()
    {
        $assembler = new LandingPageAssembler();
        $urn = 'urn:nbn:de:bsz:15-qucosa2-752899';

        $hits = [
            [
                '_source' => [
                    'title' => ['Issue'],
                    'identifier' => ['qucosa-75222', $urn, 'urn:nbn:de:bsz:15-qucosa2-752220'],
                ],
            ],
        ];

        $this->assertSame('', $assembler->pickOwnTitleFromSearchHits($hits, $urn));
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

    /**
     * getHostUrl() picks the host relatedItem's URL for embedding into the
     * "Quellenangabe" prose row (#2039); a series entry must not be picked
     * even if it happens to precede the host entry.
     */
    public function testGetHostUrlPicksHostOverSeries()
    {
        $assembler = new LandingPageAssembler();

        $url = $assembler->getHostUrl([
            ['relation' => 'series', 'url' => 'https://example.org/series'],
            ['relation' => 'host', 'url' => 'https://example.org/host'],
        ]);

        $this->assertSame('https://example.org/host', $url);
    }

    public function testGetHostUrlReturnsEmptyStringWhenNoLinkableHost()
    {
        $assembler = new LandingPageAssembler();

        $this->assertSame('', $assembler->getHostUrl([]));
        $this->assertSame('', $assembler->getHostUrl([['relation' => 'host', 'url' => null]]));
        $this->assertSame('', $assembler->getHostUrl([['relation' => 'series', 'url' => 'https://example.org/series']]));
    }

    /**
     * Reproduces #2039 item 2 (qucosa-83142/qucosa-14455): for
     * Zeitschriftenartikel (article) and Konferenzbeitrag (in_proceeding),
     * the host entry is now embedded into the citation prose
     * (EmbedHostLinkInQuellenangabeUpdate), so the separate parentItems
     * entry must be dropped to avoid showing the link twice.
     */
    public function testFilterEmbeddedHostItemsDropsHostForArticleAndInProceeding()
    {
        $assembler = new LandingPageAssembler();
        $items = [
            ['relation' => 'host', 'url' => 'https://example.org/host', 'title' => 'Journal'],
            ['relation' => 'series', 'url' => 'https://example.org/series', 'title' => 'Series'],
        ];

        $this->assertSame(
            [['relation' => 'series', 'url' => 'https://example.org/series', 'title' => 'Series']],
            $assembler->filterEmbeddedHostItems($items, 'article')
        );
        $this->assertSame(
            [['relation' => 'series', 'url' => 'https://example.org/series', 'title' => 'Series']],
            $assembler->filterEmbeddedHostItems($items, 'in_proceeding')
        );
    }

    /**
     * Other doctypes (e.g. periodical_issue/"Erschienen in") still rely on
     * the parentItems host entry as their only link — must be untouched.
     */
    public function testFilterEmbeddedHostItemsKeepsHostForOtherDoctypes()
    {
        $assembler = new LandingPageAssembler();
        $items = [['relation' => 'host', 'url' => 'https://example.org/host', 'title' => 'Journal']];

        $this->assertSame($items, $assembler->filterEmbeddedHostItems($items, 'periodical_issue'));
    }

    /**
     * getVisibleParentItems() must only drop the host entry when the prose
     * row will actually render a linked title — reproduces a bug where the
     * host entry was filtered unconditionally by doctype: an article with
     * only an ISSN on its host relatedItem (no linkable identifier, so
     * hostUrl is empty) would otherwise lose the "Erschienen in" line
     * entirely, with no unlinked prose title either (#2039 regression risk).
     */
    public function testGetVisibleParentItemsKeepsHostWhenHostUrlEmpty()
    {
        $assembler = new LandingPageAssembler();
        $items = [['relation' => 'host', 'url' => null, 'title' => 'Journal']];

        $this->assertSame(
            $items,
            $assembler->getVisibleParentItems($items, '', 'Article Title', 'article')
        );
    }

    /**
     * Same guard for the other fieldRequired on the wrap row: if
     * original_title is empty, the <dt>Zeitschrift</dt> block never renders
     * at all, so the parentItems host entry must stay as the only link.
     */
    public function testGetVisibleParentItemsKeepsHostWhenOriginalTitleEmpty()
    {
        $assembler = new LandingPageAssembler();
        $items = [['relation' => 'host', 'url' => 'https://example.org/host', 'title' => 'Journal']];

        $this->assertSame(
            $items,
            $assembler->getVisibleParentItems($items, 'https://example.org/host', '', 'article')
        );
    }

    public function testGetVisibleParentItemsDropsHostWhenProseRowWillLinkIt()
    {
        $assembler = new LandingPageAssembler();
        $items = [
            ['relation' => 'host', 'url' => 'https://example.org/host', 'title' => 'Journal'],
            ['relation' => 'series', 'url' => 'https://example.org/series', 'title' => 'Series'],
        ];

        $this->assertSame(
            [['relation' => 'series', 'url' => 'https://example.org/series', 'title' => 'Series']],
            $assembler->getVisibleParentItems($items, 'https://example.org/host', 'Article Title', 'article')
        );
    }
}
