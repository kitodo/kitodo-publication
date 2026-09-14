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

use EWW\Dpf\Updates\AddAffiliationToEditorSlotsUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddAffiliationToEditorSlotsUpdateTest extends UnitTestCase
{
    private function invokePrivate(object $object, string $method, array $args = [])
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }

    public function testGetIdentifier()
    {
        $this->assertSame(
            'dpfAddAffiliationToEditorSlots',
            (new AddAffiliationToEditorSlotsUpdate())->getIdentifier()
        );
    }

    public function testComputePublisherWrapFixAddsAffiliationSpansToAllThreePositions()
    {
        $wizard = new AddAffiliationToEditorSlotsUpdate();

        $wrap = 'value.dataWrap = <li title="{field:publisherIds1}">{field:publisher1}</li>'
            . "\r\n"
            . 'value.append {' . "\r\n"
            . "\tvalue.wrap = <li title=\"{field:publisherIds2}\">|</li>\r\n"
            . '}' . "\r\n"
            . 'value.append.append {' . "\r\n"
            . "\tvalue.wrap = <li title=\"{field:publisherIds3}\">|</li>\r\n"
            . '}';

        $fix = $this->invokePrivate($wizard, 'computePublisherWrapFix', [$wrap]);

        $this->assertNotNull($fix);
        $this->assertStringContainsString(
            'value.dataWrap = <li title="{field:publisherIds1}">{field:publisher1} '
                . '<span class="affiliation" style="display:none;">{field:affiliationEditor1}</span></li>',
            $fix
        );
        $this->assertStringContainsString(
            'value.wrap = <li title="{field:publisherIds2}">|<span class="affiliation" '
                . 'style="display:none;">{field:affiliationEditor2}</span></li>',
            $fix
        );
        $this->assertStringContainsString(
            'value.wrap = <li title="{field:publisherIds3}">|<span class="affiliation" '
                . 'style="display:none;">{field:affiliationEditor3}</span></li>',
            $fix
        );
    }

    public function testComputePublisherWrapFixReturnsNullWhenAlreadyFixed()
    {
        $wizard = new AddAffiliationToEditorSlotsUpdate();

        $wrap = 'value.dataWrap = <li title="{field:publisherIds1}">{field:publisher1} '
            . '<span class="affiliation" style="display:none;">{field:affiliationEditor1}</span></li>';

        $this->assertNull($this->invokePrivate($wizard, 'computePublisherWrapFix', [$wrap]));
    }

    public function testComputePublisherWrapFixReturnsNullForUnrelatedWrap()
    {
        $wizard = new AddAffiliationToEditorSlotsUpdate();

        $this->assertNull($this->invokePrivate($wizard, 'computePublisherWrapFix', ['anything else']));
    }
}
