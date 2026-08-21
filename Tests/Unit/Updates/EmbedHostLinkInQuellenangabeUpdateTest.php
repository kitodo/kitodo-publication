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

use EWW\Dpf\Updates\EmbedHostLinkInQuellenangabeUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class EmbedHostLinkInQuellenangabeUpdateTest extends UnitTestCase
{
    public function testRewritesTypolinkParameterOnZeitschriftRow()
    {
        $wizard = new EmbedHostLinkInQuellenangabeUpdate();

        $fix = $wizard->computeFix([
            'uid' => 313,
            'index_name' => 'original0000000000',
            'wrap' => "value.dataWrap = {field:original_title}\r\n"
                . "value.dataWrap.typolink.parameter = /id/{field:multivolume_local}\r\n"
                . "value.dataWrap.typolink.parameter.fieldRequired = multivolume_local\r\n",
        ]);

        $this->assertStringContainsString('value.dataWrap.typolink.parameter = {field:host_url}', $fix['wrap']);
        $this->assertStringContainsString(
            'value.dataWrap.typolink.parameter.fieldRequired = host_url',
            $fix['wrap']
        );
        $this->assertStringNotContainsString('multivolume_local', $fix['wrap']);
    }

    public function testRewritesTypolinkParameterOnKonferenzbandRow()
    {
        $wizard = new EmbedHostLinkInQuellenangabeUpdate();

        $fix = $wizard->computeFix([
            'uid' => 307,
            'index_name' => 'original_in_proceeding0000000',
            'wrap' => 'value.dataWrap.typolink.parameter = /id/{field:multivolume_local}'
                . "\r\nvalue.dataWrap.typolink.parameter.fieldRequired = multivolume_local",
        ]);

        $this->assertArrayHasKey('wrap', $fix);
    }

    public function testAlreadyFixedRowNeedsNoFix()
    {
        $wizard = new EmbedHostLinkInQuellenangabeUpdate();

        $fix = $wizard->computeFix([
            'uid' => 313,
            'index_name' => 'original0000000000',
            'wrap' => 'value.dataWrap.typolink.parameter = {field:host_url}'
                . "\r\nvalue.dataWrap.typolink.parameter.fieldRequired = host_url",
        ]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new EmbedHostLinkInQuellenangabeUpdate();

        $fix = $wizard->computeFix([
            'uid' => 341,
            'index_name' => 'multivolume_local',
            'wrap' => 'value.dataWrap.typolink.parameter = /id/{field:multivolume_local}',
        ]);

        $this->assertSame([], $fix);
    }

    public function testAffectedRowWithoutOldParameterNeedsNoFix()
    {
        $wizard = new EmbedHostLinkInQuellenangabeUpdate();

        $fix = $wizard->computeFix([
            'uid' => 313,
            'index_name' => 'original0000000000',
            'wrap' => 'key.wrap = <dt>|</dt>',
        ]);

        $this->assertSame([], $fix);
    }
}
