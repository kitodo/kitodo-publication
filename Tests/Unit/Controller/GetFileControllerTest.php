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
 * Covers GetFileController::canProceedWithState() (private, reached via
 * reflection) — the single choke point all file/METS/ZIP/DataCite delivery
 * actions route through. #1985: an ACTIVE document under active embargo
 * (Embargo-Enddatum non-empty) must not be delivered, even though the
 * container itself deliberately stays REMOTE_STATE_ACTIVE so metadata /
 * landing page remain public (#1969).
 */
class GetFileControllerTest extends TestCase
{
    private function callCanProceedWithState(
        GetFileController $controller,
        string $state,
        string $qid,
        string $token = ''
    ): bool {
        $method = new ReflectionMethod(GetFileController::class, 'canProceedWithState');
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

    public function testActiveDocumentUnderEmbargoIsBlocked(): void
    {
        $controller = $this->makeController($this->makeDocument(true));
        $this->assertFalse($this->callCanProceedWithState(
            $controller, DocumentWorkflow::REMOTE_STATE_ACTIVE, 'ubl-25-667'
        ));
    }

    public function testActiveDocumentWithoutEmbargoIsAllowed(): void
    {
        $controller = $this->makeController($this->makeDocument(false));
        $this->assertTrue($this->callCanProceedWithState(
            $controller, DocumentWorkflow::REMOTE_STATE_ACTIVE, 'ubl-25-667'
        ));
    }

    public function testInactiveDocumentIsBlockedRegardlessOfEmbargo(): void
    {
        $controller = $this->makeController($this->makeDocument(false));
        $this->assertFalse($this->callCanProceedWithState(
            $controller, DocumentWorkflow::REMOTE_STATE_INACTIVE, 'ubl-25-667'
        ));
    }

    public function testUnknownQidUnderActiveStateIsAllowed(): void
    {
        // No local Document row (e.g. plain remote Fedora object never
        // touched by the embargo working-copy workflow) - can't be
        // embargoed, existing ACTIVE-state behaviour is preserved.
        $controller = $this->makeController(null);
        $this->assertTrue($this->callCanProceedWithState(
            $controller, DocumentWorkflow::REMOTE_STATE_ACTIVE, 'qucosa-11203'
        ));
    }
}
