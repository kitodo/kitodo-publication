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
use EWW\Dpf\Domain\Model\File;

class DocumentTransferManager
{
    const DELETE = "";
    const REVERT = "revert";
    const INACTIVATE = "inactivate";

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
     * @return \EWW\Dpf\Domain\Model\Document|bool
     */
    public function ingest($document)
    {
        $this->documentRepository->update($document);

        $exporter = new \EWW\Dpf\Services\MetsExporter();

        $fileData = $document->getFileData();

        $exporter->setFileData($fileData);

        $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());

        // Set current date as publication date
        $dateIssued = (new \DateTime)->format(\DateTime::ISO8601);
        $mods->setDateIssued($dateIssued);

        $exporter->setMods($mods->getModsXml());

        // Set the document creator
        $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
        $slub->setDocumentCreator($document->getOwner());
        $exporter->setSlubInfo($slub->getSlubXml());

        $exporter->setObjId($document->getObjectIdentifier());

        $exporter->buildMets();

        $metsXml = $exporter->getMetsData();

        $remoteDocumentId = $this->remoteRepository->ingest($document, $metsXml);

        if ($remoteDocumentId) {
            $document->setDateIssued($dateIssued);
            $document->setObjectIdentifier($remoteDocumentId);
            $this->documentRepository->update($document);
            return $document;
        } else {
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
        $exporter = new \EWW\Dpf\Services\MetsExporter();

        $fileData = $document->getFileData();

        $exporter->setFileData($fileData);

        $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());

        $exporter->setMods($mods->getModsXml());

        // Set the document creator
        $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
        $slub->setDocumentCreator($document->getOwner());
        $exporter->setSlubInfo($slub->getSlubXml());

        $exporter->setObjId($document->getObjectIdentifier());

        $exporter->buildMets();

        $metsXml = $exporter->getMetsData();

        if ($this->remoteRepository->update($document, $metsXml)) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * Gets an existing document from the Fedora repository
     *
     * @param string $remoteId
     *
     * @return \EWW\Dpf\Domain\Model\Document|null
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function retrieve($remoteId)
    {
        $metsXml = $this->remoteRepository->retrieve($remoteId);

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
                    $document->setState(DocumentWorkflow::STATE_NONE_ACTIVE);
                    break;
                case "INACTIVE":
                    $document->setState(DocumentWorkflow::STATE_NONE_INACTIVE);
                    break;
                case "DELETED":
                    $document->setState(DocumentWorkflow::STATE_NONE_DELETED);
                    break;
                default:
                    throw new \Exception("Unknown object state: " . $state);
                    break;
            }

            $document->setRemoteLastModDate($mets->getLastModDate());
            $document->setObjectIdentifier($remoteId);
            $document->setTitle($title);
            $document->setAuthors($authors);
            $document->setDocumentType($documentType);

            $document->setXmlData($mods->getModsXml());
            $document->setSlubInfoData($slub->getSlubXml());

            $document->setDateIssued($mods->getDateIssued());

            $document->setProcessNumber($slub->getProcessNumber());

            $document->setOwner($slub->getDocumentCreator());

            $document->setTemporary(TRUE);

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

            return $document;

        } else {
            return NULL;
        }

        return NULL;
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
        if ($state == self::REVERT || $state == self::INACTIVATE) {
            return $this->remoteRepository->delete($document, $state);
        }

        return $this->remoteRepository->delete($document, $state);
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

    /**
     * Gets the last modification date of the remote document (remoteId)
     *
     * @param string $remoteId
     * @return string
     */
    public function getLastModDate($remoteId) {
        $metsXml = $this->remoteRepository->retrieve($remoteId);

        if ($metsXml) {
            $mets = new \EWW\Dpf\Helper\Mets($metsXml);
            return $mets->getLastModDate();
        }

        return NULL;
    }

}
