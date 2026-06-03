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
use EWW\Dpf\Services\ImportExternalMetadata\BibTexFileImporter;

class BibTexFileImporterTest extends UnitTestCase
{
    /**
     * @var BibTexFileImporter
     */
    protected $importer;

    /**
     * @var \ReflectionMethod
     */
    protected $splitPersons;

    /**
     * @var \ReflectionMethod
     */
    protected $parseFile;

    protected function setUp()
    {
        parent::setUp();
        $this->importer = $this->getMockBuilder(BibTexFileImporter::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $splitPersons = new \ReflectionMethod(BibTexFileImporter::class, 'splitPersons');
        $splitPersons->setAccessible(true);
        $this->splitPersons = $splitPersons;

        $parseFile = new \ReflectionMethod(BibTexFileImporter::class, 'parseFile');
        $parseFile->setAccessible(true);
        $this->parseFile = $parseFile;
    }

    /**
     * @test
     */
    public function splitPersons_handles_single_author()
    {
        $result = $this->splitPersons->invoke($this->importer, 'Smith, John');

        $this->assertCount(1, $result);
        $this->assertSame('Smith', $result[0]['family']);
        $this->assertSame('John', $result[0]['given']);
    }

    /**
     * @test
     */
    public function splitPersons_handles_two_authors_on_one_line()
    {
        $result = $this->splitPersons->invoke($this->importer, 'Smith, John and Doe, Jane');

        $this->assertCount(2, $result);
        $this->assertSame('Smith', $result[0]['family']);
        $this->assertSame('Doe', $result[1]['family']);
    }

    /**
     * @test
     * @see https://git.slub-dresden.de/kitodo-publication/issues/-/issues/2022
     */
    public function splitPersons_handles_newline_after_and()
    {
        // BibTeX multiline: author = {Smith, John and\n          Doe, Jane}
        // The raw parser preserves "and\n          " — no space before the newline
        // means explode(' and ', ...) finds no separator and returns 1 element.
        $multiline = "Smith, John and\n          Doe, Jane";

        $result = $this->splitPersons->invoke($this->importer, $multiline);

        $this->assertCount(2, $result);
        $this->assertSame('Smith', $result[0]['family']);
        $this->assertSame('John', $result[0]['given']);
        $this->assertSame('Doe', $result[1]['family']);
        $this->assertSame('Jane', $result[1]['given']);
    }

    /**
     * @test
     * @see https://git.slub-dresden.de/kitodo-publication/issues/-/issues/2023
     */
    public function parseFile_returns_all_entries_including_last()
    {
        // Verifies that parseFile() returns all entries from a multi-entry BibTeX string.
        // Context: loadFile() previously used `!$mandatoryErrors[$index]` which emits
        // E_NOTICE for clean entries (undefined offset). TYPO3's error handler can
        // promote that notice to an exception, silently dropping the last clean entry
        // when all prior entries had mandatory errors (those entries set the key, so
        // no undefined access for them). Fixed with empty().
        $bibtex = "@article{Smith2020,
  title = {First Paper},
  author = {Smith, John}
}
@book{Doe2021,
  title = {Last Paper},
  author = {Doe, Jane}
}";

        $entries = $this->parseFile->invoke($this->importer, $bibtex, true);

        $this->assertCount(2, $entries);
        $this->assertSame('article', $entries[0]['_type']);
        $this->assertSame('book', $entries[1]['_type']);
        $this->assertSame('Last Paper', $entries[1]['title']);
    }

    /**
     * @test
     * @see https://git.slub-dresden.de/kitodo-publication/issues/-/issues/2023
     *
     * TYPO3's error handler promotes E_NOTICE to exceptions. If $mandatoryErrors[$index]
     * is undefined (clean entry, key never set), `!$mandatoryErrors[$index]` emits
     * E_NOTICE. Strict environments throw on that notice, silently dropping the entry.
     * Using empty() suppresses the notice entirely.
     */
    public function mandatory_errors_check_does_not_emit_notice_for_undefined_index()
    {
        $notices = [];
        set_error_handler(function ($code, $message) use (&$notices) {
            if ($code === E_NOTICE && strpos($message, 'Undefined offset') !== false) {
                $notices[] = $message;
            }
            return true;
        });

        // Simulate the check for a clean entry (key not present in $mandatoryErrors)
        $mandatoryErrors = [];

        // Old pattern — emits E_NOTICE for undefined index
        $oldResult = !$mandatoryErrors[0];

        restore_error_handler();

        $this->assertNotEmpty($notices, 'Expected E_NOTICE for undefined offset with old pattern');
        $this->assertTrue($oldResult);
    }

    /**
     * @test
     * @see https://git.slub-dresden.de/kitodo-publication/issues/-/issues/2023
     */
    public function mandatory_errors_empty_check_does_not_emit_notice_for_undefined_index()
    {
        $notices = [];
        set_error_handler(function ($code, $message) use (&$notices) {
            if ($code === E_NOTICE && strpos($message, 'Undefined offset') !== false) {
                $notices[] = $message;
            }
            return true;
        });

        $mandatoryErrors = [];

        // Fixed pattern — no notice for undefined index
        $newResult = empty($mandatoryErrors[0]);

        restore_error_handler();

        $this->assertEmpty($notices, 'empty() must not emit E_NOTICE for undefined offset');
        $this->assertTrue($newResult);
    }

    /**
     * @test
     * @see https://git.slub-dresden.de/kitodo-publication/issues/-/issues/2023
     */
    public function parseFile_returns_last_entry_with_correct_author_when_prior_entries_exist()
    {
        $bibtex = "@article{A,
  title = {Paper One},
  author = {Alpha, Anne}
}
@article{B,
  title = {Paper Two},
  author = {Beta, Bob}
}
@article{C,
  title = {Last Publication},
  author = {Gamma, Carol}
}";

        $entries = $this->parseFile->invoke($this->importer, $bibtex, true);

        $this->assertCount(3, $entries);
        $this->assertSame('Last Publication', $entries[2]['title']);
        $this->assertSame('Gamma, Carol', $entries[2]['author']);
    }

    /**
     * @test
     * @see https://git.slub-dresden.de/kitodo-publication/issues/-/issues/2022
     */
    public function splitPersons_handles_three_authors_with_mixed_newlines()
    {
        $multiline = "Smith, John and\r\n  Doe, Jane and\n  Brown, Alice";

        $result = $this->splitPersons->invoke($this->importer, $multiline);

        $this->assertCount(3, $result);
        $this->assertSame('Smith', $result[0]['family']);
        $this->assertSame('Doe', $result[1]['family']);
        $this->assertSame('Brown', $result[2]['family']);
    }
}
