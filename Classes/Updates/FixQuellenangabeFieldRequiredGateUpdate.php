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
 * Fixes #2039 red item "Quellenangaben" (Zeitschriftenartikel / Beitrag in
 * Sammelband / Beitrag in Konferenzband, e.g. qucosa-13299, qucosa-13450,
 * qucosa-12013, qucosa-12055, qucosa-33781, qucosa-13160, qucosa-11355):
 * the whole visible Quellenangabe prose row (uids 306/307/308/310/312/313/
 * 535/536/537/538) is gated behind `value.fieldRequired = original_title`.
 * TYPO3 stdWrap drops the *entire* value.* subtree — including the
 * `.append` chain that pulls in Verlag/Erscheinungsort/Jahr/Herausgeber/
 * Schriftenreihentitel — when that one field is empty.
 *
 * For records whose migrated data only has the legacy free-text
 * `mods:relatedItem[@type="host"]/mods:note[@type="z"]` ("Altdaten") and no
 * structured `mods:relatedItem[@type="host"]/mods:titleInfo/mods:title`,
 * original_title is empty, so the whole block vanishes even though
 * publisher/place/date/editor genuinely have data (confirmed live against
 * qucosa-13299's metsdisseminator output: original_title empty, publisher0
 * = "BioMed Central").
 *
 * Fix: remove the `value.fieldRequired = original_title` line. The primary
 * displayed content stays `{field:original_title}` (empty string when
 * absent, harmless), each `.append` sub-value already self-gates via its
 * own `noTrimWrap.fieldRequired`, and LandingPageAssembler already drops
 * the whole field if the final parsed value is empty — so a record with
 * nothing at all still renders nothing, no regression.
 */
class FixQuellenangabeFieldRequiredGateUpdate implements UpgradeWizardInterface
{
    // Matches CRLF (real DB storage) as well as bare LF.
    private const GATE_LINE_PATTERN = '/value\.fieldRequired = original_title\r?\n/';

    public function getIdentifier(): string
    {
        return 'dpfFixQuellenangabeFieldRequiredGate';
    }

    public function getTitle(): string
    {
        return 'Remove original_title fieldRequired gate from Quellenangabe wrap rows';
    }

    public function getDescription(): string
    {
        return 'Removes the "value.fieldRequired = original_title" line from the 10 visible '
            . 'Quellenangabe tx_dpf_metadata wrap rows, so Verlag/Erscheinungsort/Jahr/'
            . 'Herausgeber/Schriftenreihentitel still render for records that only have the '
            . 'legacy free-text host note and no structured host title (#2039).';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @param array $row of [uid, wrap]
     * @return array column/value pairs to update, or [] if not affected
     */
    public function computeFix(array $row): array
    {
        $wrap = (string) $row['wrap'];
        $fixed = preg_replace(self::GATE_LINE_PATTERN, '', $wrap);
        if ($fixed === $wrap) {
            return [];
        }

        return ['wrap' => $fixed];
    }

    protected function findAffectedRows(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        return $connection->executeQuery(
            'SELECT uid, wrap FROM tx_dpf_metadata WHERE deleted = 0 AND hidden = 0'
            . ' AND wrap LIKE ?',
            ['%value.fieldRequired = original_title%']
        )->fetchAll();
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        foreach ($this->findAffectedRows() as $row) {
            if (!empty($this->computeFix($row))) {
                return true;
            }
        }

        return false;
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        foreach ($this->findAffectedRows() as $row) {
            $fix = $this->computeFix($row);
            if (!empty($fix)) {
                $connection->update('tx_dpf_metadata', $fix, ['uid' => $row['uid']]);
            }
        }

        return true;
    }
}
