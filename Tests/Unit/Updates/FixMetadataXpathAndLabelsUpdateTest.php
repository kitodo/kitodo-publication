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

use EWW\Dpf\Updates\FixMetadataXpathAndLabelsUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class FixMetadataXpathAndLabelsUpdateTest extends UnitTestCase
{
    public function testFixesOtherVersionCaseMismatch()
    {
        $wizard = new FixMetadataXpathAndLabelsUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'edition',
            'label' => 'Auflage',
            'xpath' => './mods:relatedItem[@type="otherVersion"]/mods:originInfo[@eventType="publication"]/mods:edition',
            'wrap' => '',
        ]);

        $this->assertSame(
            './mods:relatedItem[@type="otherversion"]/mods:originInfo[@eventType="publication"]/mods:edition',
            $fix['xpath']
        );
    }

    public function testCorporationOtherFixRewritesDisplayFormToNamePart()
    {
        $wizard = new FixMetadataXpathAndLabelsUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'corporation_other',
            'label' => 'Sonstige beteiligte Institution',
            'xpath' => 'mods:name[@type="corporate"][mods:role/mods:roleTerm="oth"]/mods:displayForm',
            'wrap' => '',
        ]);

        $this->assertSame(
            'mods:name[@type="corporate"][mods:role/mods:roleTerm="oth"]/mods:namePart',
            $fix['xpath']
        );
    }

    public function testDoesNotTouchUnaffectedDisplayFormRules()
    {
        $wizard = new FixMetadataXpathAndLabelsUpdate();

        // original_publisher targets role="edt" names, which do carry displayForm.
        $fix = $wizard->computeFix([
            'index_name' => 'original_publisher',
            'label' => 'Quellenangabe: Herausgeber',
            'xpath' => './mods:relatedItem[@type="host"]/mods:name[mods:role/mods:roleTerm="edt"][1]/mods:displayForm',
            'wrap' => '',
        ]);

        $this->assertArrayNotHasKey('xpath', $fix);
    }

    public function testRenamesLanguageLabel()
    {
        $wizard = new FixMetadataXpathAndLabelsUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'language',
            'label' => 'Sprache des Dokumentes',
            'xpath' => './mods:language/mods:languageTerm',
            'wrap' => '',
        ]);

        $this->assertSame('Sprache', $fix['label']);
    }

    public function testRenamesDokumenttypLabelOnBothTypeAndGenreRows()
    {
        $wizard = new FixMetadataXpathAndLabelsUpdate();

        $typeFix = $wizard->computeFix([
            'index_name' => 'type',
            'label' => 'Dokumenttyp',
            'xpath' => '',
            'wrap' => '',
        ]);
        $genreFix = $wizard->computeFix([
            'index_name' => 'genre',
            'label' => 'Dokumenttyp',
            'xpath' => './mods:genre',
            'wrap' => '',
        ]);

        $this->assertSame('Publikationstyp', $typeFix['label']);
        $this->assertSame('Publikationstyp', $genreFix['label']);
    }

    public function testRenamesQuellenangabeHeaderAndPageRangeDash()
    {
        $wizard = new FixMetadataXpathAndLabelsUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'original_in_book',
            'label' => 'Quellenangabe',
            'xpath' => '',
            'wrap' => "value.append.append.append.append.append.append.append.append.append.append.append.append.append {\n"
                . "\tvalue = {field:original_pages2}\n"
                . "\tvalue.insertData = 1\n"
                . "\tvalue.noTrimWrap = |-| <br />\n"
                . "\tvalue.noTrimWrap.fieldRequired = original_pages2\n"
                . "}",
        ]);

        $this->assertSame('Sammelband', $fix['label']);
        $this->assertStringContainsString('|–| <br />', $fix['wrap']);
        $this->assertStringNotContainsString('|-| <br />', $fix['wrap']);
    }

    public function testAlreadyFixedRowsProduceNoChanges()
    {
        $wizard = new FixMetadataXpathAndLabelsUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'edition',
            'label' => 'Auflage',
            'xpath' => './mods:relatedItem[@type="otherversion"]/mods:originInfo[@eventType="publication"]/mods:edition',
            'wrap' => '',
        ]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowsProduceNoChanges()
    {
        $wizard = new FixMetadataXpathAndLabelsUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'title',
            'label' => 'Titel',
            'xpath' => './mods:titleInfo/mods:title',
            'wrap' => '',
        ]);

        $this->assertSame([], $fix);
    }
}
