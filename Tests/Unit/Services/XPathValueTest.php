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

use EWW\Dpf\Services\Xml\XPathValue;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class XPathValueTest extends UnitTestCase
{
    public function testEscapeNoQuotes()
    {
        $this->assertEquals('plain value', XPathValue::escape('plain value'));
    }

    public function testEscapeDoubleQuotes()
    {
        $this->assertEquals('My \\"Special\\" Project', XPathValue::escape('My "Special" Project'));
    }

    public function testEscapeOnlyQuote()
    {
        $this->assertEquals('\\"', XPathValue::escape('"'));
    }

    public function testEscapeEmptyString()
    {
        $this->assertEquals('', XPathValue::escape(''));
    }
}
