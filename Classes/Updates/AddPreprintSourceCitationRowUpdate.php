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
 * Adds the missing Preprint Quellenangabe row (#2047, `ubl-26-5070`) - no
 * wrap existed for `type=preprint` at all, unlike article/contained_work/
 * in_proceeding/in_encyclopedia/contributionToPeriodical.
 *
 * Confirmed live: the Preprint's host relatedItem has the identical shape to
 * "Sammelband" (original_in_book, uid 308/310) plus `trl` (Übersetzer) and
 * corporate `aut` (AutorIn Institution) roles the Sammelband row doesn't
 * splice - added by AddSourceCitationPersonInstitutionRolesUpdate, a
 * co-requisite of this wizard.
 *
 * The append chain is built programmatically (buildAppendChain()) rather
 * than hand-typed, to avoid the depth-renumbering bug class documented in
 * AddThirdEditorSlotToConferenceQuellenangabeUpdate's history.
 *
 * Idempotent: does nothing if original_preprint already exists.
 */
class AddPreprintSourceCitationRowUpdate implements UpgradeWizardInterface
{
    private const INDEX_NAME = 'original_preprint';

    /**
     * @param array<int, array{field: string, prefix: string}> $fields
     */
    private function buildAppendChain(array $fields): string
    {
        $depth = '';
        $lines = [];
        foreach ($fields as $f) {
            $depth .= 'append.';
            $key = 'value.' . rtrim($depth, '.');
            $lines[] = $key . ' = TEXT';
            $lines[] = $key . ' {';
            $lines[] = "\tvalue = {field:{$f['field']}}";
            $lines[] = "\tvalue.insertData = 1";
            if (!empty($f['replacement'])) {
                $lines[] = "\tvalue.replacement.1.search = /^([0-9]{4}).*/";
                $lines[] = "\tvalue.replacement.1.replace = \$1";
                $lines[] = "\tvalue.replacement.1.useRegExp = 1";
            }
            $prefix = rtrim($f['prefix'], '|');
            $lines[] = "\tvalue.noTrimWrap = {$prefix}| <br />";
            $lines[] = "\tvalue.noTrimWrap.fieldRequired = {$f['field']}";
            $lines[] = '}';
        }
        return implode("\n", $lines);
    }

    public function buildWrap(): string
    {
        $fields = [
            ['field' => 'original_subtitle', 'prefix' => '|: |'],
            ['field' => 'original_place', 'prefix' => '|Erscheinungsort: |'],
            ['field' => 'original_date', 'prefix' => '|Erscheinungsjahr: |', 'replacement' => true],
            ['field' => 'publisher0', 'prefix' => '|Verlag: |'],
            ['field' => 'original_author', 'prefix' => '|AutorIn: |'],
            ['field' => 'original_author_1', 'prefix' => '|AutorIn: |'],
            ['field' => 'original_publisher', 'prefix' => '|HerausgeberIn: |'],
            ['field' => 'original_publisher_1', 'prefix' => '|HerausgeberIn: |'],
            ['field' => 'original_translator', 'prefix' => '|ÜbersetzerIn: |'],
            ['field' => 'original_translator_1', 'prefix' => '|ÜbersetzerIn: |'],
            ['field' => 'original_institution_author', 'prefix' => '|AutorIn (Institution): |'],
            ['field' => 'original_institution_author_1', 'prefix' => '|AutorIn (Institution): |'],
            ['field' => 'original_institution_publisher', 'prefix' => '|HerausgeberIn (Institution): |'],
            ['field' => 'original_institution_publisher_1', 'prefix' => '|HerausgeberIn (Institution): |'],
            ['field' => 'original_edition', 'prefix' => '|Ausgabe/Auflage: |'],
            ['field' => 'original_volume', 'prefix' => '|Bandnummer / Jahrgang: |'],
            ['field' => 'original_issue', 'prefix' => '|Heftnummer: |'],
        ];

        $head = "key.wrap = <dt>|</dt>\n"
            . "value.if.equals.field = type\n"
            . "value.if.value = preprint\n"
            . "value.noTrimWrap = || \n"
            . "value.dataWrap = {field:original_title}\n"
            . "value.dataWrap.typolink.parameter = {field:host_url}\n"
            . "value.dataWrap.typolink.parameter.fieldRequired = host_url\n\n";

        return $head . $this->buildAppendChain($fields) . "\nvalue.wrap3 = <dd>|</dd>";
    }

    /**
     * @return array<string, mixed> the German/default-language row
     */
    public function getMainRow(): array
    {
        return [
            'pid' => 1,
            'sorting' => 30800,
            'index_name' => self::INDEX_NAME,
            'label' => 'Preprint',
            'format' => 1,
            'format_type' => 'MODS',
            'xpath' => '',
            'wrap' => $this->buildWrap(),
        ];
    }

    /**
     * The "Source" row is an English-label overlay of the main row, not a
     * second German row - it MUST carry sys_language_uid=1 and l18n_parent
     * pointing at the main row's uid, or findRenderableFields() (which
     * selects sys_language_uid IN (-1,0) OR = current) returns both as
     * l18n_parent=0 siblings sharing the same index_name, and the second
     * (empty-wrap) one silently overwrites the first in $metaList - caught
     * live: the whole Preprint row rendered nothing until this was fixed
     * (same shape as AddBlogSourceCitationRowUpdate's uid 545/546 pair).
     *
     * @param int $mainRowUid the uid getMainRow() was inserted as
     * @return array<string, mixed>
     */
    public function getOverlayRow(int $mainRowUid): array
    {
        return [
            'pid' => 1,
            'sorting' => 30801,
            'index_name' => self::INDEX_NAME,
            'label' => 'Source',
            'format' => 1,
            'format_type' => 'MODS',
            'xpath' => '',
            'wrap' => '',
            'sys_language_uid' => 1,
            'l18n_parent' => $mainRowUid,
        ];
    }

    public function getIdentifier(): string
    {
        return 'dpfAddPreprintSourceCitationRow';
    }

    public function getTitle(): string
    {
        return 'Add Preprint source-citation row to tx_dpf_metadata';
    }

    public function getDescription(): string
    {
        return 'Adds the "Preprint" Quellenangabe row (type=preprint) per #2047 - copies the Sammelband '
            . 'wrap shape, extended with AutorIn/ÜbersetzerIn/Institution-role splices.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        $existing = $connection->count(
            'uid',
            'tx_dpf_metadata',
            ['deleted' => 0, 'index_name' => self::INDEX_NAME]
        );

        return $existing === 0;
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        $existing = $connection->count(
            'uid',
            'tx_dpf_metadata',
            ['deleted' => 0, 'index_name' => self::INDEX_NAME]
        );

        if ($existing === 0) {
            $connection->insert('tx_dpf_metadata', $this->getMainRow());
            $mainRowUid = (int) $connection->lastInsertId('tx_dpf_metadata');
            $connection->insert('tx_dpf_metadata', $this->getOverlayRow($mainRowUid));
        }

        return true;
    }
}
