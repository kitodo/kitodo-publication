<?php
namespace EWW\Dpf\Tests\Unit\Services\Api;

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

use Nimut\TestingFramework\TestCase\UnitTestCase;

use EWW\Dpf\Domain\Model\MetadataGroup;
use EWW\Dpf\Domain\Model\MetadataObject;
use EWW\Dpf\Services\Api\GroupNode;
use EWW\Dpf\Services\Api\InternalXml;

class InternalXmlTest extends UnitTestCase
{
    /**
     * @var InternalXml
     */
    private $internalXml = null;

    protected function setUp()
    {
        $this->internalXml = new InternalXml();
        $this->internalXml->setRootNode('//mods:mods/');
        $this->internalXml->setNamespaces(
            'k=http://www.kitodo.org;mods=http://www.loc.gov/mods/v3;slub=http://slub-dresden.de/;mets=http://www.loc.gov/METS/;foaf=http://xmlns.com/foaf/0.1/;rdf=http://www.w3.org/1999/02/22-rdf-syntax-ns#;person=http://www.w3.org/ns/person#'
        );
    }

    /**
     * @test
     */
    public function testFindGroup()
    {
        $xml = '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:person="http://www.w3.org/ns/person#" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd" version="3.7">
                    <mods:language>
                        <mods:languageTerm authority="iso639-2b" type="code">ger</mods:languageTerm>
                    </mods:language>
                    <mods:titleInfo usage="primary" lang="ger">
                        <mods:title>Ein Titel</mods:title>
                    </mods:titleInfo>
                    <mods:name type="personal" ID="QUCOSA_001">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname1</mods:namePart>
                        <mods:namePart type="given">Vorname1</mods:namePart>
                    </mods:name>
                    <mods:name type="personal" ID="QUCOSA_001">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname2</mods:namePart>
                        <mods:namePart type="given">Vorname2</mods:namePart>
                    </mods:name>
                    <mods:extension>
                        <foaf:Person rdf:about="QUCOSA_001">
                            <foaf:mbox>Mail</foaf:mbox>
                        </foaf:Person>
                    </mods:extension>
                    <mods:relatedItem type="original">
                        <mods:titleInfo>
                            <mods:title>Quellenangaben</mods:title>
                        </mods:titleInfo>
                    </mods:relatedItem>
                    <mods:relatedItem type="otherVersion">
                        <mods:titleInfo>
                            <mods:title>Beziehung</mods:title>
                        </mods:titleInfo>
                    </mods:relatedItem>
                </mods:mods>';

        $expectedXml = '<mods:name type="personal" ID="QUCOSA_001">
                            <mods:role>
                                <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                            </mods:role>
                            <mods:namePart type="family">Nachname2</mods:namePart>
                            <mods:namePart type="given">Vorname2</mods:namePart>
                        </mods:name>';

        $expectedExtensionXml = '<foaf:Person rdf:about="QUCOSA_001">
                                    <foaf:mbox>Mail</foaf:mbox>
                                </foaf:Person>';

        libxml_use_internal_errors(true);

        $expectedDom = new \DOMDocument();
        $expectedDom->loadXML($expectedXml);

        $expectedExtensionDom = new \DOMDocument();
        $expectedExtensionDom->loadXML($expectedExtensionXml);

        $this->internalXml->setXml($xml);

        // Try getting an existing group
        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:name[@type="personal"]');
        $metadataGroup->setMappingForReading('');
        $metadataGroup->setModsExtensionMapping('mods:extension/foaf:Person');
        $metadataGroup->setModsExtensionReference('rdf:about');

        $group = $this->internalXml->findGroup($metadataGroup, 1);

        $this->assertTrue($group instanceof GroupNode);
        $this->assertTrue($group->getMainNode() instanceof \DOMNode);
        $this->assertXmlStringEqualsXmlString(
            $expectedDom->saveXML(), $group->getMainNode()->ownerDocument->saveXML($group->getMainNode())
        );

        $this->assertTrue($group->getExtensionNode() instanceof \DOMNode);
        $this->assertXmlStringEqualsXmlString(
            $expectedExtensionDom->saveXML(), $group->getExtensionNode()->ownerDocument->saveXML($group->getExtensionNode())
        );

        // Try getting a non existing index of an existing group
        $group = $this->internalXml->findGroup($metadataGroup, 2);
        $this->assertTrue($group === null);

        // Try getting a non existing group
        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:name[@type="institution"]');
        $metadataGroup->setMappingForReading('');

        $group = $this->internalXml->findGroup($metadataGroup);
        $this->assertTrue($group === null);

        // Try getting an existing group excluding a given type attribute (mapping for reading test)
        $exp = '<mods:relatedItem type="otherVersion">
                        <mods:titleInfo>
                            <mods:title>Beziehung</mods:title>
                        </mods:titleInfo>
                    </mods:relatedItem>';

        $expectedDom = new \DOMDocument();
        $expectedDom->loadXML($exp);

        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:relatedItem[not(@type="original")]');
        $metadataGroup->setMappingForReading('');

        $group = $this->internalXml->findGroup($metadataGroup, 0);

        $this->assertTrue($group->getMainNode() instanceof \DOMElement);
        $this->assertXmlStringEqualsXmlString($expectedDom->saveXML(), $group->getMainNode()->ownerDocument->saveXML($group->getMainNode()));
    }

