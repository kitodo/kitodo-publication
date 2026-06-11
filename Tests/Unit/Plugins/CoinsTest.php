<?php

namespace EWW\Dpf\Tests\Unit\Plugins;

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

use EWW\Dpf\Plugins\Coins\Coins;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class CoinsTest extends UnitTestCase
{
    private function makeCoins(): Coins
    {
        return $this->getMockBuilder(Coins::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
    }

    private function callGenerateCoins(Coins $coins, array $metadata): string
    {
        $ref = new \ReflectionMethod(Coins::class, 'generateCoins');
        $ref->setAccessible(true);
        return $ref->invoke($coins, $metadata);
    }

    public function testGenerateCoinsFullMetadata()
    {
        $metadata = [
            'title'      => ['Test Article'],
            'urn'        => ['urn:nbn:de:bsz:14-qucosa2-123456'],
            'doi'        => ['10.25366/2020.42'],
            'language'   => ['ger'],
            'dateissued' => ['2020-05-12'],
            'author1'    => ['Musterfrau, Erika'],
            'record_id'  => ['qucosa:12345'],
        ];
        $output = $this->callGenerateCoins($this->makeCoins(), $metadata);

        $this->assertStringContainsString('class="Z3988"', $output);
        $this->assertStringContainsString('rft.atitle=', $output);
        $this->assertStringContainsString('rft.language=ger', $output);
        $this->assertStringContainsString('rft.au=', $output);
        $this->assertStringContainsString('rft_id=', $output);
        $this->assertStringContainsString('rft.date=2020', $output);
    }

    public function testGenerateCoinsWithMissingLanguage()
    {
        $metadata = ['title' => ['Only Title']];
        $output = $this->callGenerateCoins($this->makeCoins(), $metadata);

        $this->assertStringContainsString('class="Z3988"', $output);
        $this->assertStringNotContainsString('rft.language', $output);
    }

    public function testGenerateCoinsWithMissingDate()
    {
        $metadata = ['title' => ['No Date Doc']];
        $output = $this->callGenerateCoins($this->makeCoins(), $metadata);

        $this->assertStringContainsString('class="Z3988"', $output);
        $this->assertStringNotContainsString('rft.date', $output);
    }

    public function testGenerateCoinsYearOnlyDate()
    {
        $metadata = ['dateissued' => ['1989']];
        $output = $this->callGenerateCoins($this->makeCoins(), $metadata);

        $this->assertStringContainsString('rft.date=1989', $output);
    }

    public function testGenerateCoinsMultipleAuthors()
    {
        $metadata = [
            'author1' => ['Author One', 'Author Two'],
        ];
        $output = $this->callGenerateCoins($this->makeCoins(), $metadata);

        $this->assertEquals(2, substr_count($output, 'rft.au='));
    }

    public function testGenerateCoinsEmptyMetadataStillEmitsSpan()
    {
        $output = $this->callGenerateCoins($this->makeCoins(), []);

        $this->assertStringContainsString('class="Z3988"', $output);
        $this->assertStringContainsString('url_ver=Z39.88-2004', $output);
    }
}
