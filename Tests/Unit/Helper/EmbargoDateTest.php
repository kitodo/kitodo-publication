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
use EWW\Dpf\Helper\EmbargoDate;

class EmbargoDateTest extends UnitTestCase
{
    /**
     * @test
     */
    public function Year_only_ends_on_the_last_day_of_that_year()
    {
        $this->assertSame('2031-12-31', EmbargoDate::parse('2031')->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function Year_and_month_ends_on_the_last_day_of_that_month()
    {
        $this->assertSame('2027-12-31', EmbargoDate::parse('2027-12')->format('Y-m-d'));
        $this->assertSame('2028-02-29', EmbargoDate::parse('2028-02')->format('Y-m-d'));
        $this->assertSame('2027-02-28', EmbargoDate::parse('2027-02')->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function Full_date_is_kept_as_given()
    {
        $this->assertSame('2027-12-01', EmbargoDate::parse('2027-12-01')->format('Y-m-d'));
    }
}
