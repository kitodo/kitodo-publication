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

use EWW\Dpf\Updates\MergeHostRoleLinesUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class MergeHostRoleLinesUpdateTest extends UnitTestCase
{
    private function row(int $uid, string $indexName, array $extra = []): array
    {
        return array_merge([
            'uid' => $uid, 'pid' => 1, 'index_name' => $indexName, 'label' => '', 'wrap' => '',
            'sorting' => 0, 'hidden' => 0, 'sys_language_uid' => 0, 'l18n_parent' => 0, 'xpath' => '',
        ], $extra);
    }

    /** Real wraps of uid 307 and uid 537, fetched from sdvqucosa-test-web01 on 2026-09-21. */
    private function fixture(int $uid): string
    {
        return (string)file_get_contents(__DIR__ . '/Fixtures/wrap_' . $uid . '.txt');
    }

    /**
     * @test
     */
    public function konferenzbandWrapGetsOneLinePerRole()
    {
        $wrap = (new MergeHostRoleLinesUpdate())->rebuildWrap($this->fixture(307));

        $this->assertNotNull($wrap);
        $this->assertStringContainsString('|Herausgegeben von: | <br />', $wrap);
        $this->assertStringContainsString('|AutorIn: | <br />', $wrap);
        $this->assertStringContainsString('|ÜbersetzerIn: | <br />', $wrap);
        foreach (['original_publisher', 'original_publisher_1', 'original_publisher_2'] as $slot) {
            $this->assertStringNotContainsString('{field:' . $slot . '}', $wrap);
        }
        // Untouched blocks survive the rebuild.
        $this->assertStringContainsString('|Erscheinungsort: | <br />', $wrap);
        $this->assertStringContainsString('|DOI: | <br />', $wrap);
    }

    /**
     * @test
     */
    public function mediaWrapLosesEverySlotBlock()
    {
        $wrap = (new MergeHostRoleLinesUpdate())->rebuildWrap($this->fixture(537));

        $this->assertNotNull($wrap);
        $this->assertSame(1, substr_count($wrap, '|Herausgegeben von: | <br />'));
        $this->assertSame(1, substr_count($wrap, '|AutorIn: | <br />'));
        $this->assertStringNotContainsString('HerausgeberIn (Institution)', $wrap);
        $this->assertStringNotContainsString('{field:original_author_1}', $wrap);
        $this->assertStringContainsString('|ISSN: | <br />', $wrap);
    }

    /**
     * @test
     */
    public function appendDepthsStayConsecutive()
    {
        $wrap = (new MergeHostRoleLinesUpdate())->rebuildWrap($this->fixture(307));

        preg_match_all('/^(value(?:\.append)+) = TEXT\r?$/m', $wrap, $m);
        $this->assertNotEmpty($m[1]);
        foreach ($m[1] as $i => $path) {
            $this->assertSame('value' . str_repeat('.append', $i + 1), $path);
        }
    }

    /**
     * @test
     */
    public function secondRunChangesNothing()
    {
        $update = new MergeHostRoleLinesUpdate();
        $once = $update->rebuildWrap($this->fixture(307));

        $this->assertNull($update->rebuildWrap($once));
    }

    /**
     * @test
     */
    public function computeChangesAddsRowsAndHidesStandaloneRows()
    {
        $rows = [
            $this->row(304, 'original_publisher', ['pid' => 7]),
            $this->row(580, 'original_author'),
            $this->row(586, 'original_institution_publisher'),
            $this->row(307, 'original_in_proceeding0000000', ['wrap' => $this->fixture(307)]),
        ];

        $changes = (new MergeHostRoleLinesUpdate())->computeChanges($rows);

        $this->assertSame(
            ['host_editors_all', 'host_authors_all', 'host_translators_all'],
            array_column($changes['inserts'], 'index_name')
        );
        $this->assertSame(7, $changes['inserts'][0]['pid']);
        $this->assertSame(1, $changes['updates'][580]['hidden']);
        $this->assertSame(1, $changes['updates'][586]['hidden']);
        $this->assertArrayHasKey('wrap', $changes['updates'][307]);
    }

    /**
     * @test
     */
    public function computeChangesIsEmptyOnceApplied()
    {
        $update = new MergeHostRoleLinesUpdate();
        $rows = [
            $this->row(304, 'original_publisher'),
            $this->row(580, 'original_author', ['hidden' => 1]),
            $this->row(307, 'original_in_proceeding0000000', ['wrap' => $update->rebuildWrap($this->fixture(307))]),
            $this->row(901, 'host_editors_all'),
            $this->row(902, 'host_authors_all'),
            $this->row(903, 'host_translators_all'),
        ];

        $this->assertSame(['updates' => [], 'inserts' => []], $update->computeChanges($rows));
    }
}
