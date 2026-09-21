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

use EWW\Dpf\Updates\RenameNewLanguageLabelsToCodesUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class RenameNewLanguageLabelsToCodesUpdateTest extends UnitTestCase
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
    public function spelledOutLabelsBecomeCodes()
    {
        $rows = [
            $this->row(559, 'abstract_cat', ['label' => 'Abstract (Katalanisch)']),
            $this->row(556, 'classification_geo', ['label' => 'Freie Schlagwörter (Georgisch)']),
            $this->row(426, 'abstract_ita', ['label' => 'Abstract (ITA)']),
        ];

        $changes = (new RenameNewLanguageLabelsToCodesUpdate())->computeChanges($rows);

        $this->assertSame(
            [559 => ['label' => 'Abstract (CAT)'], 556 => ['label' => 'Freie Schlagwörter (GEO)']],
            $changes['updates']
        );
    }

    /**
     * @test
     */
    public function secondRunChangesNothing()
    {
        $rows = [$this->row(559, 'abstract_cat', ['label' => 'Abstract (CAT)'])];

        $this->assertSame([], (new RenameNewLanguageLabelsToCodesUpdate())->computeChanges($rows)['updates']);
    }
}
