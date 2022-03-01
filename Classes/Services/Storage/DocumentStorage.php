<?php

namespace EWW\Dpf\Services\Storage;

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
 *
 */

use DateTime;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\File;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Helper\InternalFormat;
use EWW\Dpf\Helper\XSLTransformator;
use EWW\Dpf\Services\Storage\Exception\IngestDocumentException;
use EWW\Dpf\Services\Storage\Exception\UpdateDocumentException;
use EWW\Dpf\Services\Storage\Exception\RetrieveDocumentException;
use EWW\Dpf\Services\Storage\Exception\ConnectionException;
use EWW\Dpf\Services\Storage\Fedora\Exception\FedoraException;
use EWW\Dpf\Services\Storage\Fedora\FedoraTransaction;
use EWW\Dpf\Services\Storage\Fedora\ResourceTuple;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;

class DocumentStorage
{
    protected const XML_BINARY_ID = 'METS';
    protected const XML_FILE_NAME = 'mets.xml';
    protected const XML_CONTENT_TYPE = 'text/xml';

    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $objectManager = null;

    /**
     * documentManager
     *
     * @var \EWW\Dpf\Services\Storage\Fedora\FedoraTransaction
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $fedoraTransaction = null;

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentRepository = null;

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
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $persistenceManager;

    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientConfigurationManager;

    /**
     * logger
     *
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger = null;

    public function __construct()
    {
        /** @var $logger \TYPO3\CMS\Core\Log\Logger */
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * @param Document $document
     * @return Document|null
     * @throws ConnectionException
     * @throws FedoraException
     * @throws IngestDocumentException
     */
    public function ingest(Document $document) : ?Document
    {
        try {
            $transactionUri = $this->fedoraTransaction->start();

            $processNumber = $document->getProcessNumber();
            $containerId = strtolower($processNumber);
            $document->setObjectIdentifier($containerId);

            $this->fedoraTransaction->createContainer($transactionUri, $containerId);

            $internalFormat = new InternalFormat($document->getXmlData());

            // Set current date as publication date
            if ($this->clientConfigurationManager->isAlwaysSetDateIssued() || $document->isFullTextPublication()) {
                $dateIssued = (new DateTime)->format(DateTime::ISO8601);
                $internalFormat->setDateIssued($dateIssued);
                $document->setDateIssued($dateIssued);
            }

            $internalFormat->setCreator($document->getCreator());
            $internalFormat->setCreationDate($document->getCreationDate());

            $fileId = new FileId($document->getFile());

            /** @var File $file */
            foreach ($document->getFile() as $file) {
                if (!$file->isDeleted()) {
                    $fileSrc = strpos(strtolower($file->getLink()),
                        'http') === false ? $file->getFilePath() : $file->getLink();
                    $dataStreamIdentifier = $fileId->getId($file);
                    $file->setDatastreamIdentifier($dataStreamIdentifier);
                    $file->setLink($dataStreamIdentifier);

                    $this->fedoraTransaction->createBinary(
                        $transactionUri,
                        $containerId,
                        $dataStreamIdentifier,
                        $file->getContentType(),
                        $file->getTitle(),
                        $fileSrc
                    );
                }
            }

            $internalFormat->updateFileHrefs($document->getFile());
            $document->setXmlData($internalFormat->getXml());

            $XSLTransformator = new XSLTransformator();
            $transformedXml = $XSLTransformator->getTransformedOutputXML($document);

            $tmpFilePath = sys_get_temp_dir() . '/kitodo-publication-' . uniqid() . '.xml';
            file_put_contents($tmpFilePath, $transformedXml);

            $this->fedoraTransaction->createBinary(
                $transactionUri,
                $containerId,
                self::XML_BINARY_ID,
                self::XML_CONTENT_TYPE,
                self::XML_FILE_NAME,
                $tmpFilePath
            );

            unlink($tmpFilePath);

            $this->fedoraTransaction->commit($transactionUri);

            // TODO: Is this really needed inside ingest?
            $this->documentRepository->update($document);

            return $document;

        } catch (FedoraException $fedoraException) {
            $this->logger->warning(
                'Error while ingesting document "' . $document->getProcessNumber() . '". '
                . 'Ingest aborted.'
            );

            if ($fedoraException->getCode() === FedoraException::NO_CONNECTION) {
                throw new ConnectionException('Ingest document failed. No fedora connection.');
            }

            if (isset($transactionUri)) {
                $this->fedoraTransaction->rollback($transactionUri);
            }

            throw new IngestDocumentException('Ingest document failed.');
        }
    }

