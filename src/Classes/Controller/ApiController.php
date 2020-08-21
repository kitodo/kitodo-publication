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
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository = null;


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
     * @param string $document
     */
    public function createAction($document) {

    }

    public function addFisIdAction($id) {

    }

    public function addSuggestion() {
        // Wiederherstellungsvorschlag und Dateien

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

    /**
     * Resolves and checks the current action method name
     *
     * @return string Method name of the current action
     */
    protected function resolveActionMethodName()
    {
        switch ($this->request->getMethod()) {
            case 'HEAD':
            case 'GET':
                $actionName = ($this->request->hasArgument('document')) ? 'show' : 'list';
                break;
            case 'POST':
            case 'PUT':
            case 'DELETE':
                $this->throwStatus(400, null, 'Bad Request.');
            default:
                $this->throwStatus(400, null, 'Bad Request.');
        }
        return $actionName . 'Action';
    }
}
