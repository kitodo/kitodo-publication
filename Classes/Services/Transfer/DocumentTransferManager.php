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
use EWW\Dpf\Domain\Model\LocalDocumentStatus;
use EWW\Dpf\Domain\Model\RemoteDocumentStatus;
use EWW\Dpf\Helper\XSLTransformator;
use EWW\Dpf\Domain\Model\File;

class DocumentTransferManager
{
    const DELETE = "delete";
    const REVERT = "revert";
    const INACTIVATE = "inactivate";

    /**
     * documenRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentRepository;

    /**
     * documenTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentTypeRepository;

    /**
     * fileRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FileRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $fileRepository;

    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $objectManager;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @TYPO3\CMS\Extbase\Annotation\Inject
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
        $internalFormat = new \EWW\Dpf\Helper\InternalFormat($document->getXmlData());
        // Set current date as publication date
        $dateIssued = (new \DateTime)->format(\DateTime::ISO8601);
        $internalFormat->setDateIssued($dateIssued);
        $internalFormat->setCreator($document->getCreator());
        $internalFormat->setCreationDate($document->getCreationDate());

        $exporter = new \EWW\Dpf\Services\ParserGenerator();
        $exporter->setXML($internalFormat->getXml());
        $fileData = $document->getFileData();
        $fileData = $this->overrideFilePathIfEmbargo($document, $fileData);
        $exporter->setFileData($fileData);
        $document->setXmlData($exporter->getXMLData());

        $XSLTransformator = new XSLTransformator();
        $transformedXml = $XSLTransformator->getTransformedOutputXML($document);

        $remoteDocumentId = $this->remoteRepository->ingest($document, $transformedXml);

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
     * If embargo date is set, file path must not be published
     *
     * @param $document
     * @param $fileData
     * @return mixed
     * @throws \Exception
     */
    public function overrideFilePathIfEmbargo($document, $fileData) {
        $currentDate = new \DateTime('now');

        if ($currentDate < $document->getEmbargoDate()) {
            foreach ($fileData as $fileSection => $files) {
                foreach ($files as $fileId => $fileProperties) {
                    foreach ($fileProperties as $key => $value) {
                        if ($key == 'path') {
                            $fileData[$fileSection][$fileId][$key] = '#';
                        }
                    }
                    unset($fileData[$fileSection][$fileId]);
                }
            }
        }

        return $fileData;
    }

    /**
     * Updates an existing document in the remote repository
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return boolean
     */
    public function update($document)
    {
        $internalFormat = new \EWW\Dpf\Helper\InternalFormat($document->getXmlData());
        $internalFormat->setCreator($document->getCreator());
        $internalFormat->setCreationDate($document->getCreationDate());

        $exporter = new \EWW\Dpf\Services\ParserGenerator();
        $exporter->setXML($internalFormat->getXml());
        $fileData = $document->getFileData();
        $fileData = $this->overrideFilePathIfEmbargo($document, $fileData);
        $exporter->setFileData($fileData);
        $document->setXmlData($exporter->getXMLData());

        $transformedXml = $exporter->getTransformedOutputXML($document);

        if ($this->remoteRepository->update($document, $transformedXml)) {
            $document->setTransferStatus(Document::TRANSFER_SENT);
            $this->documentRepository->update($document);
            $this->documentRepository->remove($document);
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
        $remoteXml = $this->remoteRepository->retrieve($remoteId);
        
        if ($remoteXml) {

            $XSLTransformator = new XSLTransformator();
            $inputTransformedXML = $XSLTransformator->transformInputXML($remoteXml);

            $internalFormat = new \EWW\Dpf\Helper\InternalFormat($inputTransformedXML);

            $title = $internalFormat->getTitle();
            $authors = $internalFormat->getPersons();

            $documentTypeName = $internalFormat->getDocumentType();
            $documentType     = $this->documentTypeRepository->findOneByName($documentTypeName);

            if (empty($title) || empty($documentType)) {
                return false;
            }

            $state = $internalFormat->getRepositoryState();

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

            $document->setRemoteLastModDate($internalFormat->getRepositoryLastModDate());
            $document->setObjectIdentifier($remoteId);
            $document->setTitle($title);
            $document->setAuthors($authors);
            $document->setDocumentType($documentType);

            $document->setXmlData($inputTransformedXML);

            $document->setDateIssued($internalFormat->getDateIssued());

            $document->setProcessNumber($internalFormat->getProcessNumber());

            $creationDate = $internalFormat->getCreationDate();
            if (empty($creationDate)) {
                $creationDate = $internalFormat->getRepositoryCreationDate();
            }
            $document->setCreationDate($creationDate);
            $document->setCreator($internalFormat->getCreator());

            $document->setTemporary(TRUE);

            $this->documentRepository->add($document);
            $this->persistenceManager->persistAll();

            foreach ($internalFormat->getFiles() as $attachment) {

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
                $document->addFile($file);
            }

            $this->documentRepository->update($document);
            $this->persistenceManager->persistAll();

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

        if ($state == self::DELETE) {
            return $this->remoteRepository->delete($document, $state);
        }

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

    /**
     * Gets the last modification date of the remote document (remoteId)
     *
     * @param string $remoteId
     * @return string
     */
    public function getLastModDate($remoteId) {
        $remoteXml = $this->remoteRepository->retrieve($remoteId);
        if ($remoteXml) {
            $XSLTransformator = new XSLTransformator();
            $inputTransformedXML = $XSLTransformator->transformInputXML($remoteXml);
            $internalFormat = new \EWW\Dpf\Helper\InternalFormat($inputTransformedXML);
            return $internalFormat->getRepositoryLastModDate();
        }

        return '';
    }
}
