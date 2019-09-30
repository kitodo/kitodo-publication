<?php
namespace EWW\Dpf\Services\Transfer;

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

use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Services\Transfer\ElasticsearchRepository;
use EWW\Dpf\Domain\Model\File;

class DocumentTransferManager
{

    /**
     * documenRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository;

    /**
     * documenTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @inject
     */
    protected $documentTypeRepository;

    /**
     * fileRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FileRepository
     * @inject
     */
    protected $fileRepository;

    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @inject
     */
    protected $objectManager;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @inject
     */
    protected $persistenceManager;

    /**
     * remoteRepository
     *
     * @var \EWW\Dpf\Services\Transfer\Repository
     */
    protected $remoteRepository;

    /**
     * Sets the remote repository into which the documents will be stored
     *
     * @param \EWW\Dpf\Services\Transfer\Repository $remoteRepository
     */
    public function setRemoteRepository($remoteRepository)
    {

        $this->remoteRepository = $remoteRepository;

    }

    /**
     * Stores a document into the remote repository
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return boolean
     */
    public function ingest($document)
    {

        $document->setTransferStatus(Document::TRANSFER_QUEUED);
        $this->documentRepository->update($document);

        $exporter = new \EWW\Dpf\Services\MetsExporter();

        $fileData = $document->getFileData();

        $exporter->setFileData($fileData);

        $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());

        // Set current date as publication date
        $dateIssued = (new \DateTime)->format(\DateTime::ISO8601);
        $mods->setDateIssued($dateIssued);

        $exporter->setMods($mods->getModsXml());

        $exporter->setSlubInfo($document->getSlubInfoData());

        $exporter->setObjId($document->getObjectIdentifier());

        $exporter->buildMets();

        $metsXml = $exporter->getMetsData();

        // remove document from local index
        $elasticsearchRepository = $this->objectManager->get(ElasticsearchRepository::class);
        $elasticsearchRepository->delete($document, "");

        $remoteDocumentId = $this->remoteRepository->ingest($document, $metsXml);