    /**
     * @param Document $document
     * @param string|null $state
     * @throws FedoraException
     * @throws IllegalObjectTypeException
     * @throws UpdateDocumentException
     */
    public function update(Document $document, string $state = null)
    {
        try {
            $transactionUri = $this->fedoraTransaction->start();

            $processNumber = $document->getProcessNumber();
            $containerId = strtolower($processNumber);

            $containerTuple = $this->fedoraTransaction->getResourceTuple($transactionUri, $containerId);

            if (empty($state)) {
                $state = $containerTuple->getValue('kp:state');
            }

            $lastModDate = $containerTuple->getValue('fedora:lastModified');
            $docLastModDate = $document->getRemoteLastModDate();
            if ($lastModDate !== $docLastModDate && !empty($docLastModDate)) {
                // There is a newer version in the fedora repository.
                if ($transactionUri) {
                    $this->fedoraTransaction->rollback($transactionUri);
                }

                $this->logger->warning(
                    'Error while updating document. Newer document version found. Update aborted.'
                );

                throw UpdateDocumentException::createNewerVersion(
                    'Update document failed. A newer version of the document exists. Update aborted.'
                );
            }

            $internalFormat = new InternalFormat($document->getXmlData());

            $dateIssued = $document->getDateIssued();

            if (empty($dateIssued)) {
                if (
                    $this->clientConfigurationManager->isAlwaysSetDateIssued()
                    || ($document->isFullTextPublication() && $state === DocumentWorkflow::REMOTE_STATE_ACTIVE)
                ) {
                    $dateIssued = (new DateTime)->format(DateTime::ISO8601);
                    $internalFormat->setDateIssued($dateIssued);
                    $document->setDateIssued($dateIssued);
                }
            }

            // Update binary tuple to force change of lastModified
            $containerTuple->setValue('kp:state', $state);
            $this->fedoraTransaction->updateResourceTuple($transactionUri, $containerTuple, $containerId);

            $internalFormat->setCreator($document->getCreator());
            $internalFormat->setCreationDate($document->getCreationDate());

            // Update files / attachments.
            $fileId = new FileId($document->getFile());

            /** @var File $file */
            foreach ($document->getFile() as $file) {
                if (!$file->isFileGroupDeleted()) {
                    if ($file->isDeleted()) {
                        $dataStreamIdentifier = $file->getDatastreamIdentifier();

                        if ($dataStreamIdentifier) {
                            $fileTuple = $this->fedoraTransaction->getResourceTuple(
                                $transactionUri, $containerId, $dataStreamIdentifier
                            );

                            $fileTuple->setValue('kp:state', DocumentWorkflow::REMOTE_STATE_DELETED);
                            $this->fedoraTransaction->updateResourceTuple(
                                $transactionUri, $fileTuple, $containerId, $dataStreamIdentifier
                            );
                        }
                    } else {
                        $fileSrc = strpos(strtolower($file->getLink()), 'http') === false ? $file->getFilePath() : $file->getLink();
                        $dataStreamIdentifier = $fileId->getId($file);
                        $file->setDatastreamIdentifier($dataStreamIdentifier);
                        $file->setLink($dataStreamIdentifier);

                        if ($file->getStatus() === File::STATUS_ADDED) {
                            $this->fedoraTransaction->createBinary(
                                $transactionUri,
                                $containerId,
                                $dataStreamIdentifier,
                                $file->getContentType(),
                                $file->getTitle(),
                                $fileSrc
                            );

                        } elseif ($file->getStatus() === File::STATUS_CHANGED) {
                            $this->fedoraTransaction->updateContent(
                                $transactionUri,
                                $containerId,
                                $dataStreamIdentifier,
                                $file->getContentType(),
                                $file->getTitle(),
                                $fileSrc
                            );
                        }
                    }
                }
            }

            $internalFormat->updateFileHrefs($document->getFile());
            $document->setXmlData($internalFormat->getXml());

            $XSLTransformator = new XSLTransformator();
            $transformedXml = $XSLTransformator->getTransformedOutputXML($document);

            $tmpFilePath = sys_get_temp_dir().'/kitodo-publication-' . uniqid() . '.xml';
            file_put_contents($tmpFilePath, $transformedXml);

            $this->fedoraTransaction->updateContent(
                $transactionUri,
                $containerId,
                self::XML_BINARY_ID,
                self::XML_CONTENT_TYPE,
                self::XML_FILE_NAME,
                $tmpFilePath
            );

            unlink($tmpFilePath);

            $this->documentRepository->update($document);

            // TODO:
            //  Why is the document removed?
            //  Could this lead to an error or inconsistency in case of an embargo.
            //  See method updateRemotely() in the DocumentManager.
            $this->documentRepository->remove($document);

            $this->fedoraTransaction->commit($transactionUri);

        } catch (FedoraException $fedoraException) {
            $this->logger->warning(
                'Error while updating document "' . $document->getProcessNumber() . '". ' . 'Update aborted.'
            );

            if (isset($transactionUri)) {
                $this->fedoraTransaction->rollback($transactionUri);
            }
            throw UpdateDocumentException::create('Update document failed. Update aborted.');
        }
    }

