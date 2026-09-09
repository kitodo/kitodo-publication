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
 * Partial fix for #2041 ("Andere Ausgabe": DOI und Link, `ubl-26-5023`).
 * DOI and Link are identifiers of the publication just like ISBN/ISSN
 * above and are meant to move up next to them - but that promotion is NOT
 * done here; see the deferred-work note below. This wizard only does the
 * two pieces confirmed safe and independently correct:
 *
 * 1. Narrows uid 253 ("DOI (Qucosa)")'s xpath to the primary identifier
 *    only. It previously unioned in every "Andere Ausgabe" DOI too
 *    (`./mods:relatedItem[@type="otherversion"]/...`, lowercase per the
 *    METS disseminator - see feedback_metsdisseminator_ground_truth),
 *    double-showing every "Andere Ausgabe" DOI under the wrong label.
 * 2. Strips doi_N/url_N out of the 5-slot "Andere Ausgabe" block (uid 225
 *    `otherVersion00`, and its English-overlay twin uid 224
 *    `otherVersion0`, l18n_parent=225) - it now only carries title
 *    (otherVersion_N) and note (note_N) per slot.
 *
 * DEFERRED, NOT BUILT HERE: a first attempt tried repurposing uid 268
 * (`DOI0`) and uid 269 (`PURL0`) as new standalone "DOI"/"Link" rows to
 * show the promoted values - both looked like dead placeholders (hidden=0,
 * empty xpath/wrap, no PHP reference) but turned out to be the English
 * overlays of uid 253/254 (sys_language_uid=1, l18n_parent=253/254) -
 * findRenderableFields()'s `l18n_parent = 0` filter means nothing put
 * there can ever render on the main (German) surface. Reverted; those two
 * rows are untouched by this wizard. The real fix needs genuinely NEW rows
 * (sys_language_uid=0, l18n_parent=0), inserted the way
 * AddRelationDetailFieldsUpdate does, with `sorting` placed after
 * identifier_pmid (46304) to land inside the identifier group - own
 * follow-up, not attempted again in this wizard until built correctly.
 *
 * Net effect while the promotion is deferred: any document with real
 * "Andere Ausgabe" DOI/Link content no longer shows it anywhere on the
 * landing page (a known, accepted gap until the follow-up ships) - title
 * and note (the fields most likely to carry real prose, per this batch's
 * "don't lose real content" precedent) are unaffected.
 */
