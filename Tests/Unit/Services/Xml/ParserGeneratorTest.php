<?php

namespace EWW\Dpf\Tests\Unit\Services\Xml;

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

use DOMDocument;
use EWW\Dpf\Configuration\ClientConfigurationManager;
use EWW\Dpf\Services\Xml\ParserGenerator;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * UBL-26-5106/5114: changing a document's type to "Beitrag in Sammelband"
 * (contained_work) crashed with:
 *   TypeError: Argument 1 passed to DOMDocument::importNode() must be an
 *   instance of DOMNode, null given
 * in ParserGenerator.php's "attribute only" branch of customXPath().
 *
 * Root cause: contained_work's group 190 ("Einverständniserklärung -
 * Deposit") has two metadata objects (uid 744 "Check", uid 938 "Auswahl")
 * both mapped to the same attribute, @given. When a group has no element
 * field values but does have attribute-mapped ones (buildXmlFromForm()'s
 * $item['attributes'] bucket, populated whenever a field's mapping starts
 * with "@"), the attribute-concatenation loop blindly appends every
 * attribute predicate, so two fields sharing an attribute name produce
 * [@given="..."][@given="..."] on the same element - not well-formed XML
 * (duplicate attribute). DOMDocument::loadXML() correctly refuses it,
 * leaving documentElement null, which crashes importNode().
 *
 * ParserGenerator's real constructor needs a live TYPO3
 * ObjectManager/ClientConfigurationManager, which unit tests here don't
 * set up - built via reflection instead, like InternalFormatPeerReviewTest.
 */
class ParserGeneratorTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        // GeneralUtility::addInstance()/setSingletonInstance() leak between tests otherwise.
        GeneralUtility::purgeInstances();
        GeneralUtility::resetSingletonInstances([]);
        parent::tearDown();
    }

    /**
     * customXPath()'s attributeOnly branch calls XPath::create($this->xmlData)
     * with no explicit namespace string, which falls back to a live
     * ObjectManager/ClientConfigurationManager - stub that lookup so the
     * test doesn't need a full TYPO3 bootstrap.
     */
    private function stubNamespaceLookup(string $namespaceConfiguration): void
    {
        $clientConfigurationManager = $this->createMock(ClientConfigurationManager::class);
        $clientConfigurationManager->method('getNamespaces')->willReturn($namespaceConfiguration);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->method('get')->with(ClientConfigurationManager::class)->willReturn($clientConfigurationManager);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager);
    }

    private function newInstance(string $namespaceString): ParserGenerator
    {
        $instance = (new \ReflectionClass(ParserGenerator::class))->newInstanceWithoutConstructor();

        $namespaceProperty = new \ReflectionProperty(ParserGenerator::class, 'namespaceString');
        $namespaceProperty->setAccessible(true);
        $namespaceProperty->setValue($instance, $namespaceString);

        $xmlData = new DOMDocument();
        $xmlData->loadXML('<data' . $namespaceString . '></data>');

        $xmlDataProperty = new \ReflectionProperty(ParserGenerator::class, 'xmlData');
        $xmlDataProperty->setAccessible(true);
        $xmlDataProperty->setValue($instance, $xmlData);

        // buildXmlFromForm() reads getFedoraNamespace() from this directly.
        $clientConfigurationManager = $this->createMock(ClientConfigurationManager::class);
        $clientConfigurationManager->method('getFedoraNamespace')->willReturn('qucosa');
        $clientConfigurationManagerProperty = new \ReflectionProperty(ParserGenerator::class, 'clientConfigurationManager');
        $clientConfigurationManagerProperty->setAccessible(true);
        $clientConfigurationManagerProperty->setValue($instance, $clientConfigurationManager);

        return $instance;
    }

    /**
     * Mirrors DocumentMapper::getMetadata()'s output shape for one group:
     * no element field values, two attribute-mapped fields sharing an
     * attribute name (contained_work group 190's real "Check"/"Auswahl"
     * duplicate @given mapping).
     */
    public function testBuildXmlFromFormSurvivesTwoFieldsMappedToSameAttribute()
    {
        $this->stubNamespaceLookup('slub=http://slub-dresden.de/');
        $instance = $this->newInstance(' xmlns:slub="http://slub-dresden.de/"');

        $form = [
            'metadata' => [
                [
                    'mapping' => 'slub:info/slub:rights/slub:agreement',
                    'modsExtensionMapping' => '',
                    'modsExtensionReference' => '',
                    'values' => [],
                    'attributes' => [
                        ['mapping' => '@given', 'value' => 'true', 'modsExtension' => false],
                        ['mapping' => '@given', 'value' => 'false', 'modsExtension' => false],
                    ],
                ],
            ],
        ];

        $instance->buildXmlFromForm($form);

        // Last value for a duplicate attribute mapping wins; the crash-causing
        // duplicate [@given="true"][@given="false"] predicate must be gone.
        $xml = $instance->getXMLData();
        $this->assertStringContainsString('given="false"', $xml);
        $this->assertStringNotContainsString('given="true"', $xml);
    }

    /**
     * Not every way to end up with a null $docXML->documentElement is a
     * duplicate attribute (e.g. an empty group mapping produces a bare
     * predicate that XMLFragmentGenerator can't turn into valid XML at
     * all). Whatever the cause, this must fail with a clear exception
     * naming the offending xpath - never the bare
     * "importNode(): Argument 1 ... null given" TypeError.
     */
    public function testBuildXmlFromFormThrowsClearExceptionInsteadOfCrashingOnUnbuildableFragment()
    {
        $this->stubNamespaceLookup('mods=http://www.loc.gov/mods/v3');
        $instance = $this->newInstance(' xmlns:mods="http://www.loc.gov/mods/v3"');

        $form = [
            'metadata' => [
                [
                    'mapping' => '', // empty group mapping - no element to attach the attribute to
                    'modsExtensionMapping' => '',
                    'modsExtensionReference' => '',
                    'values' => [],
                    'attributes' => [
                        ['mapping' => '@type', 'value' => 'host', 'modsExtension' => false],
                    ],
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/could not build XML fragment for xpath/');

        $instance->buildXmlFromForm($form);
    }
}
