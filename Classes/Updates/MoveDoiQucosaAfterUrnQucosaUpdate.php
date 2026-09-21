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
 * #2041 (ubl-26-5023): "DOI (Qucosa)" is a Qucosa identifier like "URN
 * Qucosa" and belongs behind it, not between the publication identifiers.
 * Sorts the row just after the last visible "URN Qucosa" row.
 */
class MoveDoiQucosaAfterUrnQucosaUpdate extends AbstractMetadataRowUpdate
{
    public function getIdentifier(): string
    {
        return 'dpfMoveDoiQucosaAfterUrnQucosa';
    }

    public function getTitle(): string
    {
        return 'Move "DOI (Qucosa)" behind "URN Qucosa" (#2041)';
    }

    public function getDescription(): string
    {
        return 'Sets the sorting of the doi row to 8 after the last visible urn row.';
    }

    public function computeChanges(array $rows): array
    {
        $urn = array_filter(self::mainRowsByIndexName($rows, 'urn'), static function (array $row): bool {
            return (int)$row['hidden'] === 0;
        });
        if ($urn === []) {
            return ['updates' => [], 'inserts' => []];
        }
        $wanted = max(array_map('intval', array_column($urn, 'sorting'))) + 8;

        $updates = [];
        foreach (self::mainRowsByIndexName($rows, 'doi') as $uid => $row) {
            if ((int)$row['sorting'] !== $wanted) {
                $updates[$uid]['sorting'] = $wanted;
            }
        }

        return ['updates' => $updates, 'inserts' => []];
    }
}
