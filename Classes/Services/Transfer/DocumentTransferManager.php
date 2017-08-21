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

use \EWW\Dpf\Domain\Model\Document;

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
        //$dateIssued = $mods->getDateIssued();
        //if (empty($dateIssued)) {
        $dateIssued = (new \DateTime)->format(\DateTime::ISO8601);
        $mods->setDateIssued($dateIssued);
        //}

        $exporter->setMods($mods->getModsXml());

        $exporter->setSlubInfo($document->getSlubInfoData());

        $exporter->setObjId($document->getObjectIdentifier());

        $exporter->buildMets();

        $metsXml = $exporter->getMetsData();

        $remoteDocumentId = $this->remoteRepository->ingest($document, $metsXml);

        if ($remoteDocumentId) {
            $document->setDateIssued($dateIssued);
            $document->setObjectIdentifier($remoteDocumentId);
            $document->setTransferStatus(Document::TRANSFER_SENT);
            $this->documentRepository->update($document);
            $this->documentRepository->remove($document);

            // remove document from local index
            $elasticsearchRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\ElasticsearchRepository');
            $elasticsearchRepository->delete($document, "");

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

        $document->setTransferStatus(Document::TRANSFER_QUEUED);
        $this->documentRepository->update($document);

        $exporter = new \EWW\Dpf\Services\MetsExporter();

        $fileData = $document->getFileData();

        $exporter->setFileData($fileData);

        $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
        //$dateIssued = $mods->getDateIssued();
        //if (empty($dateIssued)) {
        $dateIssued = $document->getDateIssued();
        $mods->setDateIssued($dateIssued);
        //}

        $exporter->setMods($mods->getModsXml());

        $exporter->setSlubInfo($document->getSlubInfoData());

        $exporter->setObjId($document->getObjectIdentifier());

        $exporter->buildMets();

        $metsXml = $exporter->getMetsData();

        if ($this->remoteRepository->update($document, $metsXml)) {
            $document->setTransferStatus(Document::TRANSFER_SENT);
            $this->documentRepository->update($document);
            $this->documentRepository->remove($document);

            // remove document from local index
            $elasticsearchRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\ElasticsearchRepository');
            $elasticsearchRepository->delete($document, "");

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
            $document = \EWW\Dpf\Domain\Factory\DocumentFactory::createFromMets($remoteId, $metsXml);
            if ($document) {
                $this->documentRepository->add($document);
                return true;
            }
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

        if ($this->remoteRepository->delete($document, $state)) {
            $document->setTransferStatus(Document::TRANSFER_SENT);

            switch ($state) {
                case "revert":
                    $document->setState(Document::OBJECT_STATE_ACTIVE);
                    $this->documentRepository->update($document);
                    break;
                case "inactivate":
                    $document->setState(Document::OBJECT_STATE_INACTIVE);
                    $this->documentRepository->update($document);
                    break;
                default:
                    $document->setState(Document::OBJECT_STATE_DELETED);
                    $this->documentRepository->update($document);
                    $this->documentRepository->remove($document);
                    // remove document from local index
                    $elasticsearchRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\ElasticsearchRepository');
                    $elasticsearchRepository->delete($document, $state);
                    break;
            }

            return true;
        } else {
            $document->setTransferStatus(Document::TRANSFER_ERROR);
            $this->documentRepository->update($document);
            return false;
        }
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
