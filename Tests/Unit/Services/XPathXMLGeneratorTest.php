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

use Nimut\TestingFramework\TestCase\UnitTestCase;
include_once 'Classes/Services/XPathXMLGenerator.php';

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
    public function testXMLGeneration1()
    {
        $xpath = 'mods:name[mods:role/mods:roleTerm[@type="code"][@authority="marcrelator"]="edt"]/mods:displayForm';
        $expectedXml = '<mods:name><mods:role><mods:roleTerm type="code" authority="marcrelator">edt</mods:roleTerm></mods:role><mods:displayForm/></mods:name>';

        $this->xpathGenerator->loop($xpath);

        $this->assertEquals(
            $expectedXml,
            $this->xpathGenerator->getXML()
        );
    }

    /**
     * @test
     */
    public function testXMLGeneration2()
    {
        $xpath = 'mods:language/mods:languageTerm[@authority="iso639-2b"][@type="code"]="ger"';
        $expectedXml = '<mods:language><mods:languageTerm authority="iso639-2b" type="code">ger</mods:languageTerm></mods:language>';

        $this->xpathGenerator->loop($xpath);

        $this->assertEquals(
            $expectedXml,
            $this->xpathGenerator->getXML()
        );
    }

    /**
     * @test
     */
    public function testXMLGeneration3()
    {
        $xpath = 'mods:originInfo[@eventType="publication"]';
        $expectedXml = '<mods:originInfo eventType="publication"/>';

        $this->xpathGenerator->loop($xpath);

        $this->assertEquals(
            $expectedXml,
            $this->xpathGenerator->getXML()
        );
    }



}