        if ($remoteDocumentId) {
            $document->setDateIssued($dateIssued);
            $document->setObjectIdentifier($remoteDocumentId);
            $document->setTransferStatus(Document::TRANSFER_SENT);

            $document->setState(DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE);

            $this->documentRepository->update($document);
            $this->documentRepository->remove($document);

            return true;
        } else {
            $document->setTransferStatus(Document::TRANSFER_ERROR);
            $this->documentRepository->update($document);
            return false;
        }

    }

    /**
     * Updates an existing document in the remote repository
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return boolean
     */
    public function update($document)
    {
        // remove document from local index
        $elasticsearchRepository = $this->objectManager->get(ElasticsearchRepository::class);
        $elasticsearchRepository->delete($document, "");

        $document->setTransferStatus(Document::TRANSFER_QUEUED);
        $this->documentRepository->update($document);

        $exporter = new \EWW\Dpf\Services\MetsExporter();

        $fileData = $document->getFileData();

        $exporter->setFileData($fileData);

        $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());

        $exporter->setMods($mods->getModsXml());

        $exporter->setSlubInfo($document->getSlubInfoData());

        $exporter->setObjId($document->getObjectIdentifier());

        $exporter->buildMets();

        $metsXml = $exporter->getMetsData();

        if ($this->remoteRepository->update($document, $metsXml)) {
            $document->setTransferStatus(Document::TRANSFER_SENT);
            $this->documentRepository->update($document);
            $this->documentRepository->remove($document);

            return true;
        } else {
            $document->setTransferStatus(Document::TRANSFER_ERROR);
            $this->documentRepository->update($document);
            return false;
        }

    }

    /**
     * Gets an existing document from the Fedora repository
     *
     * @param string $remoteId
     * @return boolean
     */
    public function retrieve($remoteId)
    {

        $metsXml = $this->remoteRepository->retrieve($remoteId);

        if ($this->documentRepository->findOneByObjectIdentifier($remoteId)) {
            throw new \Exception("Document already exist: $remoteId");
        };

        if ($metsXml) {
            $mets = new \EWW\Dpf\Helper\Mets($metsXml);
            $mods = $mets->getMods();
            $slub = $mets->getSlub();

            $title   = $mods->getTitle();
            $authors = $mods->getAuthors();

            $documentTypeName = $slub->getDocumentType();
            $documentType     = $this->documentTypeRepository->findOneByName($documentTypeName);

            if (empty($title) || empty($documentType)) {
                return false;
            }

            $state = $mets->getState();

            /* @var $document \EWW\Dpf\Domain\Model\Document */
            $document = $this->objectManager->get(Document::class);

            switch ($state) {
                case "ACTIVE":
                    $document->setState(DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE);
                    break;
                case "INACTIVE":
                    $document->setState(DocumentWorkflow::STATE_IN_PROGRESS_INACTIVE);
                    break;
                case "DELETED":
                    $document->setState(DocumentWorkflow::STATE_IN_PROGRESS_DELETED);
                    break;
                default:
                    throw new \Exception("Unknown object state: " . $state);
                    break;
            }

            $document->setObjectIdentifier($remoteId);
            $document->setTitle($title);
            $document->setAuthors($authors);
            $document->setDocumentType($documentType);

            $document->setXmlData($mods->getModsXml());
            $document->setSlubInfoData($slub->getSlubXml());

            $document->setDateIssued($mods->getDateIssued());

            $document->setProcessNumber($slub->getProcessNumber());

            $this->documentRepository->add($document);
            $this->persistenceManager->persistAll();

            foreach ($mets->getFiles() as $attachment) {

                $file = $this->objectManager->get(File::class);
                $file->setContentType($attachment['mimetype']);
                $file->setDatastreamIdentifier($attachment['id']);
                $file->setLink($attachment['href']);
                $file->setTitle($attachment['title']);
                $file->setLabel($attachment['title']);
                $file->setDownload($attachment['download']);
                $file->setArchive($attachment['archive']);
                $file->setFileGroupDeleted($attachment['deleted']);

                if ($attachment['id'] == \EWW\Dpf\Domain\Model\File::PRIMARY_DATASTREAM_IDENTIFIER) {
                    $file->setPrimaryFile(true);
                }

                $file->setDocument($document);

                $this->fileRepository->add($file);
            }

            return true;

        } else {
            return false;
        }

        return false;
    }

    /**
     * Removes an existing document from the Fedora repository
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param string $state
     * @return boolean
     */
    public function delete($document, $state)
    {

        $document->setTransferStatus(Document::TRANSFER_QUEUED);
        $this->documentRepository->update($document);

            switch ($state) {
                case "revert":
                    if ($this->remoteRepository->delete($document, $state)) {
                        $document->setTransferStatus(Document::TRANSFER_SENT);
                        $document->setState(DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE);
                        $this->documentRepository->update($document);
                        return true;
                    }
                    break;
                case "inactivate":
                    if ($this->remoteRepository->delete($document, $state)) {
                        $document->setTransferStatus(Document::TRANSFER_SENT);
                        $document->setState(DocumentWorkflow::STATE_IN_PROGRESS_INACTIVE);
                        $this->documentRepository->update($document);
                        return true;
                    }
                    break;
                default:
                    // remove document from local index
                    $elasticsearchRepository = $this->objectManager->get(ElasticsearchRepository::class);
                    $elasticsearchRepository->delete($document, $state);

                    if ($this->remoteRepository->delete($document, $state)) {
                        $document->setTransferStatus(Document::TRANSFER_SENT);
                        $document->setState(DocumentWorkflow::STATE_IN_PROGRESS_DELETED);
                        $this->documentRepository->update($document);
                        $this->documentRepository->remove($document);
                        return true;
                    }
                    break;
            }

            $document->setTransferStatus(Document::TRANSFER_ERROR);
            $this->documentRepository->update($document);
            return false;
    }

    public function getNextDocumentId()
    {
        $nextDocumentIdXML = $this->remoteRepository->getNextDocumentId();

        if (empty($nextDocumentIdXML)) {
            throw new \Exception("Couldn't get a valid document id from repository.");
        }

        $dom = new \DOMDocument();
        $dom->loadXML($nextDocumentIdXML);
        $xpath = new \DOMXpath($dom);

        $xpath->registerNamespace("management", "http://www.fedora.info/definitions/1/0/management/");
        $nextDocumentId = $xpath->query("/management:pidList/management:pid");

        return $nextDocumentId->item(0)->nodeValue;
    }

}
