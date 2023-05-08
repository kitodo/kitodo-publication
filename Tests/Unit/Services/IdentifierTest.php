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

use Nimut\TestingFramework\TestCase\UnitTestCase;
use EWW\Dpf\Services\Identifier\Identifier;

class IdentifierTest extends UnitTestCase {

    /**
     * @test
     */
    public function Identifies_integer_as_UID() {
        $this->assertTrue(Identifier::isUid(2345));
    }

    /**
     * @test
     */
    public function Identifies_process_number() {
        $this->assertTrue(Identifier::isProcessNumber("FOO-23-5"));
    }

}
