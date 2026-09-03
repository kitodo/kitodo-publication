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

use EWW\Dpf\Updates\FixAndereAusgabeUrlCopyPasteUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class FixAndereAusgabeUrlCopyPasteUpdateTest extends UnitTestCase
{
    /** Real uid-225 slot-2 excerpt as of 2026-09-04, fetched from sdvqucosa-test-web01. */
    private const SLOT2_EXCERPT = "\t30 = TEXT\r\n"
        . "\t30 {\r\n"
        . "\t\tfield = url_1\r\n"
        . "\t\ttypolink.parameter.field = url_2\r\n"
        . "\t\trequired = 1\r\n"
        . "\t\twrap = Link:&nbsp;|<br/>\r\n"
        . "\t}\r\n";

    /** Slot 1's matching pair, must survive untouched. */
    private const SLOT1_EXCERPT = "\t30 = TEXT\r\n"
        . "\t30 {\r\n"
        . "\t\tfield = url_1\r\n"
        . "\t\ttypolink.parameter.field = url_1\r\n"
        . "\t\trequired = 1\r\n"
        . "\t\twrap = Link:&nbsp;|<br/>\r\n"
        . "\t}\r\n";

    public function testFixesSlot2DisplayFieldToMatchItsOwnLink()
    {
        $wizard = new FixAndereAusgabeUrlCopyPasteUpdate();

        $wrap = self::SLOT1_EXCERPT . self::SLOT2_EXCERPT;
        $fix = $wizard->computeFix(['uid' => 225, 'index_name' => 'otherVersion00', 'wrap' => $wrap]);

        $this->assertArrayHasKey('wrap', $fix);
        // slot 1 (matching pair) untouched
        $this->assertStringContainsString(self::SLOT1_EXCERPT, $fix['wrap']);
        // slot 2 now shows/links the same field
        $this->assertStringContainsString(
            "\t\tfield = url_2\r\n\t\ttypolink.parameter.field = url_2",
            $fix['wrap']
        );
        $this->assertStringNotContainsString(
            "\t\tfield = url_1\r\n\t\ttypolink.parameter.field = url_2",
            $fix['wrap']
        );
    }

    public function testAlreadyFixedRowNeedsNoFix()
    {
        $wizard = new FixAndereAusgabeUrlCopyPasteUpdate();

        $firstFix = $wizard->computeFix(['uid' => 225, 'index_name' => 'otherVersion00', 'wrap' => self::SLOT2_EXCERPT]);

        $fix = $wizard->computeFix(['uid' => 225, 'index_name' => 'otherVersion00', 'wrap' => $firstFix['wrap']]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new FixAndereAusgabeUrlCopyPasteUpdate();

        $fix = $wizard->computeFix(['uid' => 999, 'index_name' => 'original_in_book', 'wrap' => self::SLOT2_EXCERPT]);

        $this->assertSame([], $fix);
    }
}
