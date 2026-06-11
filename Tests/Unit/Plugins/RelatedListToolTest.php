<?php

namespace EWW\Dpf\Tests\Unit\Plugins;

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
use EWW\Dpf\Plugins\RelatedListTool\RelatedListTool;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class RelatedListToolTest extends UnitTestCase
{
    private function makePlugin(): RelatedListTool
    {
        return $this->getMockBuilder(RelatedListTool::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
    }

    private function setDoc(RelatedListTool $plugin, MetsDocument $doc): void
    {
        $ref = new \ReflectionProperty(RelatedListTool::class, 'doc');
        $ref->setAccessible(true);
        $ref->setValue($plugin, $doc);
    }

    public function testGetRelatedItemsWithConstituents()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mets:mets xmlns:mets="http://www.loc.gov/METS/"
           xmlns:mods="http://www.loc.gov/mods/v3"
           xmlns:slub="http://slub-dresden.de/"
           xmlns:xlink="http://www.w3.org/1999/xlink"
           OBJID="qucosa:parent">
    <mets:dmdSec ID="DMD_000">
        <mets:mdWrap MDTYPE="MODS">
            <mets:xmlData>
                <mods:mods>
                    <mods:titleInfo><mods:title>Parent Volume</mods:title></mods:titleInfo>
                    <mods:relatedItem type="constituent">
                        <mods:titleInfo><mods:title>Chapter One</mods:title></mods:titleInfo>
                        <mods:identifier type="local">qucosa:ch1</mods:identifier>
                        <mods:extension>
                            <slub:info><slub:sortingKey>001</slub:sortingKey></slub:info>
                        </mods:extension>
                    </mods:relatedItem>
                    <mods:relatedItem type="constituent">
                        <mods:titleInfo><mods:title>Chapter Two</mods:title></mods:titleInfo>
                        <mods:identifier type="local">qucosa:ch2</mods:identifier>
                        <mods:extension>
                            <slub:info><slub:sortingKey>002</slub:sortingKey></slub:info>
                        </mods:extension>
                    </mods:relatedItem>
                    <mods:relatedItem type="host">
                        <mods:titleInfo><mods:title>Should Not Appear</mods:title></mods:titleInfo>
                    </mods:relatedItem>
                </mods:mods>
            </mets:xmlData>
        </mets:mdWrap>
    </mets:dmdSec>
    <mets:structMap TYPE="LOGICAL">
        <mets:div ID="LOG_0000" DMDID="DMD_000" TYPE="multivolume_work"/>
    </mets:structMap>
</mets:mets>
XML;
        $doc = MetsDocument::fromXmlString($xml, 'http://example.org/parent');
        $plugin = $this->makePlugin();
        $this->setDoc($plugin, $doc);

        $items = $plugin->getRelatedItems();

        $this->assertCount(2, $items);
        $this->assertEquals('Chapter One', $items[0]['title']);
        $this->assertEquals('qucosa:ch1', $items[0]['docId']);
        $this->assertEquals('local', $items[0]['type']);
        $this->assertEquals('Chapter Two', $items[1]['title']);
    }

    public function testGetRelatedItemsSortedBySortingKey()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mets:mets xmlns:mets="http://www.loc.gov/METS/"
           xmlns:mods="http://www.loc.gov/mods/v3"
           xmlns:slub="http://slub-dresden.de/"
           OBJID="qucosa:parent">
    <mets:dmdSec ID="DMD_000">
        <mets:mdWrap MDTYPE="MODS">
            <mets:xmlData>
                <mods:mods>
                    <mods:relatedItem type="constituent">
                        <mods:titleInfo><mods:title>Z Title</mods:title></mods:titleInfo>
                        <mods:identifier type="local">qucosa:z</mods:identifier>
                        <mods:extension><slub:info><slub:sortingKey>002</slub:sortingKey></slub:info></mods:extension>
                    </mods:relatedItem>
                    <mods:relatedItem type="constituent">
                        <mods:titleInfo><mods:title>A Title</mods:title></mods:titleInfo>
                        <mods:identifier type="local">qucosa:a</mods:identifier>
                        <mods:extension><slub:info><slub:sortingKey>001</slub:sortingKey></slub:info></mods:extension>
                    </mods:relatedItem>
                </mods:mods>
            </mets:xmlData>
        </mets:mdWrap>
    </mets:dmdSec>
    <mets:structMap TYPE="LOGICAL">
        <mets:div ID="LOG_0000" DMDID="DMD_000" TYPE="multivolume_work"/>
    </mets:structMap>
</mets:mets>
XML;
        $doc = MetsDocument::fromXmlString($xml);
        $plugin = $this->makePlugin();
        $this->setDoc($plugin, $doc);

        $items = $plugin->getRelatedItems();

        $this->assertCount(2, $items);
        $this->assertEquals('A Title', $items[0]['title']);
        $this->assertEquals('Z Title', $items[1]['title']);
    }

    public function testGetRelatedItemsEmptyWhenNoConstituents()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mets:mets xmlns:mets="http://www.loc.gov/METS/"
           xmlns:mods="http://www.loc.gov/mods/v3"
           OBJID="qucosa:simple">
    <mets:dmdSec ID="DMD_000">
        <mets:mdWrap MDTYPE="MODS">
            <mets:xmlData>
                <mods:mods>
                    <mods:titleInfo><mods:title>Simple Article</mods:title></mods:titleInfo>
                    <mods:relatedItem type="host">
                        <mods:titleInfo><mods:title>Journal</mods:title></mods:titleInfo>
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
        $plugin = $this->makePlugin();
        $this->setDoc($plugin, $doc);

        $items = $plugin->getRelatedItems();

        $this->assertEmpty($items);
    }

    public function testGetRelatedItemsMissingTitleFallsBackToDocId()
    {
        $xml = <<<XML
<?xml version="1.0"?>
<mets:mets xmlns:mets="http://www.loc.gov/METS/"
           xmlns:mods="http://www.loc.gov/mods/v3"
           OBJID="qucosa:parent">
    <mets:dmdSec ID="DMD_000">
        <mets:mdWrap MDTYPE="MODS">
            <mets:xmlData>
                <mods:mods>
                    <mods:relatedItem type="constituent">
                        <mods:identifier type="urn">urn:nbn:de:bsz:14-qucosa2-999</mods:identifier>
                    </mods:relatedItem>
                </mods:mods>
            </mets:xmlData>
        </mets:mdWrap>
    </mets:dmdSec>
    <mets:structMap TYPE="LOGICAL">
        <mets:div ID="LOG_0000" DMDID="DMD_000" TYPE="multivolume_work"/>
    </mets:structMap>
</mets:mets>
XML;
        $doc = MetsDocument::fromXmlString($xml);
        $plugin = $this->makePlugin();
        $this->setDoc($plugin, $doc);

        $items = $plugin->getRelatedItems();

        $this->assertCount(1, $items);
        $this->assertEquals('', $items[0]['title']);
        $this->assertEquals('urn:nbn:de:bsz:14-qucosa2-999', $items[0]['docId']);
        $this->assertEquals('urn', $items[0]['type']);
    }
}
