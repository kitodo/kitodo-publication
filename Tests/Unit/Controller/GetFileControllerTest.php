<?php
namespace EWW\Dpf\Tests\Unit\Controller;

use EWW\Dpf\Configuration\ClientConfigurationManager;
use EWW\Dpf\Controller\GetFileController;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Repository\DocumentRepository;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

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

    private function makeDocument(bool $activeEmbargo): Document
    {
        $doc = $this->createMock(Document::class);
        $doc->method('isActiveEmbargo')->willReturn($activeEmbargo);
        return $doc;
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
}
