<?php

namespace EWW\Dpf\Tests\Unit\Services\Metadata;

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
use EWW\Dpf\Services\Metadata\MetadataExtractor;
use EWW\Dpf\Services\Metadata\MetadataMappingRepository;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class MetadataExtractorTest extends UnitTestCase
{
    protected function loadDocument(): MetsDocument
    {
        $xml = file_get_contents(__DIR__ . '/Fixtures/mets_article.xml');
        return MetsDocument::fromXmlString($xml, 'http://example.org/mets');
    }

    /**
     * @param array $rules
     * @return MetadataMappingRepository
     */
    protected function stubRepository(array $rules): MetadataMappingRepository
    {
        $stub = $this->getMockBuilder(MetadataMappingRepository::class)
            ->setMethods(['findExtractionRules'])
            ->getMock();
        $stub->method('findExtractionRules')->willReturn($rules);
        return $stub;
    }

    protected function rule(string $indexName, string $xpath, array $overrides = []): array
    {
        return array_merge([
            'index_name' => $indexName,
            'xpath' => $xpath,
            'xpath_sorting' => '',
            'is_sortable' => 0,
            'default_value' => '',
            'format' => 1,
        ], $overrides);
    }

    public function testXPathRuleExtractsValue()
    {
        $extractor = new MetadataExtractor($this->stubRepository([
            $this->rule('title', './mods:titleInfo/mods:title'),
        ]));
        $metadata = $extractor->extract($this->loadDocument(), 1);
        $this->assertEquals(['Ein Testdokument'], $metadata['title']);
    }

    public function testXPathRuleExtractsMultipleValues()
    {
        $extractor = new MetadataExtractor($this->stubRepository([
            $this->rule('identifier', './mods:identifier'),
        ]));
        $metadata = $extractor->extract($this->loadDocument(), 1);
        $this->assertEquals(
            ['urn:nbn:de:bsz:14-qucosa2-123456', '10.25366/2020.42'],
            $metadata['identifier']
        );
    }

    public function testSlubNamespaceXPathWorks()
    {
        $extractor = new MetadataExtractor($this->stubRepository([
            $this->rule('project', './mods:extension/slub:info/slub:project'),
        ]));
        $metadata = $extractor->extract($this->loadDocument(), 1);
        $this->assertEquals(['Testprojekt'], $metadata['project']);
    }

    public function testDefaultValueAppliesWhenXPathEmpty()
    {
        $extractor = new MetadataExtractor($this->stubRepository([
            $this->rule('owner', './mods:nonexistent', ['default_value' => 'qucosa:ubl']),
        ]));
        $metadata = $extractor->extract($this->loadDocument(), 1);
        $this->assertEquals(['qucosa:ubl'], $metadata['owner']);
    }

    public function testFormatlessDefaultValueRow()
    {
        $extractor = new MetadataExtractor($this->stubRepository([
            $this->rule('rights_info', '', ['format' => 0, 'default_value' => 'Open Access']),
        ]));
        $metadata = $extractor->extract($this->loadDocument(), 1);
        $this->assertEquals(['Open Access'], $metadata['rights_info']);
    }

    public function testSortingCompanionFromXPathSorting()
    {
        $extractor = new MetadataExtractor($this->stubRepository([
            $this->rule('title', './mods:titleInfo/mods:title', [
                'is_sortable' => 1,
                'xpath_sorting' => './mods:titleInfo/mods:subTitle',
            ]),
        ]));
        $metadata = $extractor->extract($this->loadDocument(), 1);
        $this->assertEquals('Untertitel', $metadata['title_sorting'][0]);
    }

    public function testSortingCompanionFallsBackToValue()
    {
        $extractor = new MetadataExtractor($this->stubRepository([
            $this->rule('title', './mods:titleInfo/mods:title', ['is_sortable' => 1]),
        ]));
        $metadata = $extractor->extract($this->loadDocument(), 1);
        $this->assertEquals('Ein Testdokument', $metadata['title_sorting'][0]);
    }

    public function testTitleFallsBackToEmptyString()
    {
        $extractor = new MetadataExtractor($this->stubRepository([]));
        $metadata = $extractor->extract($this->loadDocument(), 1);
        $this->assertSame('', $metadata['title'][0]);
        $this->assertSame('', $metadata['title_sorting'][0]);
    }

    public function testTypeComesFromLogicalDiv()
    {
        $extractor = new MetadataExtractor($this->stubRepository([]));
        $metadata = $extractor->extract($this->loadDocument(), 1);
        $this->assertEquals(['article'], $metadata['type']);
    }

    public function testModsCoreSeedingRunsBeforeRules()
    {
        $extractor = new MetadataExtractor($this->stubRepository([]));
        $metadata = $extractor->extract($this->loadDocument(), 1);
        $this->assertEquals('Musterfrau, Erika' . chr(31) . 'http://d-nb.info/gnd/123456789', $metadata['author'][0]);
        $this->assertEquals('Mustermann, Max', $metadata['author'][1]);
        $this->assertEquals(['2020-05-12'], $metadata['year']);
    }

    public function testUnsupportedDmdSecIsSkippedForSupportedOne()
    {
        // Fixture toplevel div carries DMDID="DMD_TEI DMD_000" — TEIHDR must
        // be skipped, MODS processed, then extraction stops (first-break).
        $extractor = new MetadataExtractor($this->stubRepository([
            $this->rule('title', './mods:titleInfo/mods:title'),
        ]));
        $metadata = $extractor->extract($this->loadDocument(), 1);
        $this->assertEquals(['Ein Testdokument'], $metadata['title']);
        $this->assertEquals(['METS'], $metadata['document_format']);
    }

    public function testReturnsEmptyArrayWithoutCpid()
    {
        $extractor = new MetadataExtractor($this->stubRepository([]));
        $this->assertSame([], $extractor->extract($this->loadDocument(), 0));
    }

    public function testReturnsEmptyArrayWhenNoSupportedDmdSec()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mets:mets xmlns:mets="http://www.loc.gov/METS/" OBJID="qucosa:1">
    <mets:dmdSec ID="DMD_TEI">
        <mets:mdWrap MDTYPE="TEIHDR"><mets:xmlData><x/></mets:xmlData></mets:mdWrap>
    </mets:dmdSec>
    <mets:structMap TYPE="LOGICAL">
        <mets:div ID="LOG_0000" DMDID="DMD_TEI" TYPE="article"/>
    </mets:structMap>
</mets:mets>
XML;
        $document = MetsDocument::fromXmlString($xml);
        $extractor = new MetadataExtractor($this->stubRepository([]));
        $this->assertSame([], $extractor->extract($document, 1));
    }
}
