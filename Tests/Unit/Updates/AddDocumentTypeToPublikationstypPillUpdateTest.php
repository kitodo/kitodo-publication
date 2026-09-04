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

use EWW\Dpf\Updates\AddDocumentTypeToPublikationstypPillUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddDocumentTypeToPublikationstypPillUpdateTest extends UnitTestCase
{
    public function testDefinesHiddenDocumentTypeRow()
    {
        $row = (new AddDocumentTypeToPublikationstypPillUpdate())->getNewRow();

        $this->assertSame('document_type', $row['index_name']);
        $this->assertSame('./mods:genre', $row['xpath']);
        $this->assertSame(1, $row['hidden']);
    }

    public function testPillWrapAppendsDocumentTypeGatedOnPresence()
    {
        $ref = new \ReflectionClass(AddDocumentTypeToPublikationstypPillUpdate::class);
        $newWrap = $ref->getConstant('NEW_WRAP');

        $this->assertStringContainsString('value.append.if.isTrue.field = document_type', $newWrap);
        $this->assertStringContainsString('{field:document_type}', $newWrap);
        // noTrimWrap, not plain wrap - the TypoScript parser trims trailing
        // whitespace off a plain .wrap value, silently eating the space
        // after the colon ("Zeitschriftenartikel:reviewArticle" with no
        // space - caught live before shipping).
        $this->assertStringContainsString('value.append.noTrimWrap = |: ||', $newWrap);
        $this->assertStringNotContainsString('value.append.wrap =', $newWrap);
        // Must use wrap3 (applied after append), not plain wrap - same reasoning
        // as the translated-title composite rows (append would otherwise leak
        // outside the <dd>).
        $this->assertStringContainsString('value.wrap3 = <dd class="doctype">|</dd>', $newWrap);
        $this->assertStringNotContainsString('value.wrap =', $newWrap);
    }
}
