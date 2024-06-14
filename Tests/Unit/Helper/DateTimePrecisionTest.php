<?php
namespace EWW\Dpf\Tests\Unit\Helper;

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

use Nimut\TestingFramework\TestCase\UnitTestCase;
use EWW\Dpf\Helper\DateTimePrecision;

class DateTimePrecisionTest extends UnitTestCase {

    /**
     * @test
     */
    public function Truncates_microseconds_precision_down_to_six_digits() {
        $datetime = "2024-06-12T08:31:11.570355509Z";
        $this->assertEquals("2024-06-12T08:31:11.570355Z", DateTimePrecision::reducePrecision($datetime));
    }

    /**
     * @test
     */
    public function No_truncation_on_six_digit_precision_() {
        $datetime = "2024-06-12T08:31:11.570355Z";
        $this->assertEquals("2024-06-12T08:31:11.570355Z", DateTimePrecision::reducePrecision($datetime));
    }

    /**
     * @test
     */
    public function No_truncation_on_less_than_six_digit_precision_() {
        $datetime = "2024-06-12T08:31:11.570Z";
        $this->assertEquals("2024-06-12T08:31:11.570Z", DateTimePrecision::reducePrecision($datetime));
    }

}
