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

use EWW\Dpf\Updates\MoveTranslatedTitleAfterAlternativeTitleUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class MoveTranslatedTitleAfterAlternativeTitleUpdateTest extends UnitTestCase
{
    private function row(int $uid, string $indexName, array $extra = []): array
    {
        return array_merge([
            'uid' => $uid, 'pid' => 1, 'index_name' => $indexName, 'label' => '', 'wrap' => '',
            'sorting' => 0, 'hidden' => 0, 'sys_language_uid' => 0, 'l18n_parent' => 0, 'xpath' => '',
        ], $extra);
    }

    /**
     * @test
     */
    public function visibleTranslatedTitleRowsFollowTheAlternativeTitleInTheirOldOrder()
    {
        $rows = [
            $this->row(360, 'alternative_title', ['sorting' => 19968]),
            $this->row(351, 'translated_title_eng', ['sorting' => 20736]),
            $this->row(364, 'translated_title_swa', ['sorting' => 17664]),
            $this->row(347, 'translated_title1_eng', ['sorting' => 21760, 'hidden' => 1]),
            $this->row(356, 'translated_title_eng', ['sorting' => 19200, 'sys_language_uid' => 1, 'l18n_parent' => 351]),
            $this->row(1, 'translated_title', ['sorting' => 117504, 'hidden' => 1]),
        ];

        $changes = (new MoveTranslatedTitleAfterAlternativeTitleUpdate())->computeChanges($rows);

        $this->assertSame([364 => ['sorting' => 19976], 351 => ['sorting' => 19984]], $changes['updates']);
    }

    /**
     * @test
     */
    public function secondRunChangesNothing()
    {
        $rows = [
            $this->row(360, 'alternative_title', ['sorting' => 19968]),
            $this->row(364, 'translated_title_swa', ['sorting' => 19976]),
            $this->row(351, 'translated_title_eng', ['sorting' => 19984]),
        ];

        $this->assertSame([], (new MoveTranslatedTitleAfterAlternativeTitleUpdate())->computeChanges($rows)['updates']);
    }

    /**
     * @test
     */
    public function nothingHappensWithoutTheAnchorRow()
    {
        $rows = [$this->row(351, 'translated_title_eng', ['sorting' => 20736])];

        $this->assertSame([], (new MoveTranslatedTitleAfterAlternativeTitleUpdate())->computeChanges($rows)['updates']);
    }
}
