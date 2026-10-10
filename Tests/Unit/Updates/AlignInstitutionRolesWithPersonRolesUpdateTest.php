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

use EWW\Dpf\Updates\AlignInstitutionRolesWithPersonRolesUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AlignInstitutionRolesWithPersonRolesUpdateTest extends UnitTestCase
{
    /**
     * @var AlignInstitutionRolesWithPersonRolesUpdate
     */
    private $subject;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new AlignInstitutionRolesWithPersonRolesUpdate();
    }

    public function testIdentifierAndTitle()
    {
        $this->assertSame('dpfAlignInstitutionRolesWithPersonRoles', $this->subject->getIdentifier());
        $this->assertStringContainsString('Sonstige', $this->subject->getTitle());
    }

    public function testMovesInstitutionToRightAfterOtherPerson()
    {
        // real test-server values (#2051): corporation_other=114688, other=101376
        $this->assertSame(101377, $this->subject->computeNewSorting(114688, 101376));
    }

    public function testDoesNotMoveWhenAlreadyRightAfterOtherPerson()
    {
        $this->assertNull($this->subject->computeNewSorting(101377, 101376));
    }

    public function testWrapsInstitutionValueInBulletList()
    {
        // real test-server wrap of corporation_other, CRLF line ends (#2051)
        $wrap = "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.wrap = <dd>|</dd>";

        $this->assertSame(
            "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.wrap = <dd><ul><li>|</li></ul></dd>",
            $this->subject->bulletWrap($wrap)
        );
    }

    public function testWrapsInstitutionValueWithLfLineEnds()
    {
        // real test-server wrap of corporation_contributor, LF line ends (#2051)
        $wrap = "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd>|</dd>";

        $this->assertSame(
            "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd><ul><li>|</li></ul></dd>",
            $this->subject->bulletWrap($wrap)
        );
    }

    public function testKeepsAlreadyBulletedWrap()
    {
        $wrap = "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd><ul><li>|</li></ul></dd>";

        $this->assertNull($this->subject->bulletWrap($wrap));
    }

    public function testKeepsWrapWithoutPlainDdValueWrap()
    {
        $wrap = "key.wrap = <dt class=\"author\">|</dt>\nvalue.wrap = <dd class=\"author\">|</dd>";

        $this->assertNull($this->subject->bulletWrap($wrap));
    }
}
