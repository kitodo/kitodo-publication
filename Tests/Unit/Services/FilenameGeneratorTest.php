<?php
namespace EWW\Dpf\Tests\Unit\Services;

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

use EWW\Dpf\Service\FilenameGenerator;
use PHPUnit\Framework\TestCase;

class FilenameGeneratorTest extends TestCase
{
    /** @var FilenameGenerator */
    private $generator;

    protected function setUp(): void
    {
        $this->generator = new FilenameGenerator();
    }

    // ── Fixture builders ──────────────────────────────────────────────────

    private function modsXml(
        string $title = 'Lorem Ipsum Dreissig Zeichen Test',
        string $year = '2021-06-15',
        array $authors = [['Mustermann', 'Max']],
        array $editors = [],
        bool $primaryUsage = true
    ): string {
        $usageAttr = $primaryUsage ? ' usage="primary"' : '';
        $nameXml = '';
        foreach ($authors as [$family, $given]) {
            $nameXml .= "
  <mods:name type=\"personal\">
    <mods:namePart type=\"family\">{$family}</mods:namePart>
    <mods:namePart type=\"given\">{$given}</mods:namePart>
    <mods:role><mods:roleTerm authority=\"marcrelator\" type=\"code\">aut</mods:roleTerm></mods:role>
  </mods:name>";
        }
        foreach ($editors as [$family, $given]) {
            $nameXml .= "
  <mods:name type=\"personal\">
    <mods:namePart type=\"family\">{$family}</mods:namePart>
    <mods:namePart type=\"given\">{$given}</mods:namePart>
    <mods:role><mods:roleTerm authority=\"marcrelator\" type=\"code\">edt</mods:roleTerm></mods:role>
  </mods:name>";
        }
        return <<<XML
<mods:mods xmlns:mods="http://www.loc.gov/mods/v3">
  <mods:titleInfo lang="ger"{$usageAttr}>
    <mods:title>{$title}</mods:title>
  </mods:titleInfo>
  <mods:originInfo eventType="distribution">
    <mods:dateIssued encoding="iso8601" keyDate="yes">{$year}</mods:dateIssued>
  </mods:originInfo>
  {$nameXml}
</mods:mods>
XML;
    }

    private function slubInfoXml(array $collections = []): string
    {
        $colXml = '';
        if (!empty($collections)) {
            $items = implode('', array_map(
                function ($c) { return "<slub:collection>{$c}</slub:collection>"; },
                $collections
            ));
            $colXml = "<slub:collections>{$items}</slub:collections>";
        }
        return <<<XML
<slub:info xmlns:slub="http://slub-dresden.de/" xmlns:foaf="http://xmlns.com/foaf/0.1/">
  {$colXml}
  <slub:documentType>monograph</slub:documentType>
  <slub:processNumber>SLUB-21-0001</slub:processNumber>
</slub:info>
XML;
    }

    // ── Tests ─────────────────────────────────────────────────────────────

    public function testSlubDocWithOneAuthor(): void
    {
        $filename = $this->generator->generate(
            $this->modsXml('Ein Beispiel Titel', '2021-06-15', [['Mueller', 'Max']]),
            $this->slubInfoXml(),
            'qucosa:slub',
            'application/pdf',
            0,
            1
        );
        $this->assertSame('2021_Mueller_EinBeispielTitel_Qucosa-SLUB.pdf', $filename);
    }

    public function testMusiconnDocIsAllLowercase(): void
    {
        $filename = $this->generator->generate(
            $this->modsXml('Ein Beispiel Titel', '2021-06-15', [['Mueller', 'Max']]),
            $this->slubInfoXml(['fidmusik']),
            'qucosa:slub',
            'application/pdf',
            0,
            1
        );
        $this->assertSame('2021_mueller_einbeispieltitel_musiconn.pdf', $filename);
    }

    public function testFallsBackToEditorWhenNoAuthor(): void
    {
        $filename = $this->generator->generate(
            $this->modsXml('Ein Beispiel Titel', '2021', [], [['Schmidt', 'Anna']]),
            $this->slubInfoXml(),
            'qucosa:slub',
            'application/pdf',
            0,
            1
        );
        $this->assertStringContainsString('Schmidt', $filename);
    }

    public function testNoAuthorNoEditorGeneratesTitleOnlyFilename(): void
    {
        $filename = $this->generator->generate(
            $this->modsXml('Ein Beispiel Titel', '2021', [], []),
            $this->slubInfoXml(),
            'qucosa:slub',
            'application/pdf',
            0,
            1
        );
        $this->assertSame('2021_EinBeispielTitel_Qucosa-SLUB.pdf', $filename);
    }

    public function testMultiFileAppendsIndexFromSecondFile(): void
    {
        $first = $this->generator->generate(
            $this->modsXml(), $this->slubInfoXml(), 'qucosa:slub', 'application/pdf', 0, 2
        );
        $second = $this->generator->generate(
            $this->modsXml(), $this->slubInfoXml(), 'qucosa:slub', 'application/pdf', 1, 2
        );
        $this->assertStringNotContainsString('_2', $first);
        $this->assertStringContainsString('_2', $second);
    }

    public function testGermanUmlautTransliteration(): void
    {
        $filename = $this->generator->generate(
            $this->modsXml('Uebersicht', '2020', [['Müller', 'Ärger']]),
            $this->slubInfoXml(),
            'qucosa:slub',
            'application/pdf',
            0,
            1
        );
        $this->assertStringContainsString('Mueller', $filename);
        $this->assertStringNotContainsString('ü', $filename);
        $this->assertStringNotContainsString('ä', $filename);
    }

