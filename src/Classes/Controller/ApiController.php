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
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

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
     * documentManager
     *
     * @var \EWW\Dpf\Services\Document\DocumentManager
     * @inject
     */
    protected $documentManager = null;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @inject
     */
    protected $persistenceManager;


    protected $jsonMapping = <<<EOD
{
  "title": {
    "_mapping": "/mods:mods/mods:titleInfo/mods:title"
  },
  "persons": [             
        {
          "_mapping": "/mods:mods/mods:name[@type=\"personal\"]",                 
          "given": {
            "_mapping": "mods:namePart[@type=\"given\"]"           
          },
          "family": {
            "_mapping": "mods:namePart[@type=\"family\"]"           
          }
        }        
  ],
  "institution": {
     "_mapping": "/mods:mods/mods:institution",    
     "name": {
       "_mapping": "mods:institutionName"  
     },
     "title": {
       "_mapping": "mods:institutionTitle",
       "titleTest": {
          "_mapping": "mods:institutionTitleTest"
       }  
     }               
  },
   "documentType": {
        "_mapping": "/slub:info/slub:documentType"
  }    
}
EOD;

    /**
     *
     */
    public function listAction() {
        var_dump("testList");
    }

    /**
     * @param string $document
     */
    public function showAction($document) {

        $doc = $this->documentRepository->findByIdentifier($document);

        if ($doc) {
            $mapper = new \EWW\Dpf\Services\Api\DocumentToJsonMapper();
            $mapper->setMapping($this->jsonMapping);
            $jsonData = $mapper->getJson($doc);
            return $jsonData;
        }

        return '{"error": "No data found"}';
    }

    /**
     *
     */
    public function createAction() {

        if ($this->request->hasArgument('document')) {
            $args = $this->request->getArguments();
            $jsonData = $args['document'];
        }

        if (empty($jsonData)) {
            return '{"error": "invalid data"}';
        }

        $mapper = $this->objectManager->get(\EWW\Dpf\Services\Api\JsonToDocumentMapper::class);

        /** @var Document $document */
        $document = $mapper->getDocument($jsonData);

        //$document->setCreator($this->security->getUser()->getUid());

        // xml data fields are limited to 64 KB
        if (strlen($document->getXmlData()) >= 64 * 1024 || strlen($document->getSlubInfoData() >= 64 * 1024)) {
            return '{"error": "Maximum document size exceeded"}';
        }

        $this->documentRepository->add($document);
        $this->persistenceManager->persistAll();

        return '{"success": "Document created", "id": ".'.$document->getDocumentIdentifier().'."}';

    }

    public function addFisIdAction($document, $id) {

    }

    /**
     * @param Document $document
     * @return string
     */
    public function suggestionAction(Document $document) {
        // Wiederherstellungsvorschlag und Dateien

        if ($this->request->hasArgument('json')) {
            $args = $this->request->getArguments();
            $jsonData = $args['json'];
        }

        if (empty($jsonData)) {
            return '{"error": "invalid data"}';
        }

        $mapper = $this->objectManager->get(\EWW\Dpf\Services\Api\JsonToDocumentMapper::class);

        /** @var Document $editOrigDocument */
        $editOrigDocument = $mapper->editDocument($document, $jsonData);

        $suggestionDocument = $this->documentManager->addSuggestion($editOrigDocument);

        if ($suggestionDocument) {
            return '{"success": "Suggestion created", "id": ".'.$suggestionDocument->getDocumentIdentifier().'."}';
        } else {
            return '{"failed": "Suggestion not created"}';
        }
    }

    public function importDoiWithoutSavingAction($doi) {

    }

    public function importBibtexWithoutSavingAction() {

    }

    public function importRisWithoutSavingAction() {

    }

    public function bulkImportWithoutSavingAction() {
        // file with

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
