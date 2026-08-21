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

use EWW\Dpf\Updates\FixMissingPlaceOfPublicationWrapUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class FixMissingPlaceOfPublicationWrapUpdateTest extends UnitTestCase
{
    public function testSetsWrapOnEmptyPlaceOfPublicationRow()
    {
        $wizard = new FixMissingPlaceOfPublicationWrapUpdate();

        $fix = $wizard->computeFix([
            'uid' => 531,
            'index_name' => 'place_of_publication',
            'wrap' => '',
        ]);

        $this->assertSame(
            "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.wrap = <dd>|</dd>",
            $fix['wrap']
        );
    }

    public function testSetsWrapOnEmptyPlaceOfPublicationHostRow()
    {
        $wizard = new FixMissingPlaceOfPublicationWrapUpdate();

        $fix = $wizard->computeFix([
            'uid' => 532,
            'index_name' => 'place_of_publication_host',
            'wrap' => null,
        ]);

        $this->assertArrayHasKey('wrap', $fix);
    }

    public function testAlreadyWrappedRowNeedsNoFix()
    {
        $wizard = new FixMissingPlaceOfPublicationWrapUpdate();

        $fix = $wizard->computeFix([
            'uid' => 531,
            'index_name' => 'place_of_publication',
            'wrap' => "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.wrap = <dd>|</dd>",
        ]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new FixMissingPlaceOfPublicationWrapUpdate();

        $fix = $wizard->computeFix([
            'uid' => 300,
            'index_name' => 'original_place',
            'wrap' => '',
        ]);

        $this->assertSame([], $fix);
    }
}
