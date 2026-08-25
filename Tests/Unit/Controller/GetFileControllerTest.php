<?php
namespace EWW\Dpf\Tests\Unit\Controller;

use EWW\Dpf\Configuration\ClientConfigurationManager;
use EWW\Dpf\Controller\GetFileController;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Repository\DocumentRepository;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Services\ElasticSearch\PublicElasticSearch;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Covers GetFileController's two state-gate methods (private, reached via
 * reflection): canProceedWithState() (mets, dataCite - metadata only) and
 * canProceedWithStateForFileDelivery() (attachment, zip - the actual full
 * text). Both are choke points their respective actions route through.
 *
 * #1985: an ACTIVE document under active embargo (Embargo-Enddatum
 * non-empty) must not have its full text delivered. But #1969/#1985 also
 * require metadata and the landing page to stay available under embargo -
 * the container deliberately stays REMOTE_STATE_ACTIVE while embargoed. A
 * first version of this fix blocked embargo in the one shared method every
 * action used, which broke the landing page itself (its own internal METS
 * fetch routes through metsAction -> canProceedWithState()).
 */
class GetFileControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        // stubPublicIndexSearchResult() queues instances via addInstance();
        // purge them so an instance left unconsumed by one test (e.g. an
        // assertion failing before it's consumed) can't leak into the next.
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    private function callMethod(
        string $methodName,
        GetFileController $controller,
        string $state,
        string $qid,
        string $token = ''
    ): bool {
        $method = new ReflectionMethod(GetFileController::class, $methodName);
        $method->setAccessible(true);
        return $method->invoke($controller, $state, $qid, $token);
    }

    private function makeController(?Document $document): GetFileController
    {
        $documentRepository = $this->createMock(DocumentRepository::class);
        $documentRepository->method('findByIdentifier')->willReturn($document);

        $clientConfigurationManager = $this->createMock(ClientConfigurationManager::class);

        return new GetFileController($documentRepository, $clientConfigurationManager);
    }

    private function makeDocument(bool $activeEmbargo, string $objectIdentifier = ''): Document
    {
        $doc = $this->createMock(Document::class);
        $doc->method('isActiveEmbargo')->willReturn($activeEmbargo);
        $doc->method('getObjectIdentifier')->willReturn($objectIdentifier);
        return $doc;
    }

    private function callResolveObjectIdentifier(GetFileController $controller, string $qid): ?string
    {
        $method = new ReflectionMethod(GetFileController::class, 'resolveObjectIdentifier');
        $method->setAccessible(true);
        return $method->invoke($controller, $qid);
    }

    /**
     * Queues a PublicElasticSearch mock for the next GeneralUtility::makeInstance()
     * call so resolveViaPublicIndex() doesn't reach out to a real ES server.
     */
    private function stubPublicIndexSearchResult(?string $foundObjectIdentifier): void
    {
        $es = $this->createMock(PublicElasticSearch::class);
        $hits = $foundObjectIdentifier === null
            ? []
            : [['_source' => ['objectIdentifier' => $foundObjectIdentifier]]];
        $es->method('search')->willReturn(['hits' => ['hits' => $hits]]);
        GeneralUtility::addInstance(PublicElasticSearch::class, $es);
    }

    public function testFileDeliveryBlockedForActiveDocumentUnderEmbargo(): void
    {
        $controller = $this->makeController($this->makeDocument(true));
        $this->assertFalse($this->callMethod(
            'canProceedWithStateForFileDelivery',
            $controller, DocumentWorkflow::REMOTE_STATE_ACTIVE, 'ubl-25-667'
        ));
    }

    public function testFileDeliveryAllowedForActiveDocumentWithoutEmbargo(): void
    {
        $controller = $this->makeController($this->makeDocument(false));
        $this->assertTrue($this->callMethod(
            'canProceedWithStateForFileDelivery',
            $controller, DocumentWorkflow::REMOTE_STATE_ACTIVE, 'ubl-25-667'
        ));
    }

    public function testMetadataDeliveryNotBlockedByEmbargo(): void
    {
        // mets/dataCite actions call canProceedWithState() - metadata and
        // the landing page must stay visible under embargo (#1969/#1985).
        $controller = $this->makeController($this->makeDocument(true));
        $this->assertTrue($this->callMethod(
            'canProceedWithState',
            $controller, DocumentWorkflow::REMOTE_STATE_ACTIVE, 'ubl-25-667'
        ));
    }

    public function testInactiveDocumentIsBlockedForFileDeliveryRegardlessOfEmbargo(): void
    {
        $controller = $this->makeController($this->makeDocument(false));
        $this->assertFalse($this->callMethod(
            'canProceedWithStateForFileDelivery',
            $controller, DocumentWorkflow::REMOTE_STATE_INACTIVE, 'ubl-25-667'
        ));
    }

    public function testInactiveDocumentIsBlockedForMetadata(): void
    {
        $controller = $this->makeController($this->makeDocument(false));
        $this->assertFalse($this->callMethod(
            'canProceedWithState',
            $controller, DocumentWorkflow::REMOTE_STATE_INACTIVE, 'ubl-25-667'
        ));
    }

    public function testUnknownQidUnderActiveStateIsAllowedForFileDelivery(): void
    {
        // No local Document row (e.g. plain remote Fedora object never
        // touched by the embargo working-copy workflow) - can't be
        // embargoed, existing ACTIVE-state behaviour is preserved.
        $controller = $this->makeController(null);
        $this->assertTrue($this->callMethod(
            'canProceedWithStateForFileDelivery',
            $controller, DocumentWorkflow::REMOTE_STATE_ACTIVE, 'qucosa-11203'
        ));
    }

    /**
     * #1969/#1985: process-number and objectIdentifier URLs resolved to the
     * same DB row for the state/embargo checks (findByIdentifier() already
     * handles both), but Fedora path building used the raw request qid
     * directly - which 404s for any migrated document, where objectIdentifier
     * (the old Fedora 3 qucosa-ID) differs from the process number it was
     * later assigned. Found live: landing-page/UBL-25-667 (process number)
     * failed while landing-page/qucosa-99227 (objectIdentifier, same
     * document) worked.
     */
    public function testResolvesProcessNumberToObjectIdentifier(): void
    {
        $controller = $this->makeController($this->makeDocument(false, 'qucosa-99227'));
        $this->assertSame('qucosa-99227', $this->callResolveObjectIdentifier($controller, 'UBL-25-667'));
    }

    public function testObjectIdentifierResolvesToItself(): void
    {
        $controller = $this->makeController($this->makeDocument(false, 'qucosa-99227'));
        $this->assertSame('qucosa-99227', $this->callResolveObjectIdentifier($controller, 'qucosa-99227'));
    }

    public function testUnknownQidResolvesToItself(): void
    {
        // No local Document row at all - nothing to resolve against, the
        // qid must already be the Fedora objectIdentifier itself.
        $controller = $this->makeController(null);
        $this->assertSame('qucosa-11203', $this->callResolveObjectIdentifier($controller, 'qucosa-11203'));
    }

    /**
     * Process-number collision fix, 2026-08-24: a migrated document has no
     * local DB row at all, so findByIdentifier() (checked above by
     * testUnknownQidResolvesToItself) can't resolve its process number.
     * Found live: a colliding local test document with the same process
     * number was served instead of the real, DB-row-less migrated document
     * (landing-page/UBL-25-667 resolved to "test 217" instead of
     * qucosa-99227). resolveObjectIdentifier() must fall back to the public
     * ES index - which does have the mapping - before giving up and
     * returning the qid unresolved.
     */
    public function testResolvesProcessNumberViaPublicIndexWhenNoDbRow(): void
    {
        $controller = $this->makeController(null);
        $this->stubPublicIndexSearchResult('qucosa-99227');
        $this->assertSame('qucosa-99227', $this->callResolveObjectIdentifier($controller, 'UBL-25-667'));
    }

    public function testUnknownQidStillResolvesToItselfWhenPublicIndexHasNoMatch(): void
    {
        $controller = $this->makeController(null);
        $this->stubPublicIndexSearchResult(null);
        $this->assertSame('UBL-25-667', $this->callResolveObjectIdentifier($controller, 'UBL-25-667'));
    }

    /**
     * A local Document row with no objectIdentifier is the "not remote"
     * case previously carried by the separate isRemote() method - merged
     * into resolveObjectIdentifier() to remove the duplicate DB/ES lookup
     * (bug_003). Callers now branch on null instead of a boolean.
     */
    public function testLocalDocumentWithoutObjectIdentifierResolvesToNull(): void
    {
        $controller = $this->makeController($this->makeDocument(false, ''));
        $this->assertNull($this->callResolveObjectIdentifier($controller, 'ubl-25-667'));
    }
}