    /**
     * @param string $documentIdentifier
     * @return Document|null
     * @throws ConnectionException
     * @throws IllegalObjectTypeException
     * @throws RetrieveDocumentException
     */
    public function retrieve(string $documentIdentifier) : ?Document
    {
        try {
            /** @var ResourceTuple $resourceTuple */
            $resourceTuple = $this->fedoraTransaction->getResourceTuple(null, $documentIdentifier);

            $state = $resourceTuple->getValue('kp:state');
            $repositoryLastModified = $resourceTuple->getValue('fedora:lastModified');
            $repositoryCreationDate = $resourceTuple->getValue('fedora:created');

            $remoteXml = $this->fedoraTransaction->getContent(null, $documentIdentifier,
                self::XML_BINARY_ID);

            if ($remoteXml) {
                $XSLTransformator = new XSLTransformator();
                $inputTransformedXML = $XSLTransformator->transformInputXML($remoteXml);

                $internalFormat = new InternalFormat($inputTransformedXML);

                $title = $internalFormat->getTitle();
                $authors = $internalFormat->getPersons();

                $documentTypeName = $internalFormat->getDocumentType();
                $documentType = $this->documentTypeRepository->findOneByName($documentTypeName);

                if (empty($title) || empty($documentType)) {
                    $this->logger->warning(
                        'Error while retrieving document "' . $documentIdentifier . '". '
                        . 'No document title or invalid document type. Retrieve aborted.'
                    );

                    throw new RetrieveDocumentException('Retrieve document failed.');
                }

                // TODO: repository state is no longer inside metsxml
                //  => $state = $internalFormat->getRepositoryState();

                /** @var $document Document */
                $document = $this->objectManager->get(Document::class);

                switch (strtoupper($state)) {
                    case DocumentWorkflow::REMOTE_STATE_ACTIVE:
                        $document->setState(DocumentWorkflow::STATE_NONE_ACTIVE);
                        break;
                    case DocumentWorkflow::REMOTE_STATE_INACTIVE:
                        $document->setState(DocumentWorkflow::STATE_NONE_INACTIVE);
                        break;
                    case DocumentWorkflow::REMOTE_STATE_DELETED:
                        $document->setState(DocumentWorkflow::STATE_NONE_DELETED);
                        break;
                    default:
                        $this->logger->warning(
                            'Error while retrieving document "' . $documentIdentifier . '". '
                            . 'Invalid document state. Retrieve aborted.'
                        );

                        throw new RetrieveDocumentException(
                            "Retrieve document failed. Invalid document state.");
                        break;
                }

                // TODO: Lastmoddate is no longer inside metsxml
                //  => $document->setRemoteLastModDate($internalFormat->getRepositoryLastModDate());
                $document->setRemoteLastModDate($repositoryLastModified);

                $document->setObjectIdentifier($documentIdentifier);
                $document->setTitle($title);
                $document->setAuthors($authors);
                $document->setDocumentType($documentType);

                $document->setXmlData($inputTransformedXML);

                $document->setDateIssued($internalFormat->getDateIssued());

                $document->setProcessNumber($internalFormat->getProcessNumber());

                // TODO: creationDate is no longer inside metsxml
                //  => $creationDate = $internalFormat->getRepositoryCreationDate()
                $creationDate = $internalFormat->getCreationDate();
                $creationDate = $creationDate ? $creationDate : $repositoryCreationDate;
                $document->setCreationDate($creationDate);

                $document->setCreator($internalFormat->getCreator());

                $document->setTemporary(true);

                $this->documentRepository->add($document);
                $this->persistenceManager->persistAll();

                foreach ($internalFormat->getFiles() as $attachment) {

                    /** @var File $file */
                    $file = $this->objectManager->get(File::class);
                    $file->setContentType($attachment['mimetype']);
                    $file->setDatastreamIdentifier($attachment['id']);
                    $file->setFileIdentifier($attachment['id']);
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
                return null;
            }

            return null;

        } catch (FedoraException $fedoraException) {
            $this->logger->warning(
                'Error while retrieving document "' . $documentIdentifier . '". '
                . 'Retrieve aborted.'
            );

            if ($fedoraException->getCode() === FedoraException::NO_CONNECTION) {
                throw new ConnectionException('Retrieve document: No fedora connection.');
            }

            if ($fedoraException->getCode() === FedoraException::NOTHING_FOUND) {
                throw RetrieveDocumentException::createNotFound(
                    'Retrieve document failed. Nothing found.', FedoraException::NOTHING_FOUND
                );
            }

            throw new RetrieveDocumentException('Retrieve document failed.');
        }
    }
}
