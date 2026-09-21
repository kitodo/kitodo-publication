<?php
namespace EWW\Dpf\Updates;

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

/**
 * #2041 (ubl-26-5007): "Übersetzter Titel (xx)" belongs directly after
 * "Alternativtitel", but the rows sorted from before the subtitle to after
 * the citation rows. Give every visible translated-title row a sorting
 * value just after alternative_title, in their current relative order.
 * Zitierfähige Url, the next row after Alternativtitel, is 256 further on,
 * so up to 31 rows fit in between.
 */
class MoveTranslatedTitleAfterAlternativeTitleUpdate extends AbstractMetadataRowUpdate
{
    private const ANCHOR = 'alternative_title';

    private const STEP = 8;

    public function getIdentifier(): string
    {
        return 'dpfMoveTranslatedTitleAfterAlternativeTitle';
    }

    public function getTitle(): string
    {
        return 'Move "Übersetzter Titel" rows directly after "Alternativtitel" (#2041)';
    }

    public function getDescription(): string
    {
        return 'Sets the sorting of the visible translated_title_* rows to the values just after alternative_title.';
    }

    public function computeChanges(array $rows): array
    {
        $anchor = self::mainRowsByIndexName($rows, self::ANCHOR);
        if ($anchor === []) {
            return ['updates' => [], 'inserts' => []];
        }
        $start = (int)reset($anchor)['sorting'];

        $translated = array_filter($rows, static function (array $row): bool {
            return preg_match('/^translated_title_[a-z0-9]+$/', $row['index_name']) === 1
                && (int)$row['l18n_parent'] === 0
                && (int)$row['sys_language_uid'] === 0
                && (int)$row['hidden'] === 0;
        });
        usort($translated, static function (array $a, array $b): int {
            return [(int)$a['sorting'], (int)$a['uid']] <=> [(int)$b['sorting'], (int)$b['uid']];
        });

        $updates = [];
        foreach ($translated as $i => $row) {
            $wanted = $start + self::STEP * ($i + 1);
            if ((int)$row['sorting'] !== $wanted) {
                $updates[(int)$row['uid']]['sorting'] = $wanted;
            }
        }

        return ['updates' => $updates, 'inserts' => []];
    }
}
