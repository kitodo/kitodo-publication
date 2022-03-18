<?php
namespace EWW\Dpf\Tests\Unit\Services;

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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PhpParser\Node\Expr\Cast\Array_;

class XPathXMLGeneratorTest extends UnitTestCase
{

    /**
     * @var XPathXMLGenerator
     */
    private $xpathGenerator = null;

    protected function setUp() {
        $this->xpathGenerator = new \EWW\Dpf\Services\XPathXMLGenerator();
    }

    /**
     * @test
     */
    public function testComplexElementsWithAttributes()
    {
        $xpath = 'mods:name[mods:role/mods:roleTerm[@type="code"][@authority="marcrelator"]="edt"]/mods:displayForm';
        $expectedXml = '<mods:name><mods:role><mods:roleTerm type="code" authority="marcrelator">edt</mods:roleTerm></mods:role><mods:displayForm/></mods:name>';

        $this->xpathGenerator->generateXmlFromXPath($xpath);

        $this->assertEquals(
            $expectedXml,
            $this->xpathGenerator->getXML()
        );
    }

    /**
     * @test
     */
    public function testElementsWithMoreAttributes()
    {
        $xpath = 'mods:language/mods:languageTerm[@authority="iso639-2b"][@type="code"]="ger"';
        $expectedXml = '<mods:language><mods:languageTerm authority="iso639-2b" type="code">ger</mods:languageTerm></mods:language>';

        $this->xpathGenerator->generateXmlFromXPath($xpath);

        $this->assertEquals(
            $expectedXml,
            $this->xpathGenerator->getXML()
        );
    }

    /**
     * @test
     */
    public function testSimpleElementWithAttribute()
    {
        $xpath = 'mods:originInfo[@eventType="publication"]';
        $expectedXml = '<mods:originInfo eventType="publication"/>';

        $this->xpathGenerator->generateXmlFromXPath($xpath);

        $this->assertEquals(
            $expectedXml,
            $this->xpathGenerator->getXML()
        );
    }

    /**
     * @test
     */
    public function testValuesWithLinebreaks()
    {
        $xpath = "mods:originInfo[@eventType='publication']=\"A\nB\nC\n\"";
        $expectedXml = "<mods:originInfo eventType=\"publication\">A\nB\nC\n</mods:originInfo>";

        $this->xpathGenerator->generateXmlFromXPath($xpath);

        $this->assertEquals(
            $expectedXml,
            $this->xpathGenerator->getXML()
        );
    }

    /**
     * @test
     */
    public function testXMLGeneration5()
    {
        $xpath = "mods:originInfo[@xlink:href='test']";
        $expectedXml = "<mods:originInfo xlink:href=\"test\"/>";

        $this->xpathGenerator->generateXmlFromXPath($xpath);

        $this->assertEquals(
            $expectedXml,
            $this->xpathGenerator->getXML()
        );
    }

    /**
     * @test
     */
    public function returnsDOMDocumentWithNamespaces() {
        $nsConfig = ["kp=https://www.kitodo.org/publication/"];
        $xpath = "kp:file";

        $this->xpathGenerator->generateXmlFromXPath($xpath);
        /** @var DOMDocument $doc */
        $doc = $this->xpathGenerator->getDocument($nsConfig);

        $this->assertInstanceOf("DOMDocument", $doc, "Expect DOMDocument object");
        $this->assertEquals(
            "https://www.kitodo.org/publication/",
            $doc->lookupNamespaceUri("kp"),
            "Expected declared namespace in DOMDocument");
    }
}
