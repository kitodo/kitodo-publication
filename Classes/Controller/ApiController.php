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
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Helper\DocumentMapper;
use EWW\Dpf\Services\Api\InvalidJson;
use EWW\Dpf\Services\Email\Notifier;
use EWW\Dpf\Services\ImportExternalMetadata\BibTexFileImporter;
use EWW\Dpf\Services\ImportExternalMetadata\CrossRefImporter;
use EWW\Dpf\Services\ImportExternalMetadata\DataCiteImporter;
use EWW\Dpf\Services\ImportExternalMetadata\FileImporter;
use EWW\Dpf\Services\ImportExternalMetadata\K10plusImporter;
use EWW\Dpf\Services\ImportExternalMetadata\PubMedImporter;
use EWW\Dpf\Services\ImportExternalMetadata\RisWosFileImporter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $security = null;

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentRepository = null;

    /**
     * frontendUserRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $frontendUserRepository = null;

    /**
     * documentManager
     *
     * @var \EWW\Dpf\Services\Document\DocumentManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentManager = null;

    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientRepository;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $persistenceManager;

    /**
     * signalSlotDispatcher
     *
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $signalSlotDispatcher = null;

    /**
     * logger
     *
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger = null;

    protected $frontendUser = null;

    protected $validActions = [
        'show', 'create', 'suggestion', 'importDoiWithoutSaving',
        'importPubmedWithoutSaving', 'importIsbnWithoutSaving',
        'importBibtexWithoutSaving', 'importRisWithoutSaving', 'addFisId'
    ];

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
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function initializeShowAction()
    {
        $this->checkMandatoryParameters(['document', 'token']);
    }

    /**
     * @param string $document
     * @param string $token
     */
    public function showAction($document, $token) {
        if ($this->checkToken($token)) {
            /** @var Document $doc */
            $doc = $this->documentManager->read($document);

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
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function initializeCreateAction()
    {
        $this->checkMandatoryParameters(['json', 'token']);
    }

    /**
     * @param string $json
     * @param string $token
     */
    public function createAction($json, $token) {

        if ($this->checkToken($token)) {

            if (is_null(json_decode($json))) {
                return '{"error": "Invalid data in parameter json"}';
            }

            $mapper = $this->objectManager->get(\EWW\Dpf\Services\Api\JsonToDocumentMapper::class);

            /** @var Document $document */
            try {
                $document = $mapper->getDocument($json);
            } catch (InvalidJson $throwable) {
                return '{"failed": "'.$throwable->getMessage().'"}';
            } catch (\Throwable $throwable) {
                return '{"error": "Invalid data in parameter json."}';
            }

            if ($this->tokenUserId) {
                $document->setCreator($this->security->getUser()->getUid());
            }

            // xml data fields are limited to 64 KB
            if (strlen($document->getXmlData()) >= Document::XML_DATA_SIZE_LIMIT) {
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
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function initializeAddFisIdAction()
    {
        $this->checkMandatoryParameters(['document', 'id', 'token']);
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

            $internalFormat = new \EWW\Dpf\Helper\InternalFormat($doc->getXmlData());
            $internalFormat->setFisId($id);
            $doc->setXmlData($internalFormat->getXml());

            $processNumber = $doc->getProcessNumber();
            if (empty($processNumber)) {
                $processNumberGenerator = $this->objectManager->get(ProcessNumberGenerator::class);
                $processNumber = $processNumberGenerator->getProcessNumber();
                $doc->setProcessNumber($processNumber);
            }

            /** @var DocumentMapper $documentMapper */
            $documentMapper = $this->objectManager->get(DocumentMapper::class);
            // Fixme: Since the JsonToDocumentMapper does not handle the metadata-item-id for groups and fields
            // this ensures we have metadata-item-ids in the resulting xml data.
            $documentForm = $documentMapper->getDocumentForm($doc);
            $doc = $documentMapper->getDocument($documentForm);

            if ($this->documentManager->update($doc, null,true)) {
                return '{"success": "Document '.$document.' added '.$id.'"}';
            } else {
                return '{"failed": Could not update the Document"}';
            }
        }
        return '{"error": "Token failed"}';

    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function initializeSuggestionAction()
    {
        $this->checkMandatoryParameters(['document', 'json', 'comment', 'token']);
        if ($this->request->hasArgument('restore')) {
            $restore = $this->request->getArgument('restore');
            $this->request->setArgument('restore', ($restore === 'true' || $restore == 1));
        }
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

            if ($restore) {
                if (!empty($json) && is_null(json_decode($json))) {
                    return '{"error": "Invalid data in parameter json."}';
                }
            } else {
                if (empty($json) || json_decode($json,true) === []) {
                    return '{"error": "Parameter json can not be empty."}';
                }
                if (is_null(json_decode($json))) {
                    return '{"error": "Invalid data in parameter json."}';
                }
            }

            /** @var Document $doc */
            $doc = $this->documentManager->read($document);

            if (!$doc) {
                return '{"failed": "Document does not exist: '.$document.'"}';
            }

            if ($doc->getState() === DocumentWorkflow::STATE_NEW_NONE) {
                return '{"failed": "Access denied. The document is private."}';
            }

            $linkedDocument = $this->documentRepository->findOneByLinkedUid($doc->getUid());
            if (!$linkedDocument && $doc->getObjectIdentifier()) {
                $linkedDocument = $this->documentRepository->findOneByLinkedUid($doc->getObjectIdentifier());
            }

            if ($linkedDocument) {
                return '{"failed": "There is already a suggestion for the document: '.$linkedDocument->getUid().'"}';
            }

            $mapper = $this->objectManager->get(\EWW\Dpf\Services\Api\JsonToDocumentMapper::class);

            /** @var Document $editOrigDocument */
            try {
                $editOrigDocument = $mapper->editDocument($doc, $json);
            } catch (InvalidJson $throwable) {
                return '{"failed": "'.$throwable->getMessage().'"}';
            } catch (\Throwable $throwable) {
                return '{"error": "Invalid data in parameter json."}';
            }

            $editOrigDocument->setCreator($this->frontendUser->getUid());
            $suggestionDocument = $this->documentManager->addSuggestion($editOrigDocument, $restore, $comment);

            if ($restore) {
                $suggestionDocument->setTransferStatus("RESTORE");
            }

            if ($suggestionDocument) {

                $notifier = $this->objectManager->get(Notifier::class);
                $notifier->sendAdminNewSuggestionNotification($suggestionDocument);

                return '{"success": "Suggestion created", "id": "' . $suggestionDocument->getUid() . '"}';
            } else {
                return '{"failed": "Suggestion not created"}';
            }
        }
        return '{"error": "Token failed"}';
    }


    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function initializeImportDoiWithoutSavingAction()
    {
        $this->checkMandatoryParameters(['doi', 'token']);
    }

    /**
     * @param string $doi
     * @param string $token
     */
    public function importDoiWithoutSavingAction(string $doi, $token) {
        if ($this->checkToken($token)) {
            $importer = $this->objectManager->get(CrossRefImporter::class);
            $importer->deactivateProcessNumberGeneration();

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

                    $this->logger->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, $throwable->getMessage());
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
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function initializeImportPubmedWithoutSavingAction()
    {
        $this->checkMandatoryParameters(['pmid', 'token']);
    }

    /**
     * @param string $pmid
     * @param string $token
     * @return string
     */
    public function importPubmedWithoutSavingAction($pmid, $token) {
        if ($this->checkToken($token)) {
            $importer = $this->objectManager->get(PubMedImporter::class);
            $importer->deactivateProcessNumberGeneration();

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

                    $this->logger->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, $throwable->getMessage());
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
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function initializeImportIsbnWithoutSavingAction()
    {
        $this->checkMandatoryParameters(['isbn', 'token']);
    }

    /**
     * @param string $isbn
     * @param string $token
     * @return string
     */
    public function importIsbnWithoutSavingAction($isbn, $token) {
        if ($this->checkToken($token)) {
            $importer = $this->objectManager->get(K10plusImporter::class);
            $importer->deactivateProcessNumberGeneration();

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

                    $this->logger->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, $throwable->getMessage());
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
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function initializeImportBibtexWithoutSavingAction()
    {
        $this->checkMandatoryParameters(['bibtex', 'token']);
        if ($this->request->hasArgument('force')) {
            $force = $this->request->getArgument('force');
            $this->request->setArgument('force', ($force === 'true' || $force == 1));
        }
    }

    /**
     * @param string $bibtex content of a bibtex file
     * @param string $token
     * @param bool $force
     * @return string
     */
    public function importBibtexWithoutSavingAction($bibtex, $token, $force = false) {

        if ($this->checkToken($token)) {
            $importer = $this->objectManager->get(BibTexFileImporter::class);
            $importer->deactivateProcessNumberGeneration();

            try {
                $externalMetadata = $importer->loadFile($bibtex, $this->settings['bibTexMandatoryFields'], true);
                $mandatoryErrors = $importer->getMandatoryErrors();
            } catch (\Throwable $throwable) {
                return '{"failed": "' . $throwable->getMessage() . '"}';
            }

            if ($externalMetadata && (!$mandatoryErrors || $force)) {
                // create document
                try {
                    $jsonDataElements = [];
                    foreach ($externalMetadata as $externalMetadataItem) {
                        /** @var Document $newDocument */
                        $newDocument = $importer->import($externalMetadataItem);
                        if ($newDocument) {
                            /** @var $client \EWW\Dpf\Domain\Model\Client */
                            $client = $this->clientRepository->findAllByPid($this->frontendUser->getPid())->current();
                            $mapper = new \EWW\Dpf\Services\Api\DocumentToJsonMapper();
                            $mapper->setMapping($client->getFisMapping());
                            $jsonDataElements[] = $mapper->getJson($newDocument);
                        } else {
                            return '{"failed": "Import failed"}';
                        }
                    }
                    return "[" . implode(", ", $jsonDataElements) . "]";
                } catch (\Throwable $throwable) {
                    $this->logger->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, $throwable->getMessage());
                    return '{"failed": "' . $throwable->getMessage() . '"}';
                }
            } else {
                if ($mandatoryErrors) {
                    $message = [];
                    $message['failed'] = "Missing mandatory fields.";

                    foreach ($mandatoryErrors as $mandatoryError) {
                        $message['mandatoryErrors'][] = [
                            'index'=> $mandatoryError['index'],
                            'title' => ($mandatoryError['title'] ? ' (' . $mandatoryError['title'] . ')' : ''),
                            'fields' => array_values($mandatoryError['fields'])
                        ];
                    }

                    return json_encode($message);

                } else {
                    return '{"failed": "Invalid BibTex-Data"}';
                }
            }
        } else {
            return '{"error": "Token failed"}';
        }

        return '{"error": "Unexpected error"}';
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function initializeImportRisWithoutSavingAction()
    {
        $this->checkMandatoryParameters(['ris', 'token']);
        if ($this->request->hasArgument('force')) {
            $force = $this->request->getArgument('force');
            $this->request->setArgument('force', ($force === 'true' || $force == 1));
        }
    }

    /**
     * @param string $ris
     * @param string $token
     * @param bool $force
     * @return string
     */
    public function importRisWithoutSavingAction($ris, $token, $force = false) {

        if ($this->checkToken($token)) {
            /** @var FileImporter $fileImporter */
            $importer = $this->objectManager->get(RisWosFileImporter::class);
            $importer->deactivateProcessNumberGeneration();

            try {
                $externalMetadata = $importer->loadFile($ris, $this->settings['riswosMandatoryFields'], true);
                $mandatoryErrors = $importer->getMandatoryErrors();
            } catch (\Throwable $throwable) {
                return '{"failed": "' . $throwable->getMessage() . '"}';
            }

            if ($externalMetadata && (!$mandatoryErrors || $force)) {
                // create document
                try {
                    $jsonDataElements = [];
                    foreach ($externalMetadata as $externalMetadataItem) {
                        /** @var Document $newDocument */
                        $newDocument = $importer->import($externalMetadataItem);
                        if ($newDocument) {
                            /** @var $client \EWW\Dpf\Domain\Model\Client */
                            $client = $this->clientRepository->findAllByPid($this->frontendUser->getPid())->current();

                            $mapper = new \EWW\Dpf\Services\Api\DocumentToJsonMapper();
                            $mapper->setMapping($client->getFisMapping());
                            $jsonDataElements[] = $mapper->getJson($newDocument);
                        } else {
                            return '{"failed": "Import failed"}';
                        }
                    }
                    return "[" . implode(", ", $jsonDataElements) . "]";

                } catch (\Throwable $throwable) {

                    $this->logger->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, $throwable->getMessage());
                    return '{"failed": "' . $throwable->getMessage() . '"}';
                }

            } else {
                if ($mandatoryErrors) {
                    $message = [];
                    $message['failed'] = "Missing mandatory fields";

                    foreach ($mandatoryErrors as $mandatoryError) {
                        $message['mandatoryErrors'][] = [
                            'index'=> $mandatoryError['index'],
                            'title' => ($mandatoryError['title'] ? ' (' . $mandatoryError['title'] . ')' : ''),
                            'fields' => array_values($mandatoryError['fields'])
                        ];
                    }

                    return json_encode($message);

                } else {
                    return '{"failed": "Invalid RIS-Data"}';
                }
            }
        } else {
            return '{"error": "Token failed"}';
        }
        return '{"error": "Unexpected error"}';
    }


    /**
     * @param $parameterNames array
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    protected function checkMandatoryParameters($parameterNames)
    {
        $missingArguments = [];
        foreach ($parameterNames as $parameterName) {
            if (!$this->request->hasArgument($parameterName)) {
                $missingArguments[] =  $parameterName;
            }
        }

        if ($missingArguments) {
            $this->throwStatus(
                400,
                null,
                '{"error": "Missing parameters: '.implode(", ", $missingArguments).'"}'
            );
        }

    }

    /**
     * Resolves and checks the current action method name
     *
     * @return string Method name of the current action
     */
    protected function resolveActionMethodName()
    {
        if ($this->request->hasArgument('action')) {
            $actionName = $this->request->getArgument('action');
        }

        if (empty($actionName)) {
            $this->throwStatus(
                400,
                null,
                '{"error": "No action has been specified"}'
            );

        }

        if (!in_array($actionName, $this->validActions)) {
            $this->throwStatus(
                400,
                null,
            '{"error": "An invalid action hes been called: '.$actionName.'"}'
            );
        }

        return $actionName . 'Action';
    }
}
