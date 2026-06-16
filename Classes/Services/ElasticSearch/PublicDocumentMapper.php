<?php
namespace EWW\Dpf\Services\ElasticSearch;

use DateTime;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Services\Api\InternalFormat;

class PublicDocumentMapper
{
    /**
     * Maps a document to a whitelist array for the public index.
     * Returns null when the document must not appear in the public index:
     * - remote state is not ACTIVE (drafts, inactive, deleted)
     * - document is a suggestion
     * The temporary flag is intentionally NOT checked: CLI bulk-load creates
     * transient Document objects with temporary=true that must still be indexed.
     *
     * @param Document $document
     * @param InternalFormat $internalFormat
     * @return array|null
     */
    public function map(Document $document, InternalFormat $internalFormat): ?array
    {
        if ($document->getRemoteState() !== DocumentWorkflow::REMOTE_STATE_ACTIVE) {
            return null;
        }
        if ($document->isSuggestion()) {
            return null;
        }

        $data = [];

        $data['title'] = [$document->getTitle()];
        foreach ($internalFormat->getSearchTitles() as $searchTitle) {
            $data['title'][] = $searchTitle;
        }
        $data['titleSort'] = $document->getTitle();

        $data['doctype'] = $document->getDocumentType() ? $document->getDocumentType()->getName() : '';
        $data['state'] = $document->getState();
        $data['objectIdentifier'] = $document->getObjectIdentifier();
        $data['process_number'] = $document->getProcessNumber();

        $data['identifier'] = array_filter([
            $document->getObjectIdentifier(),
            $document->getProcessNumber(),
        ]);
        foreach ($internalFormat->getSearchIdentifiers() as $id) {
            $data['identifier'][] = $id;
        }

        $persons = $internalFormat->getPersons();
        $data['persons'] = [];
        foreach ($persons as $person) {
            $data['persons'][] = $person['name'] ?? '';
        }

        if (!empty($persons) && array_key_exists('family', $persons[0])) {
            $data['personsSort'] = $persons[0]['family'];
        } else {
            $data['personsSort'] = '';
        }

        $dateIssued = $internalFormat->getDateIssued();
        $data['dateIssued'] = !empty($dateIssued) ? date('Y-m-d', strtotime($dateIssued)) : null;

        $years = $internalFormat->getSearchYear();
        $data['year'] = '';
        foreach ($years as $year) {
            if (!empty($year)) {
                $data['year'] = $year;
                break;
            }
        }

        $data['collections'] = $internalFormat->getCollections() ?: [];
        $data['openAccess'] = $internalFormat->getOpenAccessForSearch();
        $data['license'] = $internalFormat->getLicense();
        $data['hasFiles'] = $document->hasFiles();
        $data['language'] = $internalFormat->getSearchLanguage();
        $data['publisher'] = $internalFormat->getPublishers();

        $embargoDate = $document->getEmbargoDate();
        $data['embargoDate'] = ($embargoDate instanceof DateTime) ? $embargoDate->format('Y-m-d') : null;

        return $data;
    }
}
