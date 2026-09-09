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

use EWW\Dpf\Updates\PromoteOtherVersionIdentifiersUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class PromoteOtherVersionIdentifiersUpdateTest extends UnitTestCase
{
    public function testGetIdentifier()
    {
        $this->assertSame(
            'dpfPromoteOtherVersionIdentifiers',
            (new PromoteOtherVersionIdentifiersUpdate())->getIdentifier()
        );
    }

    public function testNarrowsDoiQucosaToPrimaryIdentifierOnly()
    {
        $updates = (new PromoteOtherVersionIdentifiersUpdate())->getRowUpdates();

        $this->assertSame('./mods:identifier[@type="doi"]', $updates[253]['xpath']);
        $this->assertStringNotContainsString('otherversion', $updates[253]['xpath']);
    }

    /**
     * The 5-slot block must keep exactly its title (otherVersion_N) and note
     * (note_N) rendering - dropping doi_N/url_N must not touch anything
     * else, since real "Andere Ausgabe" note text is still shown for real
     * documents that carry it.
     */
    public function testOtherVersionBlockWrapDropsDoiAndUrlButKeepsTitleAndNote()
    {
        $wrap = PromoteOtherVersionIdentifiersUpdate::otherVersionBlockWrap();

        $this->assertStringNotContainsString('doi_1', $wrap);
        $this->assertStringNotContainsString('doi_2', $wrap);
        $this->assertStringNotContainsString('doi_3', $wrap);
        $this->assertStringNotContainsString('doi_4', $wrap);
        $this->assertStringNotContainsString('doi_5', $wrap);
        $this->assertStringNotContainsString('url_1', $wrap);
        $this->assertStringNotContainsString('url_2', $wrap);
        $this->assertStringNotContainsString('url_3', $wrap);
        $this->assertStringNotContainsString('url_4', $wrap);
        $this->assertStringNotContainsString('url_5', $wrap);
        $this->assertStringNotContainsString('Link:&nbsp;', $wrap);
        $this->assertStringNotContainsString('DOI:&nbsp;', $wrap);

        foreach (['note1', 'note2', 'note3', 'note4', 'note5'] as $field) {
            $this->assertStringContainsString($field, $wrap);
        }
        foreach (['otherVersion1', 'otherVersion2', 'otherVersion3', 'otherVersion4', 'otherVersion5'] as $field) {
            $this->assertStringContainsString($field, $wrap);
        }
    }

    /**
     * uid 224 (the English-surface twin of uid 225, l18n_parent=225) must
     * get the identical fix - the ticket's original bug reproduced on both
     * language surfaces, a duplicate structure that has bitten this batch
     * before (feedback_dpf_dual_labeltype_config_paths).
     */
    public function testAppliesIdenticalWrapToBothLanguageSurfaces()
    {
        $updates = (new PromoteOtherVersionIdentifiersUpdate())->getRowUpdates();

        $this->assertSame($updates[225]['wrap'], $updates[224]['wrap']);
    }

    /**
     * This wizard must NOT touch uid 268/269 - a first attempt tried
     * repurposing them as new standalone rows, but they turned out to be
     * the English l18n overlays of uid 253/254, not dead placeholders (see
     * this class's docblock). Reverted; asserting their absence here
     * guards against that mistake being reintroduced.
     */
    public function testDoesNotTouchTheMisidentifiedOverlayRows()
    {
        $updates = (new PromoteOtherVersionIdentifiersUpdate())->getRowUpdates();

        $this->assertArrayNotHasKey(268, $updates);
        $this->assertArrayNotHasKey(269, $updates);
    }
}
