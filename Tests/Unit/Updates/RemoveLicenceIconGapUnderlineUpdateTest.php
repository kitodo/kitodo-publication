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

use EWW\Dpf\Updates\RemoveLicenceIconGapUnderlineUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class RemoveLicenceIconGapUnderlineUpdateTest extends UnitTestCase
{
    /**
     * Real uid-6 (index_name=licence) wrap content as of 2026-09-14, fetched
     * from sdvqucosa-test-web01. Deliberately keeps the row's real
     * inconsistencies: single vs. double space after &nbsp; (rows 2/3/8 vs.
     * the rest), a trailing-space-only entry with no text at all (row 7,
     * rightsstatements.org InC icon - no &nbsp; to strip), and the
     * pre-existing `.rlase` typo on row 9 (untouched, out of scope here).
     */
    private const REAL_WRAP = "key.wrap = <dt>|</dt>\r\n"
        . "value.required = 1\r\n"
        . "value.replacement.1.search = https://creativecommons.org/licenses/by/4.0/\r\n"
        . "value.replacement.1.replace = <img style=\"border-width:0\" src=\"https://i.creativecommons.org/l/by/4.0/88x31.png\" />&nbsp;CC BY 4.0 \r\n"
        . "value.replacement.2.search = https://creativecommons.org/licenses/by-sa/4.0/\r\n"
        . "value.replacement.2.replace = <img style=\"border-width:0\" src=\"https://i.creativecommons.org/l/by-sa/4.0/88x31.png\" />&nbsp; CC BY-SA 4.0\r\n"
        . "value.replacement.3.search = https://creativecommons.org/licenses/by-nd/4.0/\r\n"
        . "value.replacement.3.replace =<img style=\"border-width:0\" src=\"https://i.creativecommons.org/l/by-nd/4.0/88x31.png\" />&nbsp; CC BY-ND 4.0\r\n"
        . "value.replacement.7.search = https://rightsstatements.org/page/InC/1.0/\r\n"
        . "value.replacement.7.replace = <img height=\"20\" src=\"https://rightsstatements.org/files/buttons/InC.dark-white-interior.svg\" />\r\n"
        . "value.replacement.9.search = https://creativecommons.org/licenses/by/2.0/\r\n"
        . "value.replacement.9.rlase = <img height=\"20\" src=\"https://licensebuttons.net/l/by/2.0/88x31.png\"/>&nbsp;CC BY 2.0\r\n"
        . "value.typolink.parameter.field = licence\r\n"
        . "value.wrap = <dd class=\"licence\">|</dd>";

    public function testStripsNbspAndAnyFollowingSpaceAfterEachImgTag()
    {
        $update = new RemoveLicenceIconGapUnderlineUpdate();
        $fixed = $update->computeFix(self::REAL_WRAP);

        $this->assertStringNotContainsString('&nbsp;', $fixed);
        $this->assertStringContainsString('/>CC BY 4.0', $fixed);
        $this->assertStringContainsString('/>CC BY-SA 4.0', $fixed);
        $this->assertStringContainsString('/>CC BY-ND 4.0', $fixed);
    }

    public function testLeavesRowsWithNoTrailingTextUnchanged()
    {
        $update = new RemoveLicenceIconGapUnderlineUpdate();
        $fixed = $update->computeFix(self::REAL_WRAP);

        $this->assertStringContainsString(
            '<img height="20" src="https://rightsstatements.org/files/buttons/InC.dark-white-interior.svg" />',
            $fixed
        );
    }

    public function testIsIdempotent()
    {
        $update = new RemoveLicenceIconGapUnderlineUpdate();
        $fixed = $update->computeFix(self::REAL_WRAP);

        $this->assertSame($fixed, $update->computeFix($fixed));
    }

    public function testOtherLinesUntouched()
    {
        $update = new RemoveLicenceIconGapUnderlineUpdate();
        $fixed = $update->computeFix(self::REAL_WRAP);

        // The pre-existing .rlase typo (a separate, unrelated bug) is not this fix's concern.
        $this->assertStringContainsString('value.replacement.9.rlase', $fixed);
        $this->assertStringContainsString('value.wrap = <dd class="licence">|</dd>', $fixed);
    }
}
