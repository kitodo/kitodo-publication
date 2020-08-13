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

    /**
     *
     */
    public function listAction() {
        var_dump("testList");
    }

    /**
     * @param Document $document
     */
    public function showAction(Document $document) {
        // define which properties are displayed
//        $this->view->setConfiguration([
//            'customVariable' => [
//                '_only' => [
//                    'key1',
//                    'key3',
//                ],
//            ],
//            'customVariable2' => [
//                '_exclude' => [
//                    'key1',
//                    'key3',
//                ],
//            ],
//        ]);
//
//        $this->view->setVariablesToRender(['customVariable']);
//
//        $this->view->assignMultiple([
//            'anotherVariable' => 'value',
//            'customVariable' => [
//                'key1' => 'value1',
//                'key2' => 'value2',
//                'key3' => [
//                    'key3.1' => 'value3.1',
//                    'key3.2' => 'value3.2',
//                ],
//            ],
//        ]);
        var_dump($document->getTitle());exit;
        $this->view->assign('document', $document);
    }

    public function createAction() {

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