    /**
     * @test
     */
    public function testFindField()
    {
        $xml = '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:person="http://www.w3.org/ns/person#" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd" version="3.7">
                    <mods:language>
                        <mods:languageTerm authority="iso639-2b" type="code">ger</mods:languageTerm>
                    </mods:language>
                    <mods:titleInfo usage="primary" lang="ger">
                        <mods:title>Ein Titel</mods:title>
                    </mods:titleInfo>
                    <mods:name type="personal" ID="QUCOSA_001">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname</mods:namePart>
                        <mods:namePart type="given">Vorname</mods:namePart>
                    </mods:name>
                    <mods:extension>
                        <foaf:Person rdf:about="QUCOSA_001">
                            <foaf:mbox>Mail</foaf:mbox>
                        </foaf:Person>
                    </mods:extension>
                </mods:mods>';

        $expectedXml = '<mods:namePart type="family">Nachname</mods:namePart>';
        $expectedExtensionXml = '<foaf:mbox>Mail</foaf:mbox>';

        libxml_use_internal_errors(true);

        $expectedDom = new \DOMDocument();
        $expectedDom->loadXML($expectedXml);

        $expectedExtensionDom = new \DOMDocument();
        $expectedExtensionDom->loadXML($expectedExtensionXml);

        $this->internalXml->setXml($xml);

        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:name[@type="personal"]');
        $metadataGroup->setMappingForReading('');
        $metadataGroup->setModsExtensionMapping('mods:extension/foaf:Person');
        $metadataGroup->setModsExtensionReference('rdf:about');

        // Try getting an existing field
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('mods:namePart[@type="family"]');
        $group = $this->internalXml->findGroup($metadataGroup, 0);
        $field = $this->internalXml->findField($group, $metadataObject);
        $this->assertXmlStringEqualsXmlString($expectedDom->saveXML(), $field->ownerDocument->saveXML($field));

        // Try getting an existing extension field
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('foaf:mbox');
        $group = $this->internalXml->findGroup($metadataGroup, 0);
        $field = $this->internalXml->findField($group, $metadataObject);
        $this->assertXmlStringEqualsXmlString($expectedExtensionDom->saveXML(), $field->ownerDocument->saveXML($field));

        // Try getting a non existing index of an existing field
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('mods:namePart[@type="family"]');
        $group = $this->internalXml->findGroup($metadataGroup, 0);
        $field = $this->internalXml->findField($group, $metadataObject, 2);
        $this->assertTrue($field === false);

        // Try getting a non existing field
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('mods:namePart[@type="institution"]');
        $group = $this->internalXml->findGroup($metadataGroup, 0);
        $field = $this->internalXml->findField($group, $metadataObject);
        $this->assertTrue($field === false);
    }

