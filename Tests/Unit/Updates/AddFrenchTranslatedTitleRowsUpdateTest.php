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

use EWW\Dpf\Updates\AddFrenchTranslatedTitleRowsUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddFrenchTranslatedTitleRowsUpdateTest extends UnitTestCase
{
    public function testDefinesThreeFrenchTitleRows()
    {
        $rows = (new AddFrenchTranslatedTitleRowsUpdate())->getNewRows();

        $this->assertCount(3, $rows);
        $this->assertSame('translated_title1_fre', $rows[0]['index_name']);
        $this->assertSame('translated_subtitle1_fre', $rows[1]['index_name']);
        $this->assertSame('translated_title_fre', $rows[2]['index_name']);
    }

    public function testExtractionRowsUseCorrectLanguageCodeAndAreHidden()
    {
        $rows = (new AddFrenchTranslatedTitleRowsUpdate())->getNewRows();

        foreach ([$rows[0], $rows[1]] as $row) {
            $this->assertSame(1, $row['format']);
            $this->assertSame('MODS', $row['format_type']);
            $this->assertStringContainsString('@lang="fre"', $row['xpath']);
            $this->assertStringContainsString('@type="translated"', $row['xpath']);
            // Hidden: rendered only via the composite row's {field:...} reference,
            // never standalone - matches the established translated_title1_eng/ger pattern.
            $this->assertSame(1, $row['hidden']);
        }
    }

    public function testCompositeRowIsDisplayOnlyAndReferencesExtractionRows()
    {
        $rows = (new AddFrenchTranslatedTitleRowsUpdate())->getNewRows();
        $composite = $rows[2];

        $this->assertSame(0, $composite['format']);
        $this->assertSame('', $composite['xpath']);
        $this->assertArrayNotHasKey('hidden', $composite);
        $this->assertStringContainsString('value.fieldRequired = translated_title1_fre', $composite['wrap']);
        $this->assertStringContainsString('{field:translated_title1_fre}', $composite['wrap']);
        $this->assertStringContainsString('{field:translated_subtitle1_fre}', $composite['wrap']);
        // Must use wrap3 (applied after value.append), not plain wrap (applied before
        // append) - the latter lets the appended subtitle text leak outside the <dd>.
        $this->assertStringContainsString('value.wrap3 = <dd>|</dd>', $composite['wrap']);
        $this->assertStringNotContainsString('value.wrap =', $composite['wrap']);
    }

    public function testSortingValuesAreDistinctAndOrdered()
    {
        $rows = (new AddFrenchTranslatedTitleRowsUpdate())->getNewRows();
        $sortings = array_column($rows, 'sorting');

        $this->assertSame($sortings, array_unique($sortings));
        $this->assertSame($sortings, [$sortings[0], $sortings[1], $sortings[2]]);
    }

    public function testRowsDoNotCollideWithExistingIndexNames()
    {
        $existing = [
            'translated_title1_eng',
            'translated_subtitle1_eng',
            'translated_title_eng',
            'translated_title1_ger',
        ];

        foreach ((new AddFrenchTranslatedTitleRowsUpdate())->getNewRows() as $row) {
            $this->assertNotContains($row['index_name'], $existing);
        }
    }
}
