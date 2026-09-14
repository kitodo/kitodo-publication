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

use EWW\Dpf\Updates\AddPositionalTooltipsToAuthorPublisherUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddPositionalTooltipsToAuthorPublisherUpdateTest extends UnitTestCase
{
    public function testGetIdentifier()
    {
        $this->assertSame(
            'dpfAddPositionalTooltipsToAuthorPublisher',
            (new AddPositionalTooltipsToAuthorPublisherUpdate())->getIdentifier()
        );
    }

    public function testComputeFixAddsTooltipsToAuthorPositions2And3()
    {
        $wizard = new AddPositionalTooltipsToAuthorPublisherUpdate();

        $wrap = 'value.override.append.value.noTrimWrap = |<dd class="author">|<span class="affiliation" '
            . 'style="display:none;">{field:affiliation2}</span></dd>|'
            . "\r\n"
            . 'value.override.append.append.value.noTrimWrap = |<dd class="author">|<span class="affiliation" '
            . 'style="display:none;">{field:affiliation3}</span></dd>|</dd>|';

        $fix = $wizard->computeFix(['index_name' => 'authors', 'wrap' => $wrap]);

        $this->assertStringContainsString(
            '<dd class="author" title="{field:authorIds2}">|<span class="affiliation" '
                . 'style="display:none;">{field:affiliation2}</span></dd>|',
            $fix['wrap']
        );
        $this->assertStringContainsString(
            '<dd class="author" title="{field:authorIds3}">|<span class="affiliation" '
                . 'style="display:none;">{field:affiliation3}</span></dd>|</dd>|',
            $fix['wrap']
        );
    }

    public function testComputeFixAddsTooltipsToPublisherPositions2And3()
    {
        $wizard = new AddPositionalTooltipsToAuthorPublisherUpdate();

        $wrap = "value.append {\r\n\tfieldRequired = publisher2\r\n\tvalue = {field:publisher2}\r\n"
            . "\tvalue.insertData = 1\r\n\tvalue.wrap = <li>|</li>\r\n}\r\n"
            . "value.append.append {\r\n\tfieldRequired = publisher3\r\n\tvalue = {field:publisher3}\r\n"
            . "\tvalue.insertData = 1\r\n\tvalue.wrap = <li>|</li>\r\n}";

        $fix = $wizard->computeFix(['index_name' => 'publisher', 'wrap' => $wrap]);

        $this->assertStringContainsString('value.wrap = <li title="{field:publisherIds2}">|</li>', $fix['wrap']);
        $this->assertStringContainsString('value.wrap = <li title="{field:publisherIds3}">|</li>', $fix['wrap']);
    }

    public function testComputeFixSkipsAlreadyFixedRow()
    {
        $wizard = new AddPositionalTooltipsToAuthorPublisherUpdate();

        $wrap = 'value.override.append.value.noTrimWrap = |<dd class="author" title="{field:authorIds2}">'
            . '|<span class="affiliation" style="display:none;">{field:affiliation2}</span></dd>|';

        $this->assertSame(
            [],
            $wizard->computeFix(['index_name' => 'authors', 'wrap' => $wrap])
        );
    }

    public function testComputeFixSkipsUnrelatedRow()
    {
        $wizard = new AddPositionalTooltipsToAuthorPublisherUpdate();

        $this->assertSame(
            [],
            $wizard->computeFix(['index_name' => 'original_publisher', 'wrap' => 'anything'])
        );
    }
}