    /**
     * @test
     */
    public function testSetField()
    {
        $xml = '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:person="http://www.w3.org/ns/person#" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd" version="3.7">
                    <mods:language>
                        <mods:languageTerm authority="iso639-2b" type="code">ger</mods:languageTerm>
                    </mods:language>
                    <mods:titleInfo usage="primary" lang="ger">
                        <mods:title>Ein Titel</mods:title>
                    </mods:titleInfo>
                    <mods:name type="personal" ID="QUCOSA_001">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname</mods:namePart>
                        <mods:namePart type="given">Vorname</mods:namePart>
                    </mods:name>
                    <mods:relatedItem type="original">
                        <mods:titleInfo>
                            <mods:title>Quellenangaben</mods:title>
                        </mods:titleInfo>
                    </mods:relatedItem>
                </mods:mods>';

        $expectedXml = '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:person="http://www.w3.org/ns/person#" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd" version="3.7">
                    <mods:language>
                        <mods:languageTerm authority="iso639-2b" type="code">ger</mods:languageTerm>
                    </mods:language>
                    <mods:titleInfo usage="primary" lang="eng">
                        <mods:title>A new title</mods:title>
                    </mods:titleInfo>
                    <mods:name type="personal" ID="QUCOSA_001">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname</mods:namePart>
                        <mods:namePart type="given">Vorname</mods:namePart>
                    </mods:name>
                    <mods:relatedItem type="original">
                        <mods:titleInfo>
                            <mods:title>Quellenangaben</mods:title>
                        </mods:titleInfo>
                    </mods:relatedItem>
                    <mods:relatedItem type="original" />
                    <mods:relatedItem type="original" lang="ger" />
                    <mods:name type="personal" />
                </mods:mods>';

        libxml_use_internal_errors(true);
        $expectedDom = new \DOMDocument();
        $expectedDom->loadXML($expectedXml);

        $this->internalXml->setXml($xml);

        // Set a field in an existing group
        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:titleInfo[@usage="primary"]');
        $metadataGroup->setMappingForReading('');
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('mods:title');
        $group = $this->internalXml->findGroup($metadataGroup, 0);
        $this->internalXml->setField($group, $metadataObject, 0, 'A new title');

        // Setting non existing fields in an existing group
        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:titleInfo[@usage="primary"]');
        $metadataGroup->setMappingForReading('');
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('@lang');
        $group = $this->internalXml->findGroup($metadataGroup, 0);
        $this->internalXml->setField($group, $metadataObject, 10, 'eng');

        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:relatedItem[@type="original"]');
        $metadataGroup->setMappingForReading('');
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('mods:titleInfo/mods:title');
        $group = $this->internalXml->findGroup($metadataGroup, 0);
        $this->internalXml->setField($group, $metadataObject, 1, 'Neue Quellenangabe');

        // Set a field in a new group
        $group = $this->internalXml->findGroup($metadataGroup, 1);
        if (!$group) {
            $group = $this->internalXml->addGroup($metadataGroup);
            $field = $this->internalXml->findField($group, $metadataObject, 0);
            $this->assertTrue($field === false);
        }
        $this->internalXml->setField($group, $metadataObject, 0, 'Extra Quellenangaben');

        // Setting fields in a new group
        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:relatedItem[@type="original"]');
        $metadataGroup->setMappingForReading('');
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('@lang');
        $group = $this->internalXml->findGroup($metadataGroup, 2);
        if (!$group) {
            $group = $this->internalXml->addGroup($metadataGroup);
        }
        $this->internalXml->setField($group, $metadataObject, 0, 'ger');
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('mods:titleInfo/mods:title');
        $this->internalXml->setField($group, $metadataObject, 0, 'Zusatz Quellenangabe');

        // Setting fields in another new group
        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:name[@type="personal"]');
        $metadataGroup->setMappingForReading('');
        $metadataGroup->setModsExtensionMapping('mods:extension/foaf:Person');
        $metadataGroup->setModsExtensionReference('rdf:about');
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('mods:namePart[@type="family"]');

        $group = $this->internalXml->addGroup($metadataGroup);
        $this->internalXml->setField($group, $metadataObject, 0, 'TESTTEST');

        $this->assertXmlStringEqualsXmlString($expectedDom->saveXML(), $this->internalXml->getXml());
    }

