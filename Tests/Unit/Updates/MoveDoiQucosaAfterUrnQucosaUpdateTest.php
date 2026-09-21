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

use EWW\Dpf\Updates\MoveDoiQucosaAfterUrnQucosaUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class MoveDoiQucosaAfterUrnQucosaUpdateTest extends UnitTestCase
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
    public function doiFollowsTheLastVisibleUrnRow()
    {
        $rows = [
            $this->row(16, 'urn', ['sorting' => 113664]),
            $this->row(10, 'urn', ['sorting' => 115200]),
            $this->row(11, 'urn', ['sorting' => 999999, 'hidden' => 1]),
            $this->row(253, 'doi', ['sorting' => 46080]),
        ];

        $changes = (new MoveDoiQucosaAfterUrnQucosaUpdate())->computeChanges($rows);

        $this->assertSame([253 => ['sorting' => 115208]], $changes['updates']);
    }

    /**
     * @test
     */
    public function secondRunChangesNothing()
    {
        $rows = [
            $this->row(10, 'urn', ['sorting' => 115200]),
            $this->row(253, 'doi', ['sorting' => 115208]),
        ];

        $this->assertSame([], (new MoveDoiQucosaAfterUrnQucosaUpdate())->computeChanges($rows)['updates']);
    }
}
