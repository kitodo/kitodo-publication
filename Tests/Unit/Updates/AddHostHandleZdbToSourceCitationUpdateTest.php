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

use EWW\Dpf\Updates\AddHostHandleZdbToSourceCitationUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddHostHandleZdbToSourceCitationUpdateTest extends UnitTestCase
{
    private function row(int $uid, string $indexName, array $extra = []): array
    {
        return array_merge([
            'uid' => $uid, 'pid' => 1, 'index_name' => $indexName, 'label' => '', 'wrap' => '',
            'sorting' => 0, 'hidden' => 0, 'sys_language_uid' => 0, 'l18n_parent' => 0, 'xpath' => '',
        ], $extra);
    }

    private function fixture(int $uid): string
    {
        return (string)file_get_contents(__DIR__ . '/Fixtures/wrap_' . $uid . '.txt');
    }

    /**
     * @test
     */
    public function handleAndZdbFollowTheDoiLineInBothCitationRows()
    {
        $update = new AddHostHandleZdbToSourceCitationUpdate();

        foreach ([537, 545] as $uid) {
            $wrap = $update->rebuildWrap($this->fixture($uid));

            $this->assertNotNull($wrap, (string)$uid);
            $this->assertRegExp('/\|DOI: \| <br \/>.*\|Handle: \| <br \/>.*\|ZDB-ID: \| <br \/>/s', $wrap);
            $this->assertNull($update->rebuildWrap($wrap), 'second run ' . $uid);
        }
    }

    /**
     * @test
     */
    public function computeChangesAddsTheTwoFields()
    {
        $rows = [$this->row(304, 'original_publisher', ['pid' => 7])];

        $inserts = (new AddHostHandleZdbToSourceCitationUpdate())->computeChanges($rows)['inserts'];

        $this->assertSame(['original_handle', 'original_zdb'], array_column($inserts, 'index_name'));
        $this->assertSame(7, $inserts[0]['pid']);
        $this->assertStringContainsString('@type="handle"', $inserts[0]['xpath']);
    }
}
