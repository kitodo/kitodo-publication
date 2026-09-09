<?php

namespace EWW\Dpf\Tests\Unit\Services\Api;

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

use EWW\Dpf\Configuration\ClientConfigurationManager;
use EWW\Dpf\Services\Api\InternalFormat;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * #2049: getPeerReviewForSearch()'s vote-merge logic is pure (no property
 * access), so it's tested via reflection on an instance built without the
 * constructor - InternalFormat's real constructor needs a live TYPO3
 * ObjectManager/LogManager, which unit tests here don't set up.
 */
class InternalFormatPeerReviewTest extends UnitTestCase
{
    private function newInstance(): InternalFormat
    {
        return (new \ReflectionClass(InternalFormat::class))->newInstanceWithoutConstructor();
    }

    private function normalize(InternalFormat $instance, string $value, array $peerReviewValues): string
    {
        $method = new \ReflectionMethod(InternalFormat::class, 'normalizePeerReviewVote');
        $method->setAccessible(true);
        return $method->invoke($instance, $value, $peerReviewValues);
    }

    private function merge(InternalFormat $instance, array $votes): string
    {
        $method = new \ReflectionMethod(InternalFormat::class, 'mergePeerReviewVotes');
        $method->setAccessible(true);
        return $method->invoke($instance, $votes);
    }

    private function values(): array
    {
        return ['true' => 'ja', 'false' => 'nein', 'unknown' => 'unbekannt'];
    }

    public function testNormalizeMatchesConfiguredTrueCaseInsensitively()
    {
        $this->assertSame('true', $this->normalize($this->newInstance(), 'JA', $this->values()));
    }

    public function testNormalizeMatchesConfiguredFalse()
    {
        $this->assertSame('false', $this->normalize($this->newInstance(), 'nein', $this->values()));
    }

    public function testNormalizeFallsBackToUnknownForAnythingElse()
    {
        $this->assertSame('unknown', $this->normalize($this->newInstance(), 'unbekannt', $this->values()));
        $this->assertSame('unknown', $this->normalize($this->newInstance(), 'garbage', $this->values()));
    }

    /**
     * @dataProvider mergeTruthTableProvider
     */
    public function testMergeReproducesTicketTruthTable(array $votes, string $expected)
    {
        $this->assertSame($expected, $this->merge($this->newInstance(), $votes));
    }

    public function mergeTruthTableProvider(): array
    {
        return [
            'j+j=j' => [['true', 'true'], 'true'],
            'j+n=j' => [['true', 'false'], 'true'],
            'n+n=n' => [['false', 'false'], 'false'],
            'u+j=j' => [['unknown', 'true'], 'true'],
            'u+n=n' => [['unknown', 'false'], 'false'],
            'u+u=u' => [['unknown', 'unknown'], 'unknown'],
            // #2049's reported bug case: one side unknown, other side absent
            // (contributes no vote at all) must stay unknown, not "true".
            'u+0=u' => [['unknown'], 'unknown'],
            // both sides absent: no votes at all falls back to unknown.
            '0+0=u' => [[], 'unknown'],
        ];
    }

    /**
     * Regression: a client/context with no peerReviewValues configured at
     * all (getPeerReviewValues() returns null) crashed getPeerReviewForSearch()
     * with a TypeError during a real reindex - caught live, 931/2485
     * documents failed. Must degrade to "unknown" instead.
     */
    public function testGetPeerReviewForSearchReturnsUnknownWhenValuesNotConfigured()
    {
        // No XML/DOMXPath setup needed: the null-config guard must return
        // before touching $this->xml at all, so a bare newInstanceWithoutConstructor()
        // is enough - if that guard is ever removed, this test would then
        // fail with a clear "no XML set" error rather than a silent pass.
        $instance = $this->newInstance();

        $clientConfigurationManager = $this->createMock(ClientConfigurationManager::class);
        $clientConfigurationManager->method('getPeerReviewValues')->willReturn(null);

        $property = new \ReflectionProperty(InternalFormat::class, 'clientConfigurationManager');
        $property->setAccessible(true);
        $property->setValue($instance, $clientConfigurationManager);

        $this->assertSame('unknown', $instance->getPeerReviewForSearch());
    }
}
