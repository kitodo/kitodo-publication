<?php
namespace EWW\Dpf\Tests\Unit\Services\ImportExternalMetadata;

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

use Nimut\TestingFramework\TestCase\UnitTestCase;
use EWW\Dpf\Services\ImportExternalMetadata\RisReader;

class RisReaderTest extends UnitTestCase
{
    /**
     * @var RisReader
     */
    protected $risReader;

    protected function setUp()
    {
        parent::setUp();
        $this->risReader = new RisReader();
    }

    /**
     * Minimal WoS record wrapper for contentOnly tests.
     */
    private function wosRecord(string $tiValue): string
    {
        return "PT J\nTI $tiValue\nER\nEF";
    }

    /**
     * @test
     */
    public function preserves_uppercase_umlaut_via_file()
    {
        $fixture = __DIR__ . '/Fixtures/wos-special-chars.txt';
        $entries = $this->risReader->parseFile($fixture);

        $this->assertSame('Ä Single-Centre Retrospective Analysis', $entries[0]['TI']);
    }

    /**
     * @test
     */
    public function preserves_lowercase_umlaut_no_exception()
    {
        $content = $this->wosRecord('A Süngle-Centre Retrospective Analysis');
        $entries = $this->risReader->parseFile($content, true);

        $this->assertContains('ü', $entries[0]['TI']);
        $this->assertSame('A Süngle-Centre Retrospective Analysis', $entries[0]['TI']);
    }

    /**
     * @test
     */
    public function preserves_non_latin1_characters()
    {
        $content = $this->wosRecord('Živković, Dušan: A Study');
        $entries = $this->risReader->parseFile($content, true);

        $this->assertSame('Živković, Dušan: A Study', $entries[0]['TI']);
    }

    /**
     * @test
     */
    public function preserves_em_dash()
    {
        $content = $this->wosRecord('Abc — efg');
        $entries = $this->risReader->parseFile($content, true);

        $this->assertSame('Abc — efg', $entries[0]['TI']);
    }

    /**
     * @test
     */
    public function risRecordToXML_produces_parseable_xml()
    {
        $content = $this->wosRecord('Ä Süngle-Centre: Živković');
        $entries = $this->risReader->parseFile($content, true);

        $xml = $this->risReader->risRecordToXML($entries[0]);

        $this->assertNotFalse(@simplexml_load_string($xml), 'risRecordToXML output is not valid XML');
    }

    /**
     * @test
     */
    public function risRecordToXML_preserves_special_chars_without_substitution()
    {
        $content = $this->wosRecord('Živković — Ä Süngle');
        $entries = $this->risReader->parseFile($content, true);

        $xml = $this->risReader->risRecordToXML($entries[0]);
        $parsed = simplexml_load_string($xml);

        $title = (string) $parsed->{'document-title'};
        $this->assertSame('Živković — Ä Süngle', $title);
    }
}
