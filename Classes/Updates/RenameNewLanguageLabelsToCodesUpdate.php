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
 * #2047 (ubl-26-5006): the older language rows carry a three-letter code in
 * the label ("Abstract (ITA)"), but the rows added for Georgian, Macedonian,
 * Swedish and Catalan spell the language out. Use ISO 639-2/B codes for
 * these, like the older rows do (CHI, CZE, POL, ...).
 */
class RenameNewLanguageLabelsToCodesUpdate extends AbstractMetadataRowUpdate
{
    /** index_name => [old label, new label] */
    private const LABELS = [
        'abstract_cat' => ['Abstract (Katalanisch)', 'Abstract (CAT)'],
        'classification_geo' => ['Freie Schlagwörter (Georgisch)', 'Freie Schlagwörter (GEO)'],
        'classification_mkd' => ['Freie Schlagwörter (Mazedonisch)', 'Freie Schlagwörter (MAC)'],
        'classification_swe' => ['Freie Schlagwörter (Schwedisch)', 'Freie Schlagwörter (SWE)'],
    ];

    public function getIdentifier(): string
    {
        return 'dpfRenameNewLanguageLabelsToCodes';
    }

    public function getTitle(): string
    {
        return 'Use language codes in the labels of the Georgian, Macedonian, Swedish and Catalan rows (#2047)';
    }

    public function getDescription(): string
    {
        return 'Renames abstract_cat, classification_geo, classification_mkd and classification_swe '
            . 'from a spelled-out language name to its three-letter code.';
    }

    public function computeChanges(array $rows): array
    {
        $updates = [];
        foreach (self::LABELS as $indexName => [$old, $new]) {
            foreach (self::mainRowsByIndexName($rows, $indexName) as $uid => $row) {
                if ($row['label'] === $old) {
                    $updates[$uid]['label'] = $new;
                }
            }
        }

        return ['updates' => $updates, 'inserts' => []];
    }
}
