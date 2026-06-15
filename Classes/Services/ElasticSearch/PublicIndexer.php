<?php
namespace EWW\Dpf\Services\ElasticSearch;

use EWW\Dpf\Domain\Model\Document;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PublicIndexer
{
    public function indexPublicDocument(Document $document): void
    {
        $es = GeneralUtility::makeInstance(PublicElasticSearch::class);
        $es->index($document);
    }

    public function deletePublicDocument(string $identifier): void
    {
        $es = GeneralUtility::makeInstance(PublicElasticSearch::class);
        $es->delete(strtolower($identifier));
    }
}
