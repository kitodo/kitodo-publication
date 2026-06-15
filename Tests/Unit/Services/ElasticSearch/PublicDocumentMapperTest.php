<?php
namespace EWW\Dpf\Tests\Unit\Services\ElasticSearch;

use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\DocumentType;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Services\Api\InternalFormat;
use EWW\Dpf\Services\ElasticSearch\PublicDocumentMapper;
use PHPUnit\Framework\TestCase;

class PublicDocumentMapperTest extends TestCase
{
    private function makeDocument(string $state, bool $suggestion = false): Document
    {
        [, $remoteState] = explode(':', $state);
        $doc = $this->createMock(Document::class);
        $doc->method('getRemoteState')->willReturn($remoteState);
        $doc->method('isSuggestion')->willReturn($suggestion);
        $doc->method('getState')->willReturn($state);
        $doc->method('getTitle')->willReturn('Test Document');
        $doc->method('getObjectIdentifier')->willReturn('qucosa:12345');
        $doc->method('getProcessNumber')->willReturn('UBL-25-00001');
        $doc->method('getDocumentIdentifier')->willReturn('qucosa:12345');
        $doc->method('hasFiles')->willReturn(false);
        $doc->method('getDocumentType')->willReturn($this->makeDocumentType('article'));
        return $doc;
    }

    private function makeDocumentType(string $name): DocumentType
    {
        $dt = $this->createMock(DocumentType::class);
        $dt->method('getName')->willReturn($name);
        return $dt;
    }

    private function makeInternalFormat(): InternalFormat
    {
        $fmt = $this->createMock(InternalFormat::class);
        $fmt->method('getSearchTitles')->willReturn([]);
        $fmt->method('getPersons')->willReturn([]);
        $fmt->method('getSearchIdentifiers')->willReturn([]);
        $fmt->method('getCollections')->willReturn([]);
        $fmt->method('getOpenAccessForSearch')->willReturn('');
        $fmt->method('getLicense')->willReturn('');
        $fmt->method('getPublishers')->willReturn([]);
        $fmt->method('getSearchYear')->willReturn([]);
        $fmt->method('getSearchLanguage')->willReturn([]);
        $fmt->method('getDateIssued')->willReturn('');
        return $fmt;
    }

    public function testMapRejectsNoneNoneState()
    {
        $mapper = new PublicDocumentMapper();
        $doc = $this->makeDocument(DocumentWorkflow::STATE_NONE_NONE);
        $this->assertNull($mapper->map($doc, $this->makeInternalFormat()));
    }

    public function testMapRejectsInactiveRemoteState()
    {
        $mapper = new PublicDocumentMapper();
        $doc = $this->makeDocument(DocumentWorkflow::STATE_NONE_INACTIVE);
        $this->assertNull($mapper->map($doc, $this->makeInternalFormat()));
    }

    public function testMapRejectsSuggestion()
    {
        $mapper = new PublicDocumentMapper();
        $doc = $this->makeDocument(DocumentWorkflow::STATE_NONE_ACTIVE, true);
        $this->assertNull($mapper->map($doc, $this->makeInternalFormat()));
    }

    public function testMapAcceptsNoneActive()
    {
        $mapper = new PublicDocumentMapper();
        $doc = $this->makeDocument(DocumentWorkflow::STATE_NONE_ACTIVE);
        $this->assertIsArray($mapper->map($doc, $this->makeInternalFormat()));
    }

    public function testMapAcceptsInProgressActiveForEmbargo()
    {
        $mapper = new PublicDocumentMapper();
        $doc = $this->makeDocument(DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE);
        $this->assertIsArray($mapper->map($doc, $this->makeInternalFormat()));
    }

    public function testMapAcceptsTemporaryWithActiveState()
    {
        // CLI-created documents (IndexByFile) are always temporary=true but have ACTIVE remote state
        $mapper = new PublicDocumentMapper();
        [, $remoteState] = explode(':', DocumentWorkflow::STATE_NONE_ACTIVE);
        $doc = $this->createMock(Document::class);
        $doc->method('getRemoteState')->willReturn($remoteState);
        $doc->method('isSuggestion')->willReturn(false);
        $doc->method('isTemporary')->willReturn(true);
        $doc->method('getState')->willReturn(DocumentWorkflow::STATE_NONE_ACTIVE);
        $doc->method('getTitle')->willReturn('Test');
        $doc->method('getObjectIdentifier')->willReturn('qucosa:99');
        $doc->method('getProcessNumber')->willReturn('UBL-25-00099');
        $doc->method('getDocumentIdentifier')->willReturn('qucosa:99');
        $doc->method('hasFiles')->willReturn(false);
        $doc->method('getEmbargoDate')->willReturn(null);
        $doc->method('getDocumentType')->willReturn($this->makeDocumentType('article'));
        $this->assertIsArray($mapper->map($doc, $this->makeInternalFormat()));
    }

    public function testMapExcludesPrivateFields()
    {
        $mapper = new PublicDocumentMapper();
        $doc = $this->makeDocument(DocumentWorkflow::STATE_NONE_ACTIVE);
        $result = $mapper->map($doc, $this->makeInternalFormat());
        $this->assertArrayNotHasKey('creator', $result);
        $this->assertArrayNotHasKey('creatorRole', $result);
        $this->assertArrayNotHasKey('notes', $result);
    }

    public function testMapIncludesRequiredFields()
    {
        $mapper = new PublicDocumentMapper();
        $doc = $this->makeDocument(DocumentWorkflow::STATE_NONE_ACTIVE);
        $result = $mapper->map($doc, $this->makeInternalFormat());
        foreach (['title', 'doctype', 'state', 'objectIdentifier', 'process_number', 'hasFiles'] as $field) {
            $this->assertArrayHasKey($field, $result, "Missing field: $field");
        }
    }

    public function testMapIncludesEmbargoDate()
    {
        $mapper = new PublicDocumentMapper();
        $doc = $this->makeDocument(DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE);
        $embargoDate = new \DateTime('2026-12-31');
        $doc->method('getEmbargoDate')->willReturn($embargoDate);
        $result = $mapper->map($doc, $this->makeInternalFormat());
        $this->assertSame('2026-12-31', $result['embargoDate']);
    }
}
