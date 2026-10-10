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

use EWW\Dpf\Services\Api\InternalFormat;
use EWW\Dpf\Services\Xml\XPath;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * #2051: getHostVolume() reads a document's issue designation from its host
 * relation. The InternalFormat constructor needs a live TYPO3 ObjectManager,
 * so the instance is built without it and given a ready XPath.
 */
class InternalFormatHostVolumeTest extends UnitTestCase
{
    private function newInstance(string $modsInner): InternalFormat
    {
        $dom = new \DOMDocument();
        $dom->loadXML(
            '<data xmlns:mods="http://www.loc.gov/mods/v3"><mods:mods>' . $modsInner . '</mods:mods></data>'
        );

        $instance = (new \ReflectionClass(InternalFormat::class))->newInstanceWithoutConstructor();
        $property = new \ReflectionProperty(InternalFormat::class, 'preconfiguredDomXPath');
        $property->setAccessible(true);
        $property->setValue($instance, XPath::create($dom, 'mods=http://www.loc.gov/mods/v3'));

        return $instance;
    }

    public function testReadsVolumeOfHostRelation()
    {
        $instance = $this->newInstance(
            '<mods:relatedItem type="host">'
            . '<mods:part type="volume"><mods:detail><mods:number>5,1 (2025)</mods:number></mods:detail></mods:part>'
            . '</mods:relatedItem>'
        );

        $this->assertSame('5,1 (2025)', $instance->getHostVolume());
    }

    public function testIsEmptyWithoutHostVolume()
    {
        $this->assertSame('', $this->newInstance('<mods:relatedItem type="host"/>')->getHostVolume());
    }

    public function testIgnoresVolumeOfOtherRelations()
    {
        $instance = $this->newInstance(
            '<mods:relatedItem type="series">'
            . '<mods:part type="volume"><mods:detail><mods:number>7</mods:number></mods:detail></mods:part>'
            . '</mods:relatedItem>'
        );

        $this->assertSame('', $instance->getHostVolume());
    }
}
