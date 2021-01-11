<?php
namespace EWW\Dpf\Controller;

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
use EWW\Dpf\Services\ImportExternalMetadata\BibTexFileImporter;
use EWW\Dpf\Services\ImportExternalMetadata\CrossRefImporter;
use EWW\Dpf\Services\ImportExternalMetadata\DataCiteImporter;
use EWW\Dpf\Services\ImportExternalMetadata\FileImporter;
use EWW\Dpf\Services\ImportExternalMetadata\K10plusImporter;
use EWW\Dpf\Services\ImportExternalMetadata\PubMedImporter;
use EWW\Dpf\Services\ImportExternalMetadata\RisWosFileImporter;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Log\LogManager;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;

/**
 * DocumentController
 */
class ApiController extends ActionController
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\View\JsonView
     */
    protected $view;

    /**
     * @var string
     */
    protected $defaultViewObjectName = \TYPO3\CMS\Extbase\Mvc\View\JsonView::class;


    /**
     * security
     *
     * @var \EWW\Dpf\Security\Security
     * @inject
     */
    protected $security = null;

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository = null;

    /**
     * frontendUserRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserRepository
     * @inject
     */
    protected $frontendUserRepository = null;

    /**
     * documentManager
     *
     * @var \EWW\Dpf\Services\Document\DocumentManager
     * @inject
     */
    protected $documentManager = null;

    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     * @inject
     */
    protected $clientRepository;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @inject
     */
    protected $persistenceManager;

    /**
     * signalSlotDispatcher
     *
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     * @inject
     */
    protected $signalSlotDispatcher = null;

    /**
     * logger
     *
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger = null;

    protected $frontendUser = null;


    public function __construct()
    {
        /** @var $logger \TYPO3\CMS\Core\Log\Logger */
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    public function checkToken($token) {
        // check if token exists
        $frontendUser = $this->frontendUserRepository->findOneByApiToken($token);

        if ($frontendUser) {
            $this->frontendUser = $frontendUser;
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $document
     * @param string $token
     */
    public function showAction($document, $token) {
        if ($this->checkToken($token)) {
            /** @var Document $doc */
            $doc = $this->documentManager->read($document);
            //$doc = $this->documentRepository->findByIdentifier($document);

            if ($doc) {
                $this->security->getUser()->getUid();

                /** @var $client \EWW\Dpf\Domain\Model\Client */
                $client = $this->clientRepository->findAllByPid($this->frontendUser->getPid())->current();

                $mapper = new \EWW\Dpf\Services\Api\DocumentToJsonMapper();
                $mapper->setMapping($client->getFisMapping());
                $jsonData = $mapper->getJson($doc);
                return $jsonData;
            }

            return '{"error": "No data found"}';
        }
        return '{"error": "Token failed"}';
    }

    /**
     * @param string $json
     * @param string $token
     */
    public function createAction($json, $token) {
        if ($this->checkToken($token)) {
            if ($json) {
                $jsonData = $json;
            }

            if (empty($jsonData)) {
                return '{"error": "invalid data"}';
            }

            $mapper = $this->objectManager->get(\EWW\Dpf\Services\Api\JsonToDocumentMapper::class);

            /** @var Document $document */
            $document = $mapper->getDocument($jsonData);

            if ($this->tokenUserId) {
                $document->setCreator($this->security->getUser()->getUid());
            }

            // xml data fields are limited to 64 KB
            if (strlen($document->getXmlData()) >= 64 * 1024 || strlen($document->getSlubInfoData() >= 64 * 1024)) {
                return '{"error": "Maximum document size exceeded"}';
            }

            $processNumber = $document->getProcessNumber();
            if (empty($processNumber)) {
                $processNumberGenerator = $this->objectManager->get(ProcessNumberGenerator::class);
                $processNumber = $processNumberGenerator->getProcessNumber();
                $document->setProcessNumber($processNumber);
            }

            $this->documentRepository->add($document);
            $this->persistenceManager->persistAll();

            // index the document
            $this->signalSlotDispatcher->dispatch(
                AbstractController::class, 'indexDocument', [$document]
            );

            return '{"success": "Document created", "id": "' . $document->getProcessNumber() . '"}';
        }
        return '{"error": "Token failed"}';

    }

    /**
     * @param string $document
     * @param string $id
     * @param string $token
     * @throws \Exception
     */
    public function addFisIdAction($document, $id, $token) {
        if ($this->checkToken($token)) {
            /** @var Document $doc */
            $doc = $this->documentManager->read($document);
            $slub = new \EWW\Dpf\Helper\Slub($doc->getSlubInfoData());
            $slub->setFisId($id);

            $doc->setSlubInfoData($slub->getSlubXml());

            $processNumber = $doc->getProcessNumber();
            if (empty($processNumber)) {
                $processNumberGenerator = $this->objectManager->get(ProcessNumberGenerator::class);
                $processNumber = $processNumberGenerator->getProcessNumber();
                $doc->setProcessNumber($processNumber);
            }

            $this->documentManager->update($doc);

            // index the document
            $this->signalSlotDispatcher->dispatch(
                AbstractController::class, 'indexDocument', [$doc]
            );

            return '{"success": "Document '.$document.' added '.$id.'"}';
        }
        return '{"error": "Token failed"}';

    }

    /**
     * @param string $document
     * @param string $json
     * @param string $comment
     * @param string $token
     * @param bool $restore
     * @return string
     */
    public function suggestionAction($document, $json, $comment, $token, $restore = false) {
        if ($this->checkToken($token)) {
            /** @var Document $doc */
            $doc = $this->documentManager->read($document);

            if ($json) {
                $jsonData = $json;
            }

            if (empty($jsonData) && $restore == false) {
                return '{"error": "invalid data"}';
            }

            $mapper = $this->objectManager->get(\EWW\Dpf\Services\Api\JsonToDocumentMapper::class);

            /** @var Document $editOrigDocument */
            $editOrigDocument = $mapper->editDocument($doc, $jsonData);

            $suggestionDocument = $this->documentManager->addSuggestion($editOrigDocument, $restore, $comment);

            if ($restore) {
                $suggestionDocument->setTransferStatus("RESTORE");
            }

            if ($suggestionDocument) {
                return '{"success": "Suggestion created", "id": "' . $suggestionDocument->getDocumentIdentifier() . '"}';
            } else {
                return '{"failed": "Suggestion not created"}';
            }
        }
        return '{"error": "Token failed"}';
    }

    /**
     * @param string $doi
     * @param string $token
     */
    public function importDoiWithoutSavingAction(string $doi, $token) {
        if ($this->checkToken($token)) {
            $importer = $this->objectManager->get(CrossRefImporter::class);
            $externalMetadata = $importer->findByIdentifier($doi);
            if (!$externalMetadata) {
                $importer = $this->objectManager->get(DataCiteImporter::class);
                $externalMetadata = $importer->findByIdentifier($doi);
            }

            if ($externalMetadata) {
                // create document
                try {
                    /** @var Document $newDocument */
                    $newDocument = $importer->import($externalMetadata);
                    if ($newDocument) {
                        /** @var $client \EWW\Dpf\Domain\Model\Client */
                        $client = $this->clientRepository->findAllByPid($this->frontendUser->getPid())->current();

                        $mapper = new \EWW\Dpf\Services\Api\DocumentToJsonMapper();
                        $mapper->setMapping($client->getFisMapping());
                        $jsonData = $mapper->getJson($newDocument);
                        return $jsonData;
                    } else {
                        return '{"failed": "Import failed"}';
                    }

                } catch (\Throwable $throwable) {

                    $this->logger->error($throwable->getMessage());
                    return '{"failed": "' . $throwable->getMessage() . '"}';
                }

            } else {
                // error
                return '{"failed": "Nothing found"}';
            }
        }
        return '{"error": "Token failed"}';
    }

    /**
     * @param string $pmid
     * @param string $token
     * @return string
     */
    public function importPubmedWithoutSavingAction($pmid, $token) {
        if ($this->checkToken($token)) {
            $importer = $this->objectManager->get(PubMedImporter::class);
            $externalMetadata = $importer->findByIdentifier($pmid);

            if ($externalMetadata) {
                // create document
                try {
                    /** @var Document $newDocument */
                    $newDocument = $importer->import($externalMetadata);
                    if ($newDocument) {
                        /** @var $client \EWW\Dpf\Domain\Model\Client */
                        $client = $this->clientRepository->findAllByPid($this->frontendUser->getPid())->current();

                        $mapper = new \EWW\Dpf\Services\Api\DocumentToJsonMapper();
                        $mapper->setMapping($client->getFisMapping());
                        $jsonData = $mapper->getJson($newDocument);
                        return $jsonData;
                    } else {
                        return '{"failed": "Import failed"}';
                    }

                } catch (\Throwable $throwable) {

                    $this->logger->error($throwable->getMessage());
                    return '{"failed": "' . $throwable->getMessage() . '"}';
                }

            } else {
                // error
                return '{"failed": "Nothing found"}';
            }
        }
        return '{"error": "Token failed"}';
    }

    /**
     * @param string $isbn
     * @param string $token
     * @return string
     */
    public function importIsbnWithoutSavingAction($isbn, $token) {
        if ($this->checkToken($token)) {
            $importer = $this->objectManager->get(K10plusImporter::class);
            $externalMetadata = $importer->findByIdentifier(str_replace('- ', '', $isbn));

            if ($externalMetadata) {
                // create document
                try {
                    /** @var Document $newDocument */
                    $newDocument = $importer->import($externalMetadata);
                    if ($newDocument) {
                        /** @var $client \EWW\Dpf\Domain\Model\Client */
                        $client = $this->clientRepository->findAllByPid($this->frontendUser->getPid())->current();

                        $mapper = new \EWW\Dpf\Services\Api\DocumentToJsonMapper();
                        $mapper->setMapping($client->getFisMapping());
                        $jsonData = $mapper->getJson($newDocument);
                        return $jsonData;
                    } else {
                        return '{"failed": "Import failed"}';
                    }

                } catch (\Throwable $throwable) {

                    $this->logger->error($throwable->getMessage());
                    return '{"failed": "' . $throwable->getMessage() . '"}';
                }

            } else {
                // error
                return '{"failed": "Nothing found"}';
            }
        }
        return '{"error": "Token failed"}';
    }

    /**
     * @param string $bibtex content of a bibtex file
     * @param string $token
     * @return string
     */
    public function importBibtexWithoutSavingAction($bibtex, $token) {

        if ($this->checkToken($token)) {
            $importer = $this->objectManager->get(BibTexFileImporter::class);

            try {
                $externalMetadata = $importer->loadFile($bibtex, $this->settings['bibTexMandatoryFields'], true);
            } catch (\Throwable $throwable) {
                return '{"failed": "' . $throwable->getMessage() . '"}';
            }

            if ($externalMetadata) {
                // create document
                try {
                    /** @var Document $newDocument */
                    $newDocument = $importer->import($externalMetadata[0]);
                    if ($newDocument) {
                        /** @var $client \EWW\Dpf\Domain\Model\Client */
                        $client = $this->clientRepository->findAllByPid($this->frontendUser->getPid())->current();

                        $mapper = new \EWW\Dpf\Services\Api\DocumentToJsonMapper();
                        $mapper->setMapping($client->getFisMapping());
                        $jsonData = $mapper->getJson($newDocument);
                        return $jsonData;
                    } else {
                        return '{"failed": "Import failed"}';
                    }

                } catch (\Throwable $throwable) {

                    $this->logger->error($throwable->getMessage());
                    return '{"failed": "' . $throwable->getMessage() . '"}';
                }

            } else {
                $mandatoryErrors = $importer->getMandatoryErrors();
                $message = '';
                foreach ($mandatoryErrors as $mandatoryError) {
                    $message .= 'Konnte die Publikation Nr. ' . $mandatoryError['index'] . ' nicht importieren';
                    $message .= $mandatoryError['title'] ? ' (' . $mandatoryError['title'] . ')' : '';
                    $message .= ', da die folgenden Felder leer sind: ' . implode(',', $mandatoryError['fields']);
                }
                // error
                return '{"failed": "' . $message . '"}';
            }
        }
        return '{"error": "Token failed"}';
    }

    /**
     * @param string $ris
     * @param string $token
     * @return string
     */
    public function importRisWithoutSavingAction($ris, $token) {
        if ($this->checkToken($token)) {
            /** @var FileImporter $fileImporter */
            $importer = $this->objectManager->get(RisWosFileImporter::class);

            try {
                $externalMetadata = $importer->loadFile($ris, $this->settings['riswosMandatoryFields'], true);
            } catch (\Throwable $throwable) {
                return '{"failed": "' . $throwable->getMessage() . '"}';
            }

            if ($externalMetadata) {
                // create document
                try {
                    /** @var Document $newDocument */
                    $newDocument = $importer->import($externalMetadata[0]);
                    if ($newDocument) {
                        /** @var $client \EWW\Dpf\Domain\Model\Client */
                        $client = $this->clientRepository->findAllByPid($this->frontendUser->getPid())->current();

                        $mapper = new \EWW\Dpf\Services\Api\DocumentToJsonMapper();
                        $mapper->setMapping($client->getFisMapping());
                        $jsonData = $mapper->getJson($newDocument);
                        return $jsonData;
                    } else {
                        return '{"failed": "Import failed"}';
                    }

                } catch (\Throwable $throwable) {

                    $this->logger->error($throwable->getMessage());
                    return '{"failed": "' . $throwable->getMessage() . '"}';
                }

            } else {
                $mandatoryErrors = $importer->getMandatoryErrors();
                $message = '';
                foreach ($mandatoryErrors as $mandatoryError) {
                    $message .= 'Konnte die Publikation Nr. ' . $mandatoryError['index'] . ' nicht importieren';
                    $message .= $mandatoryError['title'] ? ' (' . $mandatoryError['title'] . ')' : '';
                    $message .= ', da die folgenden Felder leer sind: ' . implode(',', $mandatoryError['fields']);
                }
                // error
                return '{"failed": "' . $message . '"}';
            }
        }
        return '{"error": "Token failed"}';
    }


//    /**
//     * Resolves and checks the current action method name
//     *
//     * @return string Method name of the current action
//     */
//    protected function resolveActionMethodName()
//    {
//        switch ($this->request->getMethod()) {
//            case 'HEAD':
//            case 'GET':
//                $actionName = ($this->request->hasArgument('document')) ? 'show' : 'list';
//                break;
//            case 'POST':
//                $actionName = 'create';
//                break;
//            case 'PUT':
//            case 'DELETE':
//                $this->throwStatus(400, null, 'Bad Request.');
//            default:
//                $this->throwStatus(400, null, 'Bad Request.');
//        }
//
//        return $actionName . 'Action';
//    }
}
