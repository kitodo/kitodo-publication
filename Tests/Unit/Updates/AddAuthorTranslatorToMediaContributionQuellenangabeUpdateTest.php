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

use EWW\Dpf\Updates\AddAuthorTranslatorToMediaContributionQuellenangabeUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddAuthorTranslatorToMediaContributionQuellenangabeUpdateTest extends UnitTestCase
{
    private const REAL_WRAP_UID537 = 'key.wrap = <dt>|</dt>
value.if.equals.field = type
value.if.value = contributionToPeriodical
value.noTrimWrap = | |
value.dataWrap = {field:original_title}

value.append = TEXT
value.append {
	value = {field:original_subtitle}
	value.insertData = 1
	value.noTrimWrap = |: |<br />
	value.noTrimWrap.fieldRequired = original_subtitle
}
value.wrap3 = <dd>|</dd>';

    public function testComputeFixReturnsNullWhenAlreadyApplied()
    {
        $update = new AddAuthorTranslatorToMediaContributionQuellenangabeUpdate();

        $this->assertNull($update->computeFix('{field:original_author}' . self::REAL_WRAP_UID537));
    }

    public function testComputeFixReturnsNullWhenShapeUnexpected()
    {
        $update = new AddAuthorTranslatorToMediaContributionQuellenangabeUpdate();

        $this->assertNull($update->computeFix('some unrelated wrap without the marker'));
    }

    public function testComputeFixInsertsChainBeforeFinalWrap3()
    {
        $update = new AddAuthorTranslatorToMediaContributionQuellenangabeUpdate();
        $fixed = $update->computeFix(self::REAL_WRAP_UID537);

        $this->assertNotNull($fixed);
        $this->assertStringContainsString('{field:original_author}', $fixed);
        $this->assertStringContainsString('{field:original_translator}', $fixed);
        $this->assertStringContainsString('{field:original_institution_author}', $fixed);
        $this->assertStringContainsString('{field:original_institution_publisher}', $fixed);
        $this->assertLessThan(
            strpos($fixed, 'value.wrap3 = <dd>|</dd>'),
            strpos($fixed, '{field:original_author}')
        );
    }

    public function testAdditionalChainStartsAtDepth18()
    {
        $update = new AddAuthorTranslatorToMediaContributionQuellenangabeUpdate();
        $chain = $update->buildAdditionalChain();

        $this->assertStringStartsWith('value.' . rtrim(str_repeat('append.', 18), '.') . ' = TEXT', ltrim($chain));
    }
}
