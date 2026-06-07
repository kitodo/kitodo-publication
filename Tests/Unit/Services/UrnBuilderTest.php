<?php
namespace EWW\Dpf\Tests\Unit\Services;

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

use InvalidArgumentException;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use EWW\Dpf\Services\Identifier\UrnBuilder;

class UrnBuilderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function Calculates_check_digit_for_known_qucosa_urn()
    {
        // Docblock claims check digit 0 for qucosa-8765 — incorrect.
        // Actual algorithm output is 4. Code is correct; docblock is wrong.
        $builder = new UrnBuilder('bsz', '14');
        $this->assertSame(4, $builder->getCheckDigit('qucosa-8765'));
    }

    /**
     * @test
     */
    public function Generates_complete_urn_with_check_digit()
    {
        $builder = new UrnBuilder('bsz', '14');
        $this->assertSame('urn:nbn:de:bsz:14-qucosa-87654', $builder->getUrn('qucosa-8765'));
    }

    /**
     * @test
     */
    public function Check_digit_is_integer()
    {
        $builder = new UrnBuilder('bsz', '14');
        $this->assertIsInt($builder->getCheckDigit('qucosa-8765'));
    }

    /**
     * @test
     */
    public function Rejects_invalid_snid1_with_digits()
    {
        $this->expectException(InvalidArgumentException::class);
        new UrnBuilder('bsz14', '14');
    }

    /**
     * @test
     */
    public function Rejects_invalid_niss_with_special_chars()
    {
        $this->expectException(InvalidArgumentException::class);
        $builder = new UrnBuilder('bsz', '14');
        $builder->getCheckDigit('qucosa 8765');
    }
}