    /**
     * @test
     */
    public function testAddGroup()
    {
        $xml = '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:person="http://www.w3.org/ns/person#" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd" version="3.7">
                    <mods:language>
                        <mods:languageTerm authority="iso639-2b" type="code">ger</mods:languageTerm>
                    </mods:language>
                    <mods:titleInfo usage="primary" lang="ger">
                        <mods:title>Ein Titel</mods:title>
                    </mods:titleInfo>
                    <mods:relatedItem type="original">
                       <mods:titleInfo>
                           <mods:title>Quellenangaben</mods:title>
                       </mods:titleInfo>
                    </mods:relatedItem>
                    <mods:name type="personal" ID="QUCOSA_001">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname</mods:namePart>
                        <mods:namePart type="given">Vorname</mods:namePart>
                    </mods:name>
                </mods:mods>';

        $expectedXml = '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:person="http://www.w3.org/ns/person#" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd" version="3.7">
                    <mods:language>
                        <mods:languageTerm authority="iso639-2b" type="code">ger</mods:languageTerm>
                    </mods:language>
                    <mods:titleInfo usage="primary" lang="ger">
                        <mods:title>Ein Titel</mods:title>
                    </mods:titleInfo>
                    <mods:relatedItem type="original">
                       <mods:titleInfo>
                           <mods:title>Quellenangaben</mods:title>
                       </mods:titleInfo>
                    </mods:relatedItem>
                    <mods:name type="personal" ID="QUCOSA_001">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname</mods:namePart>
                        <mods:namePart type="given">Vorname</mods:namePart>
                    </mods:name>
                    <mods:relatedItem type="original" />
                    <mods:relatedItem />
                    <mods:relatedItemTest>
                        <mods:item type="test">
                            <mods:element>
                              <mods:test>Test 100</mods:test>
                              <mods:test>Test 200</mods:test>
                            </mods:element>
                        </mods:item>
                    </mods:relatedItemTest>
                    <mods:relatedItem type="original" lang="ger">
                       <mods:titleInfo>
                           <mods:title>Gruppen-Titel</mods:title>
                       </mods:titleInfo>
                       <mods:titleInfo>
                           <mods:subtitle>Gruppen-Untertitel</mods:subtitle>
                       </mods:titleInfo>
                    </mods:relatedItem>
                     <mods:name type="personal">
                        <mods:namePart type="family">Nachname</mods:namePart>
                        <mods:namePart type="given">Vorname</mods:namePart>
                    </mods:name>
                </mods:mods>';

        libxml_use_internal_errors(true);
        $expectedDom = new \DOMDocument();
        $expectedDom->loadXML($expectedXml);

        $this->internalXml->setXml($xml);

        // Adding new groups without fields
        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:relatedItem[@type="original"]');
        $metadataGroup->setMappingForReading('');
        $this->internalXml->addGroup($metadataGroup);

        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:relatedItem');
        $metadataGroup->setMappingForReading('mods:relatedItem[not(@type="original")]');
        $this->internalXml->addGroup($metadataGroup);

        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:relatedItemTest/mods:item[@type="test"]/mods:element');
        $metadataGroup->setMappingForReading('');

        $fieldData = [];
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('mods:test');
        $fieldData[] = [
            'metadataObject' => $metadataObject,
            'value' => 'Test 100'
        ];
        $fieldData[] = [
            'metadataObject' => $metadataObject,
            'value' => 'Test 200'
        ];

        $g = $this->internalXml->addGroup($metadataGroup, $fieldData);

        // Add a new group with fields
        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:relatedItem[@type="original"]');
        $metadataGroup->setMappingForReading('');

        $fieldData = [];
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('mods:titleInfo/mods:title');
        $fieldData[] = [
            'metadataObject' => $metadataObject,
            'value' => 'Gruppen-Titel'
        ];

        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('mods:titleInfo/mods:subtitle');
        $fieldData[] = [
            'metadataObject' => $metadataObject,
            'value' => 'Gruppen-Untertitel'
        ];

        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('@lang');
        $fieldData[] = [
            'metadataObject' => $metadataObject,
            'value' => 'ger'
        ];

        $this->internalXml->addGroup($metadataGroup, $fieldData);


        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:name[@type="personal"]');

        $fieldData = [];
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('mods:namePart[@type="family"]');
        $fieldData[] = [
            'metadataObject' => $metadataObject,
            'value' => 'Nachname'
        ];

        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('mods:namePart[@type="given"]');
        $fieldData[] = [
            'metadataObject' => $metadataObject,
            'value' => 'Vorname'
        ];

        $this->internalXml->addGroup($metadataGroup, $fieldData);


        $this->assertXmlStringEqualsXmlString($expectedDom->saveXML(), $this->internalXml->getXml());
    }

