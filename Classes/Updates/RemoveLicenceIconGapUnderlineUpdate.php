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
 * Fixes #2040 "the underline gap": the license link's icon+text markup
 * (tx_dpf_metadata uid 6, index_name=licence) has each `<img/>` directly
 * followed by a literal `&nbsp;` text node before the license label, e.g.
 * `<img .../>&nbsp;CC BY-ND 4.0`. That `&nbsp;` is a real text character
 * inside the underlined `<a>`, so it always renders underlined regardless
 * of any CSS on the `<img>` itself (text-decoration:none on the image
 * only suppresses the line under the image's own box, not a sibling text
 * node - a CSS-only fix on the image was tried first and didn't touch this).
 *
 * Removes the `&nbsp;` (and any stray literal space right after it) from
 * every `value.replacement.N.replace` line, so the anchor's text run is
 * just the image immediately followed by the label with no gap character
 * to underline. Visual spacing is restored via CSS margin on the image
 * instead (landingpage.scss).
 */
class RemoveLicenceIconGapUnderlineUpdate implements UpgradeWizardInterface
{
    private const INDEX_NAME = 'licence';

    /**
     * @param string $wrap
     * @return string
     */
    public function computeFix(string $wrap): string
    {
        return (string) preg_replace('~/>(&nbsp;)+ ?~', '/>', $wrap);
    }

    public function getIdentifier(): string
    {
        return 'dpfRemoveLicenceIconGapUnderline';
    }

    public function getTitle(): string
    {
        return 'Remove the &nbsp; gap between the license icon and label';
    }

    public function getDescription(): string
    {
        return 'Strips the literal &nbsp; text node between the license icon <img> and its '
            . 'label text (tx_dpf_metadata uid 6) so the underlined link has no gap character '
            . 'left to underline; spacing is restored via CSS margin instead.';
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
            'SELECT uid, wrap FROM tx_dpf_metadata WHERE deleted = 0 AND index_name = ?',
            [self::INDEX_NAME]
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

        return $this->computeFix((string) $row['wrap']) !== (string) $row['wrap'];
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        $row = $this->findRow();
        if ($row === null) {
            return true;
        }

        $fixed = $this->computeFix((string) $row['wrap']);
        if ($fixed !== (string) $row['wrap']) {
            $connection->update('tx_dpf_metadata', ['wrap' => $fixed], ['uid' => $row['uid']]);
        }

        return true;
    }
}
