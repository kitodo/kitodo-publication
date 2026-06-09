<?php

namespace EWW\Dpf\Tests\Unit\Services\Metadata;

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

use EWW\Dpf\Services\Metadata\ModsCoreExtractor;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class ModsCoreExtractorTest extends UnitTestCase
{
    protected function extract(string $modsXml): array
    {
        $xml = simplexml_load_string(
            '<mods:mods xmlns:mods="http://www.loc.gov/mods/v3">' . $modsXml . '</mods:mods>'
        );
        $metadata = [];
        (new ModsCoreExtractor())->extractMetadata($xml, $metadata);
        return $metadata;
    }

    public function testAuthorFromNamePartsOrdersFamilyThenGiven()
    {
        $metadata = $this->extract(
            '<mods:name><mods:namePart type="given">Erika</mods:namePart>'
            . '<mods:namePart type="family">Musterfrau</mods:namePart>'
            . '<mods:role><mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm></mods:role>'
            . '</mods:name>'
        );
        $this->assertEquals('Musterfrau, Erika', $metadata['author'][0]);
    }

    public function testAuthorPrefersDisplayForm()
    {
        $metadata = $this->extract(
            '<mods:name><mods:displayForm>Mustermann, Max</mods:displayForm>'
            . '<mods:namePart type="family">Mustermann</mods:namePart>'
            . '<mods:role><mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm></mods:role>'
            . '</mods:name>'
        );
        $this->assertEquals('Mustermann, Max', $metadata['author'][0]);
    }

    public function testAuthorValueUriAppendedWithUnitSeparator()
    {
        $metadata = $this->extract(
            '<mods:name valueURI="http://d-nb.info/gnd/1"><mods:displayForm>Mustermann, Max</mods:displayForm>'
            . '<mods:role><mods:roleTerm type="code" authority="marcrelator">aut</mods:roleTerm></mods:role>'
            . '</mods:name>'
        );
        $this->assertEquals('Mustermann, Max' . chr(31) . 'http://d-nb.info/gnd/1', $metadata['author'][0]);
    }

    public function testNamesWithoutRoleAreFallbackAuthors()
    {
        $metadata = $this->extract(
            '<mods:name><mods:displayForm>Fallback, Autor</mods:displayForm></mods:name>'
        );
        $this->assertEquals('Fallback, Autor', $metadata['author'][0]);
    }

    public function testPlaceAndPlaceSortingStripsPunctuation()
    {
        $metadata = $this->extract(
            '<mods:originInfo><mods:place><mods:placeTerm type="text">Dresden,</mods:placeTerm></mods:place></mods:originInfo>'
        );
        $this->assertEquals('Dresden,', $metadata['place'][0]);
        $this->assertEquals('Dresden', $metadata['place_sorting'][0]);
    }

    public function testYearFromKeyDate()
    {
        $metadata = $this->extract(
            '<mods:originInfo><mods:dateIssued keyDate="yes">2020-05-12</mods:dateIssued>'
            . '<mods:dateIssued>1999</mods:dateIssued></mods:originInfo>'
        );
        $this->assertEquals('2020-05-12', $metadata['year'][0]);
        $this->assertEquals(20200512, $metadata['year_sorting'][0]);
    }

    public function testYearSortingCenturyHeuristic()
    {
        $metadata = $this->extract(
            '<mods:originInfo><mods:dateIssued keyDate="yes">19. Jh.</mods:dateIssued></mods:originInfo>'
        );
        $this->assertEquals('19. Jh.', $metadata['year'][0]);
        $this->assertEquals(1850, $metadata['year_sorting'][0]);
    }
}