    /**
     * @test
     */
    public function testAddField()
    {
        $xml = '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:person="http://www.w3.org/ns/person#" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd" version="3.7">
                    <mods:language>
                        <mods:languageTerm authority="iso639-2b" type="code">ger</mods:languageTerm>
                    </mods:language>
                    <mods:titleInfo usage="primary" lang="ger">
                        <mods:title>Ein Titel</mods:title>
                    </mods:titleInfo>
                    <mods:relatedItem type="original">
                       <mods:titleInfo>
                           <mods:title>Quellenangaben</mods:title>
                       </mods:titleInfo>
                       <mods:titleInfo>
                           <mods:subTitle>Untertitel</mods:subTitle>
                       </mods:titleInfo>
                    </mods:relatedItem>
                    <mods:name type="personal" ID="QUCOSA_001">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname</mods:namePart>
                        <mods:namePart type="given">Vorname</mods:namePart>
                    </mods:name>
                    <mods:extension>
                        <foaf:Person rdf:about="QUCOSA_001">
                            <foaf:birthPlace>QUCOSA_001</foaf:birthPlace>
                        </foaf:Person>
                    </mods:extension>
                    <mods:name type="personal">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname</mods:namePart>
                        <mods:namePart type="given">Vorname</mods:namePart>
                    </mods:name>
                    <mods:relatedItemTest>
                        <mods:item type="test">
                            <mods:element />
                        </mods:item>
                    </mods:relatedItemTest>
                </mods:mods>';

        $expectedXml = '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:person="http://www.w3.org/ns/person#" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd" version="3.7">
                    <mods:language>
                        <mods:languageTerm authority="iso639-2b" type="code">ger</mods:languageTerm>
                    </mods:language>
                    <mods:titleInfo usage="primary" lang="ger">
                        <mods:title>Ein Titel</mods:title>
                    </mods:titleInfo>
                    <mods:relatedItem type="original">
                       <mods:titleInfo>
                           <mods:title>Quellenangaben</mods:title>
                       </mods:titleInfo>
                       <mods:titleInfo>
                           <mods:subTitle>Untertitel</mods:subTitle>
                       </mods:titleInfo>
                       <mods:titleInfo>
                           <mods:title>Eine zusätzliche Quellenangaben</mods:title>
                       </mods:titleInfo>
                    </mods:relatedItem>
                    <mods:name type="personal" ID="QUCOSA_001">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname</mods:namePart>
                        <mods:namePart type="given">Vorname</mods:namePart>
                    </mods:name>
                    <mods:extension>
                        <foaf:Person rdf:about="QUCOSA_001">
                            <foaf:birthPlace>QUCOSA_001</foaf:birthPlace>
                            <foaf:mbox>Mail QUCOSA_001</foaf:mbox>
                        </foaf:Person>
                    </mods:extension>
                    <mods:name type="personal" ID="QUCOSA_002">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname</mods:namePart>
                        <mods:namePart type="given">Vorname</mods:namePart>
                    </mods:name>
                    <mods:relatedItemTest>
                        <mods:item type="test">
                            <mods:element>
                                <mods:test>Test 100</mods:test>
                                <mods:test>Test 200</mods:test>
                           </mods:element>
                        </mods:item>
                    </mods:relatedItemTest>
                    <mods:relatedItem type="original">
                       <mods:titleInfo>
                           <mods:title>Eine weitere Quellenangabe</mods:title>
                       </mods:titleInfo>
                       <mods:titleInfo>
                           <mods:title>Noch eine Quellenangabe</mods:title>
                       </mods:titleInfo>
                    </mods:relatedItem>
                    <mods:extension>
                        <foaf:Person rdf:about="QUCOSA_002">
                            <foaf:mbox>Mail</foaf:mbox>
                        </foaf:Person>
                    </mods:extension>
                </mods:mods>';

        libxml_use_internal_errors(true);
        $expectedDom = new \DOMDocument();
        $expectedDom->loadXML($expectedXml);

        $this->internalXml->setXml($xml);

        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:relatedItem[@type="original"]');
        $metadataGroup->setMappingForReading('');
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('mods:titleInfo/mods:title');

        // Add a field to an existing group.
        $group = $this->internalXml->findGroup($metadataGroup, 0);
        $this->internalXml->addField($group, $metadataObject, "Eine zusätzliche Quellenangaben");

        // Add a field to a new group.
        $newGroup = $this->internalXml->addGroup($metadataGroup);
        $this->internalXml->addField($newGroup, $metadataObject, "Eine weitere Quellenangabe");
        $this->internalXml->addField($newGroup, $metadataObject, "Noch eine Quellenangabe");

        // Add an extension field to an existing group with existing extension fields.
        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:name[@type="personal"]');
        $metadataGroup->setMappingForReading('');
        $metadataGroup->setModsExtensionMapping('mods:extension/foaf:Person');
        $metadataGroup->setModsExtensionReference('rdf:about');
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('foaf:mbox');
        $metadataObject->setModsExtension(true);
        $group = $this->internalXml->findGroup($metadataGroup, 0);
        $this->internalXml->addField($group, $metadataObject, "Mail QUCOSA_001");

        // Add an extension field to an existing group.
        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:name[@type="personal"]');
        $metadataGroup->setMappingForReading('');
        $metadataGroup->setModsExtensionMapping('mods:extension/foaf:Person');
        $metadataGroup->setModsExtensionReference('rdf:about');
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('foaf:mbox');
        $metadataObject->setModsExtension(true);
        $group = $this->internalXml->findGroup($metadataGroup, 1);
        $this->internalXml->addField($group, $metadataObject, "Mail");

        // Adding fields to an existing group with complex path.
        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:relatedItemTest/mods:item/mods:element');
        $metadataGroup->setMappingForReading('');
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('mods:test');
        $group = $this->internalXml->findGroup($metadataGroup, 0);
        $this->internalXml->addField($group, $metadataObject, "Test 100");
        $this->internalXml->addField($group, $metadataObject, "Test 200");

        $this->assertXmlStringEqualsXmlString($expectedDom->saveXML(), $this->internalXml->getXml());
    }

