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

use EWW\Dpf\Updates\AddBlogSourceCitationRowUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddBlogSourceCitationRowUpdateTest extends UnitTestCase
{
    public function testDefinesBlogRow()
    {
        $row = (new AddBlogSourceCitationRowUpdate())->getNewRow();

        $this->assertSame('original_in_dynamicwebresource', $row['index_name']);
        $this->assertSame('Blog', $row['label']);
    }

    public function testWrapIsGatedOnTheVerifiedTypeValue()
    {
        $row = (new AddBlogSourceCitationRowUpdate())->getNewRow();

        $this->assertStringContainsString('value.if.equals.field = type', $row['wrap']);
        $this->assertStringContainsString('value.if.value = partOfADynamicWebResource', $row['wrap']);
    }

    public function testWrapKeepsTheTemplateStructure()
    {
        $wrap = (new AddBlogSourceCitationRowUpdate())->getNewRow()['wrap'];

        $this->assertStringStartsWith('key.wrap = <dt>|</dt>', $wrap);
        $this->assertStringContainsString('value.wrap3 = <dd>|</dd>', $wrap);
        $this->assertStringContainsString('value.fieldRequired = original_title', $wrap);
        // Page ranges must use the en dash per #2042, matching the fixed template rows.
        $this->assertStringContainsString('|–| <br />', $wrap);
        $this->assertStringNotContainsString('|-| <br />', $wrap);
    }

    public function testRowDoesNotCollideWithExistingIndexNames()
    {
        $existing = [
            'original_in_proceeding0000000',
            'original_in_book',
            'original0000000000',
            'original_in_encyclopedia',
            'original_in_media',
        ];

        $row = (new AddBlogSourceCitationRowUpdate())->getNewRow();
        $this->assertNotContains($row['index_name'], $existing);
    }

    public function testSortingValuesAreDistinct()
    {
        $row = (new AddBlogSourceCitationRowUpdate())->getNewRow();

        $this->assertNotSame($row['sorting'], $row['overlay_sorting']);
    }
}
