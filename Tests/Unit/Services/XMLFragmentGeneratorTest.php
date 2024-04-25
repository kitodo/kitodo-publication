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

use EWW\Dpf\Services\Xml\XMLFragmentGenerator;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class XMLFragmentGeneratorTest extends UnitTestCase
{

    public function testSimpleElement()
    {
        $fragment = XMLFragmentGenerator::fragmentFor('mods:title');
        $expectedXml = '<mods:title/>';
        $this->assertEquals($expectedXml, $fragment);
    }

    public function testSimpleElementWithTextNode()
    {
        $fragment = XMLFragmentGenerator::fragmentFor('mods:title="Hello"');
        $expectedXml = '<mods:title>Hello</mods:title>';
        $this->assertEquals($expectedXml, $fragment);
    }

    public function testSimpleElementTextNodeWithQuotes()
    {
        $fragment = XMLFragmentGenerator::fragmentFor('mods:title="\"Hello\" World"');
        $expectedXml = '<mods:title>&quot;Hello&quot; World</mods:title>';
        $this->assertEquals($expectedXml, $fragment);
    }

    public function testSimpleElementAttributeWithQuotes()
    {
        $fragment = XMLFragmentGenerator::fragmentFor('mods:title[@foo="\"Hello\" World"]');
        $expectedXml = '<mods:title foo="&quot;Hello&quot; World"/>';
        $this->assertEquals($expectedXml, $fragment);
    }

    public function testComplexElementsWithAttributes()
    {
        $fragment = XMLFragmentGenerator::fragmentFor('mods:name[mods:role/mods:roleTerm[@type="code"][@authority="marcrelator"]="edt"]/mods:displayForm');
        $expectedXml = '<mods:name><mods:role><mods:roleTerm type="code" authority="marcrelator">edt</mods:roleTerm></mods:role><mods:displayForm/></mods:name>';
        $this->assertEquals($expectedXml, $fragment);
    }

    public function testElementsWithMoreAttributes()
    {
        $fragment = XMLFragmentGenerator::fragmentFor('mods:language/mods:languageTerm[@authority="iso639-2b"][@type="code"]="ger"');
        $expectedXml = '<mods:language><mods:languageTerm authority="iso639-2b" type="code">ger</mods:languageTerm></mods:language>';
        $this->assertEquals($expectedXml, $fragment);
    }

    public function testSimpleElementWithAttribute()
    {
        $fragment = XMLFragmentGenerator::fragmentFor('mods:originInfo[@eventType="publication"]');
        $expectedXml = '<mods:originInfo eventType="publication"/>';
        $this->assertEquals($expectedXml, $fragment);
    }

    public function testComplexPathWithAttributes()
    {
        $fragment = XMLFragmentGenerator::fragmentFor('mods:originInfo[@eventType="publication"]/mods:dateIssued[@encoding="iso8601"]');
        $expectedXml = '<mods:originInfo eventType="publication"><mods:dateIssued encoding="iso8601"/></mods:originInfo>';
        $this->assertEquals($expectedXml, $fragment);
    }

    public function testValuesWithLinebreaks()
    {
        $fragment = XMLFragmentGenerator::fragmentFor("mods:originInfo[@eventType='publication']=\"A\nB\nC\n\"");
        $expectedXml = "<mods:originInfo eventType=\"publication\">A\nB\nC\n</mods:originInfo>";
        $this->assertEquals($expectedXml, $fragment);
    }

    public function testElementWithNamespacedAttribute()
    {
        $fragment = XMLFragmentGenerator::fragmentFor("mods:originInfo[@xlink:href='test']");
        $expectedXml = "<mods:originInfo xlink:href=\"test\"/>";
        $this->assertEquals($expectedXml, $fragment);
    }
}