    public function testFidMoveMandat(): void
    {
        $filename = $this->generator->generate(
            $this->modsXml(), $this->slubInfoXml(), 'qucosa:fid-move', 'application/pdf', 0, 1
        );
        $this->assertStringContainsString('FIDmove', $filename);
    }

    public function testMonarchMandant(): void
    {
        $filename = $this->generator->generate(
            $this->modsXml(), $this->slubInfoXml(), 'qucosa:ubc', 'application/pdf', 0, 1
        );
        $this->assertStringContainsString('Monarch', $filename);
    }

    public function testUnknownNamespaceFallback(): void
    {
        $filename = $this->generator->generate(
            $this->modsXml(), $this->slubInfoXml(), 'qucosa:xyz', 'application/pdf', 0, 1
        );
        $this->assertStringContainsString('XYZ', $filename);
    }

    public function testTitleTruncatedAt30Chars(): void
    {
        $longTitle = 'Ein sehr langer Titel der definitiv mehr als dreissig Zeichen hat';
        $filename = $this->generator->generate(
            $this->modsXml($longTitle), $this->slubInfoXml(), 'qucosa:slub', 'application/pdf', 0, 1
        );
        // Extract title segment (between name and suffix)
        $parts = explode('_', basename($filename, '.pdf'));
        // Join parts between index 2 and last to get title section
        $titlePart = implode('_', array_slice($parts, 2, -1));
        $this->assertLessThanOrEqual(30, strlen($titlePart));
    }

    public function testEpubExtension(): void
    {
        $filename = $this->generator->generate(
            $this->modsXml(), $this->slubInfoXml(), 'qucosa:slub', 'application/epub+zip', 0, 1
        );
        $this->assertStringEndsWith('.epub', $filename);
    }

    public function testNoAuthorFallsBackToTitleOnly(): void
    {
        $filename = $this->generator->generate(
            $this->modsXml('Rundbrief Goerlitz', '2025-01-01', [], []),
            $this->slubInfoXml(),
            'qucosa:slub',
            'application/pdf',
            0,
            1
        );
        $this->assertSame('2025_RundbriefGoerlitz_Qucosa-SLUB.pdf', $filename);
    }

    public function testReturnsEmptyStringWhenAllFieldsEmpty(): void
    {
        // MODS with no title, no author, no date
        $emptyMods = '<mods:mods xmlns:mods="http://www.loc.gov/mods/v3"></mods:mods>';
        $filename = $this->generator->generate(
            $emptyMods,
            $this->slubInfoXml(),
            '', // no sword namespace either
            'application/pdf',
            0,
            1
        );
        $this->assertSame('', $filename);
    }

    public function testReturnsEmptyStringOnInvalidMods(): void
    {
        $filename = $this->generator->generate(
            'not valid xml <<<',
            $this->slubInfoXml(),
            'qucosa:slub',
            'application/pdf',
            0,
            1
        );
        $this->assertSame('', $filename);
    }

    public function testYearExtractedFromFullIsoDate(): void
    {
        $filename = $this->generator->generate(
            $this->modsXml('Titel', '2019-10-24'),
            $this->slubInfoXml(),
            'qucosa:slub',
            'application/pdf',
            0,
            1
        );
        $this->assertStringStartsWith('2019_', $filename);
    }

    public function testMimeToExtensionOfficeFormats(): void
    {
        $this->assertSame('.xlsx', FilenameGenerator::mimeToExtension('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
        $this->assertSame('.docx', FilenameGenerator::mimeToExtension('application/vnd.openxmlformats-officedocument.wordprocessingml.document'));
        $this->assertSame('.pptx', FilenameGenerator::mimeToExtension('application/vnd.openxmlformats-officedocument.presentationml.presentation'));
        $this->assertSame('.xls',  FilenameGenerator::mimeToExtension('application/vnd.ms-excel'));
        $this->assertSame('.doc',  FilenameGenerator::mimeToExtension('application/msword'));
        $this->assertSame('.ppt',  FilenameGenerator::mimeToExtension('application/vnd.ms-powerpoint'));
        $this->assertSame('.odt',  FilenameGenerator::mimeToExtension('application/vnd.oasis.opendocument.text'));
        $this->assertSame('.ods',  FilenameGenerator::mimeToExtension('application/vnd.oasis.opendocument.spreadsheet'));
        $this->assertSame('.odp',  FilenameGenerator::mimeToExtension('application/vnd.oasis.opendocument.presentation'));
    }

    public function testMimeToExtensionUnknownMimeReturnsEmpty(): void
    {
        $this->assertSame('', FilenameGenerator::mimeToExtension('application/x-custom-format'));
        $this->assertSame('', FilenameGenerator::mimeToExtension(''));
    }

    public function testXlsxLabelStripsExtensionAndReappliesFromMime(): void
    {
        // Reproduces: Fedora label "File.xlsx" + MIME xlsx → should yield "File.xlsx", not "File"
        $filename = $this->generator->generate(
            $this->modsXml('Titel', '2024-01-01'),
            $this->slubInfoXml(),
            'qucosa:slub',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            1,
            2
        );
        $this->assertStringEndsWith('.xlsx', $filename);
    }
}
