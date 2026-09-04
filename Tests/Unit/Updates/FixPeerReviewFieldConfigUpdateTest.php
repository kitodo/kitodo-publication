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

use EWW\Dpf\Updates\FixPeerReviewFieldConfigUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class FixPeerReviewFieldConfigUpdateTest extends UnitTestCase
{
    public function testFillsInFormatAndWrapForHalfConfiguredRow()
    {
        $wizard = new FixPeerReviewFieldConfigUpdate();

        $fix = $wizard->computeFix([
            'uid' => 533,
            'index_name' => 'peer_review',
            'format' => 0,
            'wrap' => '',
        ]);

        $this->assertSame(1, $fix['format']);
        $this->assertSame('MODS', $fix['format_type']);
        $this->assertStringContainsString('key.wrap = <dt>|</dt>', $fix['wrap']);
        $this->assertStringContainsString('value.wrap = <dd>|</dd>', $fix['wrap']);
    }

    public function testAlreadyFixedRowNeedsNoFix()
    {
        $wizard = new FixPeerReviewFieldConfigUpdate();

        $fix = $wizard->computeFix([
            'uid' => 533,
            'index_name' => 'peer_review',
            'format' => 1,
            'wrap' => "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd>|</dd>",
        ]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new FixPeerReviewFieldConfigUpdate();

        $fix = $wizard->computeFix([
            'uid' => 17,
            'index_name' => 'VersionBegutachtungsstatus',
            'format' => 0,
            'wrap' => '',
        ]);

        $this->assertSame([], $fix);
    }
}
