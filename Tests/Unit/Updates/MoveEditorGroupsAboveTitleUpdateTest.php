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

use EWW\Dpf\Updates\MoveEditorGroupsAboveTitleUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class MoveEditorGroupsAboveTitleUpdateTest extends UnitTestCase
{
    /** Real sorting/wrap values as of 2026-09-03, fetched from sdvqucosa-test-web01. */
    private const TITLE_SORTING = 17408;
    private const PUBLISHER_SORTING = 89088;
    private const CORPORATION_EDITOR_SORTING = 102656;

    private const PUBLISHER_WRAP = "key.wrap = <dt>|</dt>\r\n"
        . "value.fieldRequired = publisher1\r\n"
        . "value.dataWrap = <li>{field:publisher1}</li>\r\n"
        . "value.append = TEXT\r\n"
        . "value.wrap3 = <dd><ul>|</ul></dd>\r\n";

    private const CORPORATION_EDITOR_WRAP = "key.wrap = <dt>|</dt>\r\n"
        . "value.required = 1\r\n"
        . "value.wrap = <dd>|</dd>\r\n";

    public function testMovesPersonalEditorGroupAboveTitleAndAddsClassHooks()
    {
        $wizard = new MoveEditorGroupsAboveTitleUpdate();

        $fix = $wizard->computeFix(
            ['uid' => 96, 'index_name' => 'publisher', 'sorting' => self::PUBLISHER_SORTING, 'wrap' => self::PUBLISHER_WRAP],
            self::TITLE_SORTING
        );

        $this->assertLessThan(self::TITLE_SORTING, $fix['sorting']);
        $this->assertStringContainsString('key.wrap = <dt class="editor">|</dt>', $fix['wrap']);
        $this->assertStringContainsString(
            'value.wrap3 = <dd class="editor"><ul class="editor-list">|</ul></dd>',
            $fix['wrap']
        );
    }

    public function testMovesInstitutionalEditorRowAboveTitleAndAddsClassHooks()
    {
        $wizard = new MoveEditorGroupsAboveTitleUpdate();

        $fix = $wizard->computeFix(
            ['uid' => 43, 'index_name' => 'corporation_editor', 'sorting' => self::CORPORATION_EDITOR_SORTING, 'wrap' => self::CORPORATION_EDITOR_WRAP],
            self::TITLE_SORTING
        );

        $this->assertLessThan(self::TITLE_SORTING, $fix['sorting']);
        $this->assertStringContainsString('key.wrap = <dt class="editor">|</dt>', $fix['wrap']);
        $this->assertStringContainsString('value.wrap = <dd class="editor">|</dd>', $fix['wrap']);
    }

    public function testAlreadyMovedRowNeedsNoFix()
    {
        $wizard = new MoveEditorGroupsAboveTitleUpdate();

        $firstFix = $wizard->computeFix(
            ['uid' => 96, 'index_name' => 'publisher', 'sorting' => self::PUBLISHER_SORTING, 'wrap' => self::PUBLISHER_WRAP],
            self::TITLE_SORTING
        );

        $fix = $wizard->computeFix(
            ['uid' => 96, 'index_name' => 'publisher', 'sorting' => $firstFix['sorting'], 'wrap' => $firstFix['wrap']],
            self::TITLE_SORTING
        );

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new MoveEditorGroupsAboveTitleUpdate();

        $fix = $wizard->computeFix(
            ['uid' => 395, 'index_name' => 'authors', 'sorting' => 9728, 'wrap' => 'key.wrap = <dt class="author">|</dt>'],
            self::TITLE_SORTING
        );

        $this->assertSame([], $fix);
    }
}
