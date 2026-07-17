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
 * Landing page layout fixes in tx_dpf_metadata content (#2040):
 *
 * - The Förder-/Projektangaben wrap carries `ifNull = <br/>` / `ifEmpty =
 *   <br/>` on every funder 1-10 sub-field. For documents with fewer funders
 *   these emit a run of literal <br/> tags, and — because ifNull runs before
 *   required in stdWrap order — the injected <br/> lets the funder separator
 *   `wrap = <hr>` fire despite `required = 1`, producing the oversized gap
 *   seen on qucosa-82985 (<br/><br/><br/><hr><br/><br/> at the end of the
 *   dd). Stripping the ifNull/ifEmpty lines removes the junk; present values
 *   keep their line breaks via the fieldRequired-gated noTrimWrap, and the
 *   <hr> still renders between two real funders.
 * - The Publikationstyp row gets class="doctype" hooks on its dt/dd wrap and
 *   moves to the top of the field list (sorting 512, ahead of artist at
 *   6144), so slub_web_qucosa can render it as a badge above the title, as
 *   requested in #2040. The English overlay row carries its own byte-equal
 *   wrap copy (getRecordOverlay() prefers it), so it gets the class hooks
 *   too; overlay sorting is unused and left alone.
 *
 * Content-only, same idempotent pattern as FixMetadataXpathAndLabelsUpdate:
 * rows are matched by content, not uid, so the wizard is safe to re-run and
 * to run on other environments.
 */
class FixLandingPageLayoutMetadataUpdate implements UpgradeWizardInterface
{
    private const TYPE_SORTING_TOP = 512;

    public function getIdentifier(): string
    {
        return 'dpfFixLandingPageLayoutMetadata';
    }

    public function getTitle(): string
    {
        return 'Fix landing page layout metadata (funding wrap junk, doctype badge hooks) in tx_dpf_metadata';
    }

    public function getDescription(): string
    {
        return 'Strips ifNull/ifEmpty <br/> junk from the Förder-/Projektangaben wrap (oversized gap '
            . 'on the landing page) and adds class="doctype" hooks plus top sorting to the '
            . 'Publikationstyp row so the frontend can render it as a badge above the title.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * Compute the field changes a row needs, or [] if it's already correct.
     *
     * @param array $row of [index_name, sys_language_uid, l18n_parent, sorting, wrap]
     * @return array<string, string|int> changed fields only
     */
    public function computeFix(array $row): array
    {
        $updates = [];

        if (strpos($row['index_name'], 'project_funding') === 0) {
            $wrap = preg_replace('/^[ \t]*if(?:Null|Empty) = <br\/>[ \t]*\r?\n/m', '', $row['wrap']);
            if ($wrap !== $row['wrap']) {
                $updates['wrap'] = $wrap;
            }
        }

        if ($row['index_name'] === 'type') {
            $wrap = str_replace(
                ['<dt>|</dt>', '<dd>|</dd>'],
                ['<dt class="doctype">|</dt>', '<dd class="doctype">|</dd>'],
                $row['wrap']
            );
            if ($wrap !== $row['wrap']) {
                $updates['wrap'] = $wrap;
            }
            if ((int)$row['l18n_parent'] === 0 && (int)$row['sorting'] !== self::TYPE_SORTING_TOP) {
                $updates['sorting'] = self::TYPE_SORTING_TOP;
            }
        }

        return $updates;
    }

    protected function findRowsNeedingFix(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        $rows = $connection->executeQuery(
            'SELECT uid, index_name, sys_language_uid, l18n_parent, sorting, wrap'
            . ' FROM tx_dpf_metadata WHERE deleted = 0 AND hidden = 0'
        )->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $fix = $this->computeFix($row);
            if (!empty($fix)) {
                $result[$row['uid']] = $fix;
            }
        }

        return $result;
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        return !empty($this->findRowsNeedingFix());
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        foreach ($this->findRowsNeedingFix() as $uid => $fix) {
            $connection->update('tx_dpf_metadata', $fix, ['uid' => $uid]);
        }

        return true;
    }
}
