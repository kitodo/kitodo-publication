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
     * #1985: only a genuinely future embargo date counts as "currently
     * embargoed" - an expired date, an unparseable value, or none at all
     * must all render nothing.
     */
    public function testIsFutureDate()
    {
        $method = new \ReflectionMethod(LandingPageAssembler::class, 'isFutureDate');
        $method->setAccessible(true);
        $assembler = new LandingPageAssembler();

        $farFuture = date('Y-m-d', strtotime('+5 years'));
        $pastDate = '2000-01-01';

        $this->assertTrue($method->invoke($assembler, $farFuture));
        $this->assertFalse($method->invoke($assembler, $pastDate));
        $this->assertFalse($method->invoke($assembler, ''));
        $this->assertFalse($method->invoke($assembler, 'not-a-date'));
    }

    /**
     * #2039/#2046: Vorgänger/Nachfolger are a sequence relation, not a
     * parent/container one - getParentItems() must not pick them up, and
     * getSequenceItems() must, with the correct German labels.
     */
    public function testGetSequenceItemsIncludesPrecedingAndSucceeding()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mets:mets xmlns:mets="http://www.loc.gov/METS/"
           xmlns:mods="http://www.loc.gov/mods/v3"
           xmlns:slub="http://slub-dresden.de/"
           OBJID="qucosa:12742">
    <mets:dmdSec ID="DMD_000">
        <mets:mdWrap MDTYPE="MODS">
            <mets:xmlData>
                <mods:mods>
                    <mods:relatedItem type="preceding">
                        <mods:titleInfo><mods:title>Bericht des Rektoratskollegiums</mods:title></mods:titleInfo>
                        <mods:identifier type="issn">1111-1111</mods:identifier>
                    </mods:relatedItem>
                    <mods:relatedItem type="succeeding">
                        <mods:titleInfo><mods:title>Jahresspiegel</mods:title></mods:titleInfo>
                        <mods:identifier type="issn">2222-2222</mods:identifier>
                    </mods:relatedItem>
                </mods:mods>
            </mets:xmlData>
        </mets:mdWrap>
    </mets:dmdSec>
    <mets:structMap TYPE="LOGICAL">
        <mets:div ID="LOG_0000" DMDID="DMD_000" TYPE="report"/>
    </mets:structMap>
