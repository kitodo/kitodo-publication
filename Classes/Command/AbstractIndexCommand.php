<?php
namespace EWW\Dpf\Command;

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
use EWW\Dpf\Domain\Model\File;
use EWW\Dpf\Domain\Repository\ClientRepository;
use EWW\Dpf\Domain\Repository\DocumentRepository;
use EWW\Dpf\Domain\Repository\DocumentTypeRepository;
use EWW\Dpf\Domain\Repository\FileRepository;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Helper\InternalFormat;
use EWW\Dpf\Helper\XSLTransformator;
use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class AbstractIndexCommand extends Command
{
    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     */
    protected $clientRepository = null;

    /**
     * fileRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FileRepository
     */
    protected $fileRepository = null;

    /**
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     */
    protected $documentTypeRepository = null;

    /**
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     */
    protected $documentRepository = null;

    /**
     * @var ObjectManager
     */
    protected $objectManager = null;

    public function __construct(string $name = null)
    {
        parent::__construct($name);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->objectManager = $objectManager;
        $this->clientRepository = $objectManager->get(ClientRepository::class);
        $this->fileRepository = $objectManager->get(FileRepository::class);
        $this->documentTypeRepository = $objectManager->get(DocumentTypeRepository::class);
        $this->documentRepository = $objectManager->get(DocumentRepository::class);
    }

    /**
     * @param $xml
     * @return Document|false|null
     * @throws \Exception
     */
    protected function createDocument($xml)
    {
        if ($xml) {
            $domDocument = new \DOMDocument();
            $domDocument->loadXML($xml);
            $domXPath = \EWW\Dpf\Helper\XPath::create($domDocument);
            $objectIdentifier = $domXPath->query('@OBJID')->item(0)->nodeValue;

            $XSLTransformator = new XSLTransformator();
            $inputTransformedXML = $XSLTransformator->transformInputXML($xml);

            $internalFormat = new InternalFormat($inputTransformedXML);

            $title = $internalFormat->getTitle();
            $authors = $internalFormat->getPersons();

            $documentTypeName = $internalFormat->getDocumentType();
            $documentType     = $this->documentTypeRepository->findOneByName($documentTypeName);

            if (empty($title) || empty($documentType) || empty($objectIdentifier)) {
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
            $document->setObjectIdentifier($objectIdentifier);
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

                if ($attachment['id'] == File::PRIMARY_DATASTREAM_IDENTIFIER) {
                    $file->setPrimaryFile(true);
                }

                $document->addFile($file);
            }

            return $document;
        }

        return null;
    }

}