class PromoteOtherVersionIdentifiersUpdate implements UpgradeWizardInterface
{
    private const OTHER_VERSION_BLOCK_WRAP = "key.wrap = <dt>|</dt>\r\n\r\nvalue.append = COA\r\nvalue.append {\r\n"
        . "1 = COA\r\n1.if {\r\n\tisTrue.cObject = COA\r\n\tisTrue.cObject {\r\n\t\t10 = TEXT\r\n\t\t10.field = note1\r\n"
        . "\t\t20 = TEXT\r\n\t\t20.field = otherVersion1\r\n\t}\r\n}\r\n1 {\r\n\t20 = TEXT\r\n\t20 {\r\n"
        . "\t\tvalue.fieldRequired = otherVersion1\r\n\t\tvalue.dataWrap = {field:otherVersion1}\r\n"
        . "\t\tvalue.dataWrap.typolink.parameter = /id/{field:otherVersion_local1}\r\n"
        . "\t\tvalue.dataWrap.typolink.parameter.fieldRequired = otherVersion_local1\r\n"
        . "\t\tvalue.wrap = |<br/>\r\n\t}\r\n\t10 = TEXT\r\n\t10 {\r\n\t\tfield = note1\r\n\t\trequired = 1\r\n"
        . "\t\twrap = |<br/>\r\n\t}\r\n\twrap = <dd>|</dd>\r\n}\r\n"
        . "2 = COA\r\n2.if {\r\n\tisTrue.cObject = COA\r\n\tisTrue.cObject {\r\n\t\t10 = TEXT\r\n\t\t10.field = note_2\r\n"
        . "\t\t20 = TEXT\r\n\t\t20.field = otherVersion2\r\n\t}\r\n}\r\n2 {\r\n\t20 = TEXT\r\n\t20 {\r\n"
        . "\t\tvalue.fieldRequired = otherVersion2\r\n\t\tvalue.dataWrap = {field:otherVersion2}\r\n"
        . "\t\tvalue.dataWrap.typolink.parameter = /id/{field:otherVersion_local2}\r\n"
        . "\t\tvalue.dataWrap.typolink.parameter.fieldRequired = otherVersion_local2\r\n"
        . "\t\tvalue.wrap = |<br/>\r\n\t}\r\n\t10 = TEXT\r\n\t10 {\r\n\t\tfield = note2\r\n\t\trequired = 1\r\n"
        . "\t\twrap = |<br/>\r\n\t}\r\n\twrap = <dd>|</dd>\r\n}\r\n"
        . "3 = COA\r\n3.if {\r\n\tisTrue.cObject = COA\r\n\tisTrue.cObject {\r\n\t\t10 = TEXT\r\n\t\t10.field = note3\r\n"
        . "\t\t20 = TEXT\r\n\t\t20.field = otherVersion3\r\n\t}\r\n}\r\n3 {\r\n\t20 = TEXT\r\n\t20 {\r\n"
        . "\t\tvalue.fieldRequired = otherVersion3\r\n\t\tvalue.dataWrap = {field:otherVersion3}\r\n"
        . "\t\tvalue.dataWrap.typolink.parameter = /id/{field:otherVersion_local3}\r\n"
        . "\t\tvalue.dataWrap.typolink.parameter.fieldRequired = otherVersion_local3\r\n"
        . "\t\tvalue.wrap = |<br/>\r\n\t}\r\n\t10 = TEXT\r\n\t10 {\r\n\t\tfield = note3\r\n\t\trequired = 1\r\n"
        . "\t\twrap = |<br/>\r\n\t}\r\n\twrap = <dd>|</dd>\r\n}\r\n"
        . "4 = COA\r\n4.if {\r\n\tisTrue.cObject = COA\r\n\tisTrue.cObject {\r\n\t\t10 = TEXT\r\n\t\t10.field = otherVersion4\r\n"
        . "\t\t20 = TEXT\r\n\t\t20.field = note4\r\n\t}\r\n}\r\n4 {\r\n\t10 = TEXT\r\n\t10 {\r\n"
        . "\t\tvalue.fieldRequired = otherVersion4\r\n\t\tvalue.dataWrap = {field:otherVersion4}\r\n"
        . "\t\twrap = |<br />\r\n\t}\r\n\t20 = TEXT\r\n\t20 {\r\n\t\tfield = note4\r\n\t\trequired = 1\r\n"
        . "\t\twrap = |<br />\r\n\t}\r\n\twrap = <dd>|</dd>\r\n}\r\n"
        . "5 = COA\r\n5.if {\r\n\tisTrue.cObject = COA\r\n\tisTrue.cObject {\r\n\t\t10 = TEXT\r\n\t\t10.field = otherVersion5\r\n"
        . "\t\t20 = TEXT\r\n\t\t20.field = note5\r\n\t}\r\n}\r\n5 {\r\n\t10 = TEXT\r\n\t10 {\r\n"
        . "\t\tvalue.fieldRequired = otherVersion5\r\n\t\tvalue.dataWrap = {field:otherVersion5}\r\n"
        . "\t\twrap = |<br />\r\n\t}\r\n\t20 = TEXT\r\n\t20 {\r\n\t\tfield = note5\r\n\t\trequired = 1\r\n"
        . "\t\twrap = |<br />\r\n\t}\r\n\twrap = <dd>|</dd>\r\n}\r\n}\r\n\twrap = <dd>|</dd>";

    public static function narrowedDoiQucosaXpath(): string
    {
        return './mods:identifier[@type="doi"]';
    }

    public static function otherVersionBlockWrap(): string
    {
        return self::OTHER_VERSION_BLOCK_WRAP;
    }

    /**
     * @return array<int, array<string, mixed>> uid => fields to update
     */
    public function getRowUpdates(): array
    {
        return [
            253 => ['xpath' => self::narrowedDoiQucosaXpath()],
            225 => ['wrap' => self::OTHER_VERSION_BLOCK_WRAP],
            224 => ['wrap' => self::OTHER_VERSION_BLOCK_WRAP],
        ];
    }

    public function getIdentifier(): string
    {
        return 'dpfPromoteOtherVersionIdentifiers';
    }

    public function getTitle(): string
    {
        return 'Fix "DOI (Qucosa)" double-count and strip DOI/Link from the "Andere Ausgabe" block (#2041)';
    }

    public function getDescription(): string
    {
        return 'Narrows "DOI (Qucosa)" to the primary identifier only (was double-showing every "Andere '
            . 'Ausgabe" DOI), and strips DOI/Link out of the 5-slot "Andere Ausgabe" block (both language '
            . 'surfaces) so it only carries title and note. Promoting DOI/Link into the identifier group '
            . 'itself is a deferred follow-up, not done here - see this class\'s docblock.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    private function getConnection()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_dpf_metadata');
    }

    public function updateNecessary(): bool
    {
        $connection = $this->getConnection();

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        $xpath = $connection->executeQuery(
            'SELECT xpath FROM tx_dpf_metadata WHERE deleted = 0 AND uid = 253'
        )->fetchColumn();

        return $xpath !== self::narrowedDoiQucosaXpath();
    }

    public function executeUpdate(): bool
    {
        $connection = $this->getConnection();

        foreach ($this->getRowUpdates() as $uid => $fields) {
            $connection->update('tx_dpf_metadata', $fields, ['uid' => $uid]);
        }

        return true;
    }
}
