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

use EWW\Dpf\Updates\MoveNameIdentifiersIntoHoverBoxUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class MoveNameIdentifiersIntoHoverBoxUpdateTest extends UnitTestCase
{
    /**
     * @var MoveNameIdentifiersIntoHoverBoxUpdate
     */
    private $subject;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new MoveNameIdentifiersIntoHoverBoxUpdate();
    }

    public function testIdentifier()
    {
        $this->assertSame('dpfMoveNameIdentifiersIntoHoverBox', $this->subject->getIdentifier());
    }

    public function testAuthorSlotLosesTitleAndShowsIdentifiersInHoverBox()
    {
        $wrap = 'value.override = <dd class="author" title="{field:authorIds1}">{field:author1} '
            . '<span class="affiliation" style="display:none;">{field:affiliation1}</span></dd>';

        $this->assertSame(
            'value.override = <dd class="author">{field:author1} '
            . '<span class="affiliation" style="display:none;">{field:authorIds1}</span></dd>',
            $this->subject->convertWrap($wrap)
        );
    }

    public function testPublisherSlotLosesTitleAndShowsIdentifiersInHoverBox()
    {
        $wrap = "\tvalue.wrap = <li title=\"{field:publisherIds2}\">|"
            . '<span class="affiliation" style="display:none;">{field:affiliationEditor2}</span></li>';

        $this->assertSame(
            "\tvalue.wrap = <li>|"
            . '<span class="affiliation" style="display:none;">{field:publisherIds2}</span></li>',
            $this->subject->convertWrap($wrap)
        );
    }

    public function testConvertsEverySlotOfAWrap()
    {
        $wrap = '<dd class="author" title="{field:authorIds1}"><span class="affiliation">{field:affiliation1}</span></dd>'
            . '<dd class="author" title="{field:authorIds2}"><span class="affiliation">{field:affiliation2}</span></dd>'
            . '<dd class="author" title="{field:authorIds3}"><span class="affiliation">{field:affiliation3}</span></dd>';

        $converted = $this->subject->convertWrap($wrap);

        $this->assertStringNotContainsString('title=', $converted);
        $this->assertStringNotContainsString('{field:affiliation', $converted);
        $this->assertStringContainsString('{field:authorIds3}', $converted);
    }

    public function testKeepsAlreadyConvertedWrap()
    {
        $wrap = '<dd class="author">{field:author1} <span class="affiliation">{field:authorIds1}</span></dd>';

        $this->assertNull($this->subject->convertWrap($wrap));
    }

    public function testKeepsWrapWithoutNameIdentifierTitles()
    {
        $this->assertNull($this->subject->convertWrap('value.wrap = <dd class="author">|</dd>'));
    }
}
