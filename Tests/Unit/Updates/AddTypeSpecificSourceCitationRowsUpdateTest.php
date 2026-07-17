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

use EWW\Dpf\Updates\AddTypeSpecificSourceCitationRowsUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddTypeSpecificSourceCitationRowsUpdateTest extends UnitTestCase
{
    public function testDefinesNachschlagewerkAndErschienenInRows()
    {
        $rows = (new AddTypeSpecificSourceCitationRowsUpdate())->getNewRows();

        $this->assertCount(2, $rows);
        $this->assertSame('original_in_encyclopedia', $rows[0]['index_name']);
        $this->assertSame('Nachschlagewerk', $rows[0]['label']);
        $this->assertSame('original_in_media', $rows[1]['index_name']);
        $this->assertSame('Erschienen in', $rows[1]['label']);
    }

    public function testWrapsAreGatedOnTheVerifiedTypeValues()
    {
        $rows = (new AddTypeSpecificSourceCitationRowsUpdate())->getNewRows();

        foreach ($rows as $row) {
            $this->assertStringContainsString('value.if.equals.field = type', $row['wrap']);
        }
        $this->assertStringContainsString('value.if.value = in_encyclopedia', $rows[0]['wrap']);
        $this->assertStringContainsString('value.if.value = contributionToPeriodical', $rows[1]['wrap']);
    }

    public function testWrapsKeepTheTemplateStructure()
    {
        $rows = (new AddTypeSpecificSourceCitationRowsUpdate())->getNewRows();

        foreach ($rows as $row) {
            $wrap = $row['wrap'];
            $this->assertStringStartsWith('key.wrap = <dt>|</dt>', $wrap);
            $this->assertStringContainsString('value.wrap3 = <dd>|</dd>', $wrap);
            $this->assertStringContainsString('value.fieldRequired = original_title', $wrap);
            // Page ranges must use the en dash per #2042, matching the fixed template rows.
            $this->assertStringContainsString('|–| <br />', $wrap);
            $this->assertStringNotContainsString('|-| <br />', $wrap);
        }
    }

    public function testRowsDoNotCollideWithExistingIndexNames()
    {
        $existing = [
            'original_in_proceeding0000000',
            'original_in_book',
            'original0000000000',
        ];

        foreach ((new AddTypeSpecificSourceCitationRowsUpdate())->getNewRows() as $row) {
            $this->assertNotContains($row['index_name'], $existing);
        }
    }

    public function testSortingValuesAreDistinct()
    {
        $sortings = [];
        foreach ((new AddTypeSpecificSourceCitationRowsUpdate())->getNewRows() as $row) {
            $sortings[] = $row['sorting'];
            $sortings[] = $row['overlay_sorting'];
        }

        $this->assertSame($sortings, array_unique($sortings));
    }
}
