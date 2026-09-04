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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Fixes #2047's "Beitrag/Interview in nicht-wiss. Medien" Quellenangabe row
 * (uid 537, `ubl-26-5073`): only HerausgeberIn (Person, `original_publisher`
 * /`_1`/`_2`) is spliced today - AutorIn (Person), ÜbersetzerIn (Person),
 * AutorIn (Institution) and HerausgeberIn (Institution) are missing, even
 * though the host relatedItem carries all of them (confirmed live, same
 * shape as the Preprint fixture).
 *
 * The new splices are appended AFTER the existing 17-level chain rather
 * than inserted in the middle, so none of the working append-depth numbers
 * change (avoids the renumbering-bug class from
 * AddThirdEditorSlotToConferenceQuellenangabeUpdate's history). Depth 18-25
 * key paths are still valid TypoScript regardless of visual position -
 * they're just further down the citation block than a hand-ordered
 * insertion would be.
 *
 * Uses the same 8 fields added by AddSourceCitationPersonInstitutionRolesUpdate
 * (co-requisite).
 *
 * Idempotent: does nothing if uid 537's wrap already contains original_author.
 */
class AddAuthorTranslatorToMediaContributionQuellenangabeUpdate implements UpgradeWizardInterface
{
    private const TARGET_UID_LABEL = 'Erschienen in';
    private const TARGET_INDEX_NAME = 'original_in_media';
    private const EXISTING_APPEND_DEPTH = 17;
    private const MARKER_FIELD = 'original_author';

    /**
     * @return array<int, array{field: string, prefix: string}>
     */
    private function newFields(): array
    {
        return [
            ['field' => 'original_author', 'prefix' => '|AutorIn: |'],
            ['field' => 'original_author_1', 'prefix' => '|AutorIn: |'],
            ['field' => 'original_translator', 'prefix' => '|ÜbersetzerIn: |'],
            ['field' => 'original_translator_1', 'prefix' => '|ÜbersetzerIn: |'],
            ['field' => 'original_institution_author', 'prefix' => '|AutorIn (Institution): |'],
            ['field' => 'original_institution_author_1', 'prefix' => '|AutorIn (Institution): |'],
            ['field' => 'original_institution_publisher', 'prefix' => '|HerausgeberIn (Institution): |'],
            ['field' => 'original_institution_publisher_1', 'prefix' => '|HerausgeberIn (Institution): |'],
        ];
    }

    public function buildAdditionalChain(): string
    {
        $depth = str_repeat('append.', self::EXISTING_APPEND_DEPTH);
        $lines = [];
        foreach ($this->newFields() as $f) {
            $depth .= 'append.';
            $key = 'value.' . rtrim($depth, '.');
            $lines[] = $key . ' = TEXT';
            $lines[] = $key . ' {';
            $lines[] = "\tvalue = {field:{$f['field']}}";
            $lines[] = "\tvalue.insertData = 1";
            $prefix = rtrim($f['prefix'], '|');
            $lines[] = "\tvalue.noTrimWrap = {$prefix}| <br />";
            $lines[] = "\tvalue.noTrimWrap.fieldRequired = {$f['field']}";
            $lines[] = '}';
        }
        return implode("\n", $lines);
    }

    /**
     * @param string $wrap current wrap column value for uid 537
     * @return string|null new wrap, or null if already applied / not the expected shape
     */
    public function computeFix(string $wrap): ?string
    {
        if (strpos($wrap, '{field:' . self::MARKER_FIELD . '}') !== false) {
            return null;
        }
        if (strpos($wrap, 'value.wrap3 = <dd>|</dd>') === false) {
            return null;
        }

        return str_replace(
            'value.wrap3 = <dd>|</dd>',
            $this->buildAdditionalChain() . "\nvalue.wrap3 = <dd>|</dd>",
            $wrap
        );
    }

    public function getIdentifier(): string
    {
        return 'dpfAddAuthorTranslatorToMediaContributionQuellenangabe';
    }

    public function getTitle(): string
    {
        return 'Add AutorIn/ÜbersetzerIn/Institution splices to Beitrag/Interview Quellenangabe';
    }

    public function getDescription(): string
    {
        return 'Extends tx_dpf_metadata uid 537\'s ("Erschienen in", type=contributionToPeriodical) '
            . 'Quellenangabe wrap with AutorIn (Person), ÜbersetzerIn (Person), AutorIn (Institution) and '
            . 'HerausgeberIn (Institution) splices, per #2047.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    protected function findRow(): ?array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        $row = $connection->executeQuery(
            'SELECT uid, wrap FROM tx_dpf_metadata WHERE deleted = 0 AND index_name = ? AND label = ?',
            [self::TARGET_INDEX_NAME, self::TARGET_UID_LABEL]
        )->fetch();

        return $row ?: null;
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        $row = $this->findRow();
        if ($row === null) {
            return false;
        }

        return $this->computeFix((string) $row['wrap']) !== null;
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        $row = $this->findRow();
        if ($row === null) {
            return true;
        }

        $fix = $this->computeFix((string) $row['wrap']);
        if ($fix !== null) {
            $connection->update('tx_dpf_metadata', ['wrap' => $fix], ['uid' => $row['uid']]);
        }

        return true;
    }
}
