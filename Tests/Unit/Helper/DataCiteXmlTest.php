<?php
namespace EWW\Dpf\Tests\Unit\Helper;

use EWW\Dpf\Helper\DataCiteXml;
use PHPUnit\Framework\TestCase;

class DataCiteXmlTest extends TestCase
{
    private function mets(string $body): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/">
{$body}
</root>
XML;
    }

    private function loadDataCite(string $metsXml): \DOMXPath
    {
        $xml = DataCiteXml::convertFromMetsXml($metsXml);
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('dc', 'http://datacite.org/schema/kernel-4');
        return $xpath;
    }

    public function testAmpersandInTitleDoesNotBreakXml(): void
    {
        $mets = $this->mets(<<<XML
<mods:identifier type="doi">10.5281/zenodo.1234</mods:identifier>
<mods:titleInfo usage="primary">
    <mods:title>Cats &amp; Dogs</mods:title>
</mods:titleInfo>
XML
        );

        $xpath = $this->loadDataCite($mets);
        $this->assertSame('Cats & Dogs', $xpath->query('//dc:titles/dc:title')->item(0)->nodeValue);
    }

    public function testDoiPrefixIsStripped(): void
    {
        $mets = $this->mets(
            '<mods:identifier type="doi">https://doi.org/10.5281/zenodo.1234</mods:identifier>'
        );

        $xpath = $this->loadDataCite($mets);
        $this->assertSame('10.5281/zenodo.1234', $xpath->query('//dc:identifier')->item(0)->nodeValue);
    }

    public function testMissingDoiYieldsEmptyIdentifierNotPlaceholder(): void
    {
        $xpath = $this->loadDataCite($this->mets(''));

        $this->assertSame('', $xpath->query('//dc:identifier')->item(0)->nodeValue);
    }

    public function testAllSubjectClassificationNodesAreCollected(): void
    {
        $mets = $this->mets(<<<XML
<mods:classification authority="z">alpha, beta</mods:classification>
<mods:classification authority="z">gamma</mods:classification>
XML
        );

        $xpath = $this->loadDataCite($mets);
        $subjects = [];
        foreach ($xpath->query('//dc:subjects/dc:subject') as $node) {
            $subjects[] = $node->nodeValue;
        }
        $this->assertSame(['alpha', 'beta', 'gamma'], $subjects);
    }

    public function testKnownDocumentTypeMapsToResourceTypeGeneral(): void
    {
        $xpath = $this->loadDataCite($this->mets('<slub:documentType>doctoral_thesis</slub:documentType>'));

        $resourceType = $xpath->query('//dc:resourceType')->item(0);
        $this->assertSame('doctoral_thesis', $resourceType->nodeValue);
        $this->assertSame('Dissertation', $resourceType->getAttribute('resourceTypeGeneral'));
    }

    public function testUnknownDocumentTypeFallsBackToText(): void
    {
        $xpath = $this->loadDataCite($this->mets('<slub:documentType>something_new</slub:documentType>'));

        $resourceType = $xpath->query('//dc:resourceType')->item(0);
        $this->assertSame('Text', $resourceType->getAttribute('resourceTypeGeneral'));
    }

    public function testEditorIsListedAsContributorNotCreator(): void
    {
        $mets = $this->mets(<<<XML
<mods:name type="personal">
    <mods:namePart type="given">Jane</mods:namePart>
    <mods:namePart type="family">Author</mods:namePart>
    <mods:role><mods:roleTerm type="code">aut</mods:roleTerm></mods:role>
</mods:name>
<mods:name type="personal">
    <mods:namePart type="given">John</mods:namePart>
    <mods:namePart type="family">Editor</mods:namePart>
    <mods:role><mods:roleTerm type="code">edt</mods:roleTerm></mods:role>
</mods:name>
XML
        );

        $xpath = $this->loadDataCite($mets);
        $this->assertSame(1, $xpath->query('//dc:creators/dc:creator')->length);
        $this->assertSame('Author, Jane', $xpath->query('//dc:creators/dc:creator/dc:creatorName')->item(0)->nodeValue);

        $this->assertSame(1, $xpath->query('//dc:contributors/dc:contributor')->length);
        $this->assertSame(
            'Editor',
            $xpath->query('//dc:contributors/dc:contributor')->item(0)->getAttribute('contributorType')
        );
    }

    public function testCorporateEditorBecomesCreatorWhenNoPersonalAuthor(): void
    {
        $mets = $this->mets(<<<XML
<mods:name type="corporate">
    <mods:namePart>Unfallkasse Sachsen</mods:namePart>
    <mods:role><mods:roleTerm type="code">edt</mods:roleTerm></mods:role>
</mods:name>
<mods:name type="corporate">
    <mods:namePart>SLUB Dresden</mods:namePart>
    <mods:role><mods:roleTerm type="code">prv</mods:roleTerm></mods:role>
</mods:name>
XML
        );

        $xpath = $this->loadDataCite($mets);
        $creator = $xpath->query('//dc:creators/dc:creator')->item(0);
        $this->assertNotNull($creator);
        $this->assertSame('Unfallkasse Sachsen', $xpath->query('.//dc:creatorName', $creator)->item(0)->nodeValue);
        $this->assertSame(
            'Organizational',
            $xpath->query('.//dc:creatorName', $creator)->item(0)->getAttribute('nameType')
        );
    }

    public function testProviderOnlyCorporateNameDoesNotBecomeCreator(): void
    {
        $mets = $this->mets(
            '<mods:name type="corporate">
                <mods:namePart>SLUB Dresden</mods:namePart>
                <mods:role><mods:roleTerm type="code">prv</mods:roleTerm></mods:role>
            </mods:name>'
        );

        $xpath = $this->loadDataCite($mets);
        $this->assertSame(0, $xpath->query('//dc:creators/dc:creator')->length);
    }

    public function testPublicationDateBecomesIssuedDate(): void
    {
        $mets = $this->mets(
            '<mods:originInfo eventType="publication"><mods:dateIssued>2024-03-15</mods:dateIssued></mods:originInfo>'
        );

        $xpath = $this->loadDataCite($mets);
        $date = $xpath->query('//dc:dates/dc:date')->item(0);
        $this->assertSame('2024-03-15', $date->nodeValue);
        $this->assertSame('Issued', $date->getAttribute('dateType'));
        $this->assertSame('2024', $xpath->query('//dc:publicationYear')->item(0)->nodeValue);
    }

    public function testUrnIdentifierBecomesAlternateIdentifier(): void
    {
        $mets = $this->mets('<mods:identifier type="urn">urn:nbn:de:bsz:14-qucosa-12345</mods:identifier>');

        $xpath = $this->loadDataCite($mets);
        $alt = $xpath->query('//dc:alternateIdentifiers/dc:alternateIdentifier')->item(0);
        $this->assertSame('urn:nbn:de:bsz:14-qucosa-12345', $alt->nodeValue);
        $this->assertSame('URN', $alt->getAttribute('alternateIdentifierType'));
    }

    public function testRelatedItemIdentifierIsNotAlsoAnAlternateIdentifier(): void
    {
        $mets = $this->mets(<<<XML
<mods:identifier type="urn">urn:nbn:de:bsz:14-qucosa-12345</mods:identifier>
<mods:relatedItem type="host">
    <mods:identifier type="issn">1234-5678</mods:identifier>
</mods:relatedItem>
XML
        );

        $xpath = $this->loadDataCite($mets);
        $this->assertSame(1, $xpath->query('//dc:alternateIdentifiers/dc:alternateIdentifier')->length);
        $this->assertSame(
            'urn:nbn:de:bsz:14-qucosa-12345',
            $xpath->query('//dc:alternateIdentifiers/dc:alternateIdentifier')->item(0)->nodeValue
        );
    }

    public function testHostRelatedItemBecomesRelatedIdentifier(): void
    {
        $mets = $this->mets(<<<XML
<mods:relatedItem type="host">
    <mods:identifier type="issn">1234-5678</mods:identifier>
</mods:relatedItem>
XML
        );

        $xpath = $this->loadDataCite($mets);
        $related = $xpath->query('//dc:relatedIdentifiers/dc:relatedIdentifier')->item(0);
        $this->assertSame('1234-5678', $related->nodeValue);
        $this->assertSame('ISSN', $related->getAttribute('relatedIdentifierType'));
        $this->assertSame('IsPartOf', $related->getAttribute('relationType'));
    }

    public function testAbstractBecomesDescription(): void
    {
        $mets = $this->mets('<mods:abstract xml:lang="en">A short summary.</mods:abstract>');

        $xpath = $this->loadDataCite($mets);
        $description = $xpath->query('//dc:descriptions/dc:description')->item(0);
        $this->assertSame('A short summary.', $description->nodeValue);
        $this->assertSame('Abstract', $description->getAttribute('descriptionType'));
    }
}
