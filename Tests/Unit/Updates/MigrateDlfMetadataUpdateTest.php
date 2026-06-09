<?php

namespace EWW\Dpf\Tests\Unit\Updates;

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

use EWW\Dpf\Updates\MigrateDlfMetadataUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class MigrateDlfMetadataUpdateTest extends UnitTestCase
{
    protected function dlfRow(array $overrides = []): array
    {
        return array_merge([
            'uid' => 42,
            'pid' => 1,
            'tstamp' => 1000,
            'crdate' => 900,
            'cruser_id' => 3,
            'hidden' => 0,
            'sys_language_uid' => 0,
            'l18n_parent' => 0,
            'sorting' => 256,
            'index_name' => 'title',
            'label' => 'Titel',
            'wrap' => 'key.wrap = <dt>|</dt>',
            'is_listed' => 1,
            'is_sortable' => 0,
            'format' => 1,
            'default_value' => '',
        ], $overrides);
    }

    public function testMapRowPreservesUidAndCarriesFirstChild()
    {
        $wizard = new MigrateDlfMetadataUpdate();
        $rows = $wizard->mapRow($this->dlfRow(), [
            ['xpath' => './mods:titleInfo/mods:title', 'xpath_sorting' => '', 'format_type' => 'MODS'],
        ]);

        $this->assertCount(1, $rows);
        $this->assertEquals(42, $rows[0]['uid']);
        $this->assertEquals('MODS', $rows[0]['format_type']);
        $this->assertEquals('./mods:titleInfo/mods:title', $rows[0]['xpath']);
        $this->assertEquals('Titel', $rows[0]['label']);
        $this->assertEquals('key.wrap = <dt>|</dt>', $rows[0]['wrap']);
        $this->assertEquals(1, $rows[0]['is_listed']);
        $this->assertEquals(0, $rows[0]['deleted']);
    }

    public function testMapRowWithoutChildrenYieldsSingleRowWithoutXpath()
    {
        $wizard = new MigrateDlfMetadataUpdate();
        $rows = $wizard->mapRow(
            $this->dlfRow(['format' => 0, 'default_value' => 'Open Access']),
            []
        );

        $this->assertCount(1, $rows);
        $this->assertEquals('', $rows[0]['xpath']);
        $this->assertEquals('', $rows[0]['format_type']);
        $this->assertEquals('Open Access', $rows[0]['default_value']);
        $this->assertEquals(0, $rows[0]['format']);
    }

    public function testMapRowWithMultipleChildrenAppendsExtraRowsWithoutUid()
    {
        $wizard = new MigrateDlfMetadataUpdate();
        $rows = $wizard->mapRow($this->dlfRow(), [
            ['xpath' => './mods:a', 'xpath_sorting' => '', 'format_type' => 'MODS'],
            ['xpath' => './slub:b', 'xpath_sorting' => './slub:c', 'format_type' => 'SLUB'],
        ]);

        $this->assertCount(2, $rows);
        $this->assertEquals(42, $rows[0]['uid']);
        $this->assertArrayNotHasKey('uid', $rows[1]);
        $this->assertEquals('SLUB', $rows[1]['format_type']);
        $this->assertEquals('./slub:b', $rows[1]['xpath']);
        $this->assertEquals('./slub:c', $rows[1]['xpath_sorting']);
        $this->assertEquals('title', $rows[1]['index_name']);
    }

    public function testMapRowKeepsLocalizationFields()
    {
        $wizard = new MigrateDlfMetadataUpdate();
        $rows = $wizard->mapRow(
            $this->dlfRow(['uid' => 99, 'sys_language_uid' => 1, 'l18n_parent' => 42, 'label' => 'Title']),
            []
        );

        $this->assertEquals(99, $rows[0]['uid']);
        $this->assertEquals(1, $rows[0]['sys_language_uid']);
        $this->assertEquals(42, $rows[0]['l18n_parent']);
        $this->assertEquals('Title', $rows[0]['label']);
    }
}