    /**
     * @test
     */
    public function testRemoveField()
    {
        $xml = '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:person="http://www.w3.org/ns/person#" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd" version="3.7">
                    <mods:language>
                        <mods:languageTerm authority="iso639-2b" type="code">ger</mods:languageTerm>
                    </mods:language>
                    <mods:titleInfo usage="primary" lang="ger">
                        <mods:title>Ein Titel 1</mods:title>
                    </mods:titleInfo>
                    <mods:name type="personal" ID="QUCOSA_001">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname</mods:namePart>
                        <mods:namePart type="given">Vorname 1</mods:namePart>
                        <mods:namePart type="given">Vorname 2</mods:namePart>
                    </mods:name>
                    <mods:extension>
                        <foaf:Person rdf:about="QUCOSA_001">
                            <foaf:mbox>Mail</foaf:mbox>
                        </foaf:Person>
                    </mods:extension>
                </mods:mods>';

        $expectedXml = '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:person="http://www.w3.org/ns/person#" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd" version="3.7">
                    <mods:language>
                        <mods:languageTerm authority="iso639-2b" type="code">ger</mods:languageTerm>
                    </mods:language>
                    <mods:titleInfo usage="primary">
                        <mods:title>Ein Titel 1</mods:title>
                    </mods:titleInfo>
                    <mods:name type="personal" ID="QUCOSA_001">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname</mods:namePart>
                        <mods:namePart type="given">Vorname 1</mods:namePart>
                    </mods:name>
                    <mods:extension>
                        <foaf:Person rdf:about="QUCOSA_001" />
                    </mods:extension>
                </mods:mods>';

        libxml_use_internal_errors(true);

        $expectedDom = new \DOMDocument();
        $expectedDom->loadXML($expectedXml);

        $this->internalXml->setXml($xml);

        // Remove a field
        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:name[@type="personal"]');
        $metadataGroup->setMappingForReading('');
        $metadataGroup->setModsExtensionMapping('mods:extension/foaf:Person');
        $metadataGroup->setModsExtensionReference('rdf:about');
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('mods:namePart[@type="given"]');

        $group = $this->internalXml->findGroup($metadataGroup, 0);
        $this->internalXml->removeField($group, $metadataObject, 1);

        // Remove an attribute field
        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:titleInfo[@usage="primary"]');
        $metadataGroup->setMappingForReading('');
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('@lang');

        $group = $this->internalXml->findGroup($metadataGroup, 0);
        $this->internalXml->removeField($group, $metadataObject);

        // Remove an extension field
        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:name[@type="personal"]');
        $metadataGroup->setMappingForReading('');
        $metadataGroup->setModsExtensionMapping('mods:extension/foaf:Person');
        $metadataGroup->setModsExtensionReference('rdf:about');
        $metadataObject = new MetadataObject();
        $metadataObject->setMapping('foaf:mbox');
        $metadataObject->setModsExtension(true);

        $group = $this->internalXml->findGroup($metadataGroup, 0);
        $this->internalXml->removeField($group, $metadataObject, 0);

        $this->assertXmlStringEqualsXmlString($expectedDom->saveXML(), $this->internalXml->getXml());
    }