</mets:mets>
XML;
        $doc = MetsDocument::fromXmlString($xml);
        $assembler = new LandingPageAssembler();

        $this->assertCount(0, $assembler->getParentItems($doc, []));

        $items = $assembler->getSequenceItems($doc, []);
        $this->assertCount(2, $items);

        $byTitle = array_column($items, 'relationLabel', 'title');
        $this->assertSame('Vorgänger', $byTitle['Bericht des Rektoratskollegiums']);
        $this->assertSame('Nachfolger', $byTitle['Jahresspiegel']);
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

        // Two hits sharing the URN, neither an exact single-URN owner nor a
        // container doctype: genuinely ambiguous, no candidate to prefer.
        $hits = [
            [
                '_source' => [
                    'title' => ['Issue A'],
                    'identifier' => ['qucosa-75222', $urn, 'urn:nbn:de:bsz:15-qucosa2-752220'],
                ],
            ],
            [
                '_source' => [
                    'title' => ['Issue B'],
                    'identifier' => ['qucosa-75223', $urn, 'urn:nbn:de:bsz:15-qucosa2-752230'],
                ],
            ],
        ];

        $this->assertSame('', $assembler->pickOwnTitleFromSearchHits($hits, $urn));
    }

    /**
     * Reproduces qucosa-11225's "Vorgänger" link (#2041): the predecessor
     * (qucosa-11116, doctype "issue") carries two URNs itself - its own plus
     * one inherited from its own host relation - so it matches neither the
     * urn-count-1 rule nor the container-doctype fallback. But the ES search
     * for this URN returns exactly one hit, so there's no ambiguity to
     * resolve: it must be the target.
     */
    public function testPickOwnTitleFromSearchHitsReturnsSoleHitsTitleWithNoDisambiguationNeeded()
    {
        $assembler = new LandingPageAssembler();
        $urn = 'urn:nbn:de:bsz:15-qucosa-64507';

        $hits = [
            [
                '_source' => [
                    'title' => ['GAIR-Mitteilungen'],
                    'identifier' => ['qucosa-11116', 'UBL-MIG-11116', 'urn:nbn:de:bsz:15-qucosa-154766', $urn],
                    'doctype' => 'issue',
                ],
            ],
        ];

        $this->assertSame('GAIR-Mitteilungen', $assembler->pickOwnTitleFromSearchHits($hits, $urn));
    }

    /**
     * Reproduces qucosa-35005: the multivolume_work container carries its own
     * distinct URN *and* the shared/inherited one, so the urn-count-1 rule
     * never matches it. doctype must be used as the fallback discriminator (#2039).
     */
    public function testPickOwnTitleFromSearchHitsFallsBackToDoctypeForMultivolumeWork()
    {
        $assembler = new LandingPageAssembler();
        $urn = 'urn:nbn:de:bsz:15-qucosa2-334170';

        $hits = [
            [
                '_source' => [
                    'title' => ['Tagungsband'],
                    'identifier' => ['qucosa-33421', 'UBL-19-595', $urn, 'urn:nbn:de:bsz:15-qucosa2-334215'],
                    'doctype' => 'proceeding',
                ],
            ],
            [
                '_source' => [
                    'title' => ['9. Leipziger Tierärztekongress', '[18. bis 20. Januar 2018]'],
                    'identifier' => ['qucosa-33417', 'UBL-19-591', 'urn:nbn:de:bsz:15-qucosa2-342569', $urn],
                    'doctype' => 'multivolume_work',
                ],
            ],
        ];

        $this->assertSame('9. Leipziger Tierärztekongress', $assembler->pickOwnTitleFromSearchHits($hits, $urn));
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
     * filterEmbeddedRelations() drops exactly the relations getMetadataHtml()
     * reports as embedded — no doctype guessing, since getMetadataHtml() now
     * knows precisely what it rendered (#2039 follow-up: field-order parity
     * with legacy replaced the old doctype-heuristic filter).
     */
    public function testFilterEmbeddedRelationsDropsOnlyReportedRelations()
    {
        $assembler = new LandingPageAssembler();
        $items = [
            ['relation' => 'host', 'url' => 'https://example.org/host', 'title' => 'Journal'],
            ['relation' => 'series', 'url' => 'https://example.org/series', 'title' => 'Series'],
        ];

        $this->assertSame(
            [['relation' => 'series', 'url' => 'https://example.org/series', 'title' => 'Series']],
            $assembler->filterEmbeddedRelations($items, ['host' => true, 'series' => false])
        );
        $this->assertSame($items, $assembler->filterEmbeddedRelations($items, ['host' => false, 'series' => false]));
        $this->assertSame([], $assembler->filterEmbeddedRelations($items, ['host' => true, 'series' => true]));
    }

    /**
     * firstByRelation() picks the first entry of the requested relation and
     * ignores others — used by getMetadataHtml() to find the one host/series
     * entry to splice into the <dl> at the legacy field-order position.
     */
    public function testFirstByRelationPicksMatchingEntry()
    {
        $assembler = new LandingPageAssembler();
        $method = new \ReflectionMethod(LandingPageAssembler::class, 'firstByRelation');
        $method->setAccessible(true);

        $items = [
            ['relation' => 'series', 'title' => 'Series'],
            ['relation' => 'host', 'title' => 'Journal'],
        ];

        $this->assertSame(['relation' => 'host', 'title' => 'Journal'], $method->invoke($assembler, $items, 'host'));
        $this->assertSame(['relation' => 'series', 'title' => 'Series'], $method->invoke($assembler, $items, 'series'));
        $this->assertNull($method->invoke($assembler, $items, 'constituent'));
        $this->assertNull($method->invoke($assembler, [], 'host'));
    }

    /**
     * renderEmbeddedParentItemRow() must produce the same <dt>/<dd> shape the
     * standalone parent-link partial used to, so splicing it into the <dl>
     * (#2039 order-parity fix) is visually identical to the block it replaces.
     */
    public function testRenderEmbeddedParentItemRowLinksWhenUrlPresent()
    {
        $assembler = new LandingPageAssembler();
        $method = new \ReflectionMethod(LandingPageAssembler::class, 'renderEmbeddedParentItemRow');
        $method->setAccessible(true);

        $html = $method->invoke($assembler, [
            'relationLabel' => 'Erschienen in',
            'title' => 'Archiv für Epigraphik',
            'url' => 'https://nbn-resolving.de/urn:nbn:de:bsz:15-qucosa2-809604',
        ]);

        $this->assertSame(
            '<dt>Erschienen in</dt><dd><a href="https://nbn-resolving.de/urn:nbn:de:bsz:15-qucosa2-809604">'
                . 'Archiv für Epigraphik</a></dd>',
            $html
        );
    }

    public function testRenderEmbeddedParentItemRowUnlinkedWhenNoUrl()
    {
        $assembler = new LandingPageAssembler();
        $method = new \ReflectionMethod(LandingPageAssembler::class, 'renderEmbeddedParentItemRow');
        $method->setAccessible(true);

        $html = $method->invoke($assembler, [
            'relationLabel' => 'Schriftenreihe',
            'title' => 'Series Title',
            'url' => null,
        ]);

        $this->assertSame('<dt>Schriftenreihe</dt><dd>Series Title</dd>', $html);
    }

    /**
     * htmlspecialchars() must be applied to attacker-controlled title/url
     * (sourced from public MODS/ES data) so the embedded row can't break out
     * of its <dd> into markup.
     */
    public function testRenderEmbeddedParentItemRowEscapesTitleAndUrl()
    {
        $assembler = new LandingPageAssembler();
        $method = new \ReflectionMethod(LandingPageAssembler::class, 'renderEmbeddedParentItemRow');
        $method->setAccessible(true);

        $html = $method->invoke($assembler, [
            'relationLabel' => 'Erschienen in',
            'title' => '<script>alert(1)</script>',
            'url' => 'https://example.org/?a=1&b=2',
        ]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('https://example.org/?a=1&amp;b=2', $html);
    }

    /**
     * resolveEmbeddableParentItems() must only pre-embed 'host' for the
     * doctypes whose Quellenangabe wrap row actually consumes {field:host_url}
     * (article, in_proceeding — EmbedHostLinkInQuellenangabeUpdate). Every
     * other doctype's host relatedItem is not consumed by any wrap row, so
     * it must still come back as a splice candidate — reproduces a bug where
     * doctype was dropped from the guard and e.g. contained_work's host link
     * was silently discarded (not spliced, not in prose, filtered from the
     * bottom block).
     */
    public function testResolveEmbeddableParentItemsEmbedsHostOnlyForWrapConsumingDoctypes()
    {
        $assembler = new LandingPageAssembler();
        $method = new \ReflectionMethod(LandingPageAssembler::class, 'resolveEmbeddableParentItems');
        $method->setAccessible(true);

        $parentItems = [['relation' => 'host', 'url' => 'https://example.org/host', 'title' => 'Journal']];
        $metadata = ['original_title' => ['Article Title'], 'type' => ['article']];

        [$hostItem, , $embedded] = $method->invoke($assembler, $parentItems, 'https://example.org/host', $metadata);
        $this->assertNull($hostItem);
        $this->assertTrue($embedded['host']);

        $metadata['type'] = ['contained_work'];
        [$hostItem, , $embedded] = $method->invoke($assembler, $parentItems, 'https://example.org/host', $metadata);
        $this->assertSame($parentItems[0], $hostItem);
        $this->assertFalse($embedded['host']);
    }

    public function testResolveEmbeddableParentItemsKeepsHostWhenProseGuardsUnmet()
    {
        $assembler = new LandingPageAssembler();
        $method = new \ReflectionMethod(LandingPageAssembler::class, 'resolveEmbeddableParentItems');
        $method->setAccessible(true);

        $parentItems = [['relation' => 'host', 'url' => 'https://example.org/host', 'title' => 'Journal']];

        [$hostItem] = $method->invoke($assembler, $parentItems, '', ['type' => ['article']]);
        $this->assertSame($parentItems[0], $hostItem);

        [$hostItem] = $method->invoke(
            $assembler,
            $parentItems,
            'https://example.org/host',
            ['type' => ['article'], 'original_title' => ['']]
        );
        $this->assertSame($parentItems[0], $hostItem);
    }
}
