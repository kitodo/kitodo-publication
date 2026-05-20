<?php
namespace EWW\Dpf\Tests\Unit\Services\Transfer;

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

use EWW\Dpf\Services\Transfer\DocumentTransferManager;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass — overrides fetchFindObjectsXml so no HTTP call is made.
 */
class TestableDocumentTransferManager extends DocumentTransferManager
{
    private $findObjectsResponse = false;

    public function setFindObjectsResponse($response): void
    {
        $this->findObjectsResponse = $response;
    }

    protected function fetchFindObjectsXml(string $url)
    {
        return $this->findObjectsResponse;
    }

    public function callResolveFedoraPid(string $urn): ?string
    {
        return $this->resolveFedoraPid($urn);
    }
}

class DocumentTransferManagerTest extends TestCase
{
    private TestableDocumentTransferManager $manager;

    protected function setUp(): void
    {
        $this->manager = new TestableDocumentTransferManager();

        // Minimal stub — getFedoraHost() just needs to return a non-empty string
        $clientConfig = new class {
            public function getFedoraHost(): string { return 'localhost:8080'; }
        };

        $ref = new \ReflectionProperty(DocumentTransferManager::class, 'clientConfigurationManager');
        $ref->setAccessible(true);
        $ref->setValue($this->manager, $clientConfig);
    }

    // ── Fixture builders ──────────────────────────────────────────────────

    private function findObjectsXml(array $objects): string
    {
        $fields = '';
        foreach ($objects as ['pid' => $pid, 'identifier' => $identifier]) {
            $fields .= sprintf(
                '<objectFields><pid>%s</pid><identifier>%s</identifier></objectFields>',
                htmlspecialchars($pid),
                htmlspecialchars($identifier)
            );
        }
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<result xmlns="http://www.fedora.info/definitions/1/0/types/">'
            . '<resultList>%s</resultList>'
            . '</result>',
            $fields
        );
    }

    // ── Tests ─────────────────────────────────────────────────────────────

    public function testSingleResultExactMatch(): void
    {
        $urn = 'urn:nbn:de:bsz:14-qucosa2-1000895';
        $this->manager->setFindObjectsResponse($this->findObjectsXml([
            ['pid' => 'qucosa:42', 'identifier' => $urn],
        ]));

        self::assertSame('qucosa:42', $this->manager->callResolveFedoraPid($urn));
    }

    /**
     * Core regression: terms= returns parent + children that reference the parent URN.
     * We must return the parent's PID, not null.
     */
    public function testMultipleResultsOnlyParentHasExactIdentifierMatch(): void
    {
        $parentUrn = 'urn:nbn:de:bsz:14-qucosa2-1000895';
        $this->manager->setFindObjectsResponse($this->findObjectsXml([
            ['pid' => 'qucosa:42', 'identifier' => $parentUrn],
            ['pid' => 'qucosa:100', 'identifier' => 'urn:nbn:de:bsz:14-qucosa2-1001001'],
            ['pid' => 'qucosa:101', 'identifier' => 'urn:nbn:de:bsz:14-qucosa2-1001002'],
        ]));

        self::assertSame('qucosa:42', $this->manager->callResolveFedoraPid($parentUrn));
    }

    public function testNoExactMatchReturnsNull(): void
    {
        $urn = 'urn:nbn:de:bsz:14-qucosa2-1000895';
        $this->manager->setFindObjectsResponse($this->findObjectsXml([
            ['pid' => 'qucosa:100', 'identifier' => 'urn:nbn:de:bsz:14-qucosa2-1001001'],
            ['pid' => 'qucosa:101', 'identifier' => 'urn:nbn:de:bsz:14-qucosa2-1001002'],
        ]));

        self::assertNull($this->manager->callResolveFedoraPid($urn));
    }

    public function testHttpFailureReturnsNull(): void
    {
        $this->manager->setFindObjectsResponse(false);

        self::assertNull($this->manager->callResolveFedoraPid('urn:nbn:de:bsz:14-qucosa2-1000895'));
    }

    public function testEmptyResponseReturnsNull(): void
    {
        $this->manager->setFindObjectsResponse('');

        self::assertNull($this->manager->callResolveFedoraPid('urn:nbn:de:bsz:14-qucosa2-1000895'));
    }

    public function testMalformedXmlReturnsNull(): void
    {
        $this->manager->setFindObjectsResponse('not valid < xml >>');

        self::assertNull($this->manager->callResolveFedoraPid('urn:nbn:de:bsz:14-qucosa2-1000895'));
    }

    public function testEmptyResultListReturnsNull(): void
    {
        $this->manager->setFindObjectsResponse(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<result xmlns="http://www.fedora.info/definitions/1/0/types/">'
            . '<resultList></resultList>'
            . '</result>'
        );

        self::assertNull($this->manager->callResolveFedoraPid('urn:nbn:de:bsz:14-qucosa2-1000895'));
    }

    public function testClientConfigurationManagerNullReturnsNull(): void
    {
        $manager = new TestableDocumentTransferManager();
        // clientConfigurationManager not set — remains null (default PHP uninitialized)
        $ref = new \ReflectionProperty(DocumentTransferManager::class, 'clientConfigurationManager');
        $ref->setAccessible(true);
        $ref->setValue($manager, null);

        self::assertNull($manager->callResolveFedoraPid('urn:nbn:de:bsz:14-qucosa2-1000895'));
    }
}