    /**
     * @test
     */
    public function testRemoveGroup()
    {
        $xml = '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:person="http://www.w3.org/ns/person#" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd" version="3.7">
                    <mods:language>
                        <mods:languageTerm authority="iso639-2b" type="code">ger</mods:languageTerm>
                    </mods:language>
                    <mods:titleInfo usage="primary" lang="ger">
                        <mods:title>Ein Titel</mods:title>
                    </mods:titleInfo>
                    <mods:name type="personal" ID="QUCOSA_001">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname</mods:namePart>
                        <mods:namePart type="given">Vorname</mods:namePart>
                    </mods:name>
                    <mods:extension>
                        <foaf:Person rdf:about="QUCOSA_001">
                            <foaf:mbox>Mail 1</foaf:mbox>
                        </foaf:Person>
                    </mods:extension>
                    <mods:name type="personal" ID="QUCOSA_002">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname</mods:namePart>
                        <mods:namePart type="given">Vorname</mods:namePart>
                    </mods:name>
                    <mods:extension>
                        <foaf:Person rdf:about="QUCOSA_002">
                            <foaf:mbox>Mail 2</foaf:mbox>
                        </foaf:Person>
                    </mods:extension>
                </mods:mods>';

        $expectedXml = '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:person="http://www.w3.org/ns/person#" xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-7.xsd" version="3.7">
                    <mods:language>
                        <mods:languageTerm authority="iso639-2b" type="code">ger</mods:languageTerm>
                    </mods:language>
                    <mods:titleInfo usage="primary" lang="ger">
                        <mods:title>Ein Titel</mods:title>
                    </mods:titleInfo>
                    <mods:name type="personal" ID="QUCOSA_002">
                        <mods:role>
                            <mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm>
                        </mods:role>
                        <mods:namePart type="family">Nachname</mods:namePart>
                        <mods:namePart type="given">Vorname</mods:namePart>
                    </mods:name>
                    <mods:extension>
                        <foaf:Person rdf:about="QUCOSA_002">
                            <foaf:mbox>Mail 2</foaf:mbox>
                        </foaf:Person>
                    </mods:extension>
                </mods:mods>';

        libxml_use_internal_errors(true);

        $expectedDom = new \DOMDocument();
        $expectedDom->loadXML($expectedXml);

        $this->internalXml->setXml($xml);

        // Remove a group
        $metadataGroup = new MetadataGroup();
        $metadataGroup->setMapping('mods:name[@type="personal"]');
        $metadataGroup->setMappingForReading('');
        $metadataGroup->setModsExtensionMapping('mods:extension/foaf:Person');
        $metadataGroup->setModsExtensionReference('rdf:about');

        $this->internalXml->removeGroup($metadataGroup, 0);

        $this->assertXmlStringEqualsXmlString($expectedDom->saveXML(), $this->internalXml->getXml());
    }
}
