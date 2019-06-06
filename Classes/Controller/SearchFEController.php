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

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * SearchFEController
 */
class SearchFEController extends \EWW\Dpf\Controller\AbstractSearchController
{

    /**
     * action
     * @var string
     */
    protected $action;

    /**
     * query
     * @var array
     */
    protected $query;

    /**
     * current page
     * @var int
     */
    protected $currentPage;

    /**
     * type
     * @var string
     */
    protected $type = 'object';

    /**
     * result list
     * @var array
     */
    protected $resultList;

    /**
     * documenTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @inject
     */
    protected $documentTypeRepository;

    /**
     * __construct
     */
    public function __construct()
    {
        // get session data
        $session = $this->getSessionData('tx_dpf_frontendsearch');
        // get action
        $action = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('tx_dpf_frontendsearch')['action'];
        // get query
        $query = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('tx_dpf_frontendsearch')['query'];
        // get current page
        $currentPage = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('tx_dpf_frontendsearch')['@widget_0']['currentPage'];

        // set action
        if ($action == 'extendedSearch' ||
            ($action != 'search' && (!empty($session) && key($session) == 'extendedSearch'))) {
            $this->action = 'extendedSearch';
        } else {
            $this->action = 'search';
        }

        // set query
        if ((!empty($query))) {
            $this->query = $this->filterSafelyParameters($query);
            $this->currentPage = 1;
        } else {
            // restore query
            $this->query = (!empty($session[$this->action]['query'])) ? $session[$this->action]['query'] : array();
            // set current page
            if ((!empty($currentPage))) {
                $this->currentPage = MathUtility::forceIntegerInRange($currentPage, 1);
            } elseif (!empty($session[$this->action]['currentPage'])) {
                // restore current page
                $this->currentPage = MathUtility::forceIntegerInRange($session[$this->action]['currentPage'], 1);
            }
        }
    }

    /**
     * action search
     * @return void
     */
    public function searchAction()
    {
        try {
            if ($this->action == 'extendedSearch') {
                $this->forward('extendedSearch');
            }
            if (!empty($this->query['fulltext'])) {
                $query = $this->searchFulltext($this->query['fulltext']);
                $this->resultList = $this->getResults($query);
                $this->setSession();
                $this->viewAssign();
            }
        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');

            $this->addFlashMessage(
                $message,
                '',
                $severity,
                true
            );
        }
    }

    /**
     * action extendedSearch
     * @return void
     */
    public function extendedSearchAction()
    {
        try {
            if ($this->action == 'search') {
                $this->forward('search');
            }
            $this->docTypes();
            if (!empty(implode('', $this->query))) {
                $query = $this->extendedSearch($this->query);
                $this->resultList = $this->getResults($query);
                $this->setSession();
                $this->viewAssign();
            }
        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');

            $this->addFlashMessage(
                $message,
                '',
                $severity,
                true
            );
        }
    }

    /**
     * elastic searc
     * @param  array $query
     * @return array $results
     */
    private function getResults($query)
    {
        $size = $this->settings['list']['paginate']['itemsPerPage'];

        $query['body']['from'] = ($this->currentPage - 1) * $size;
        $query['body']['size'] = $size;

        $resultList = $this->getResultList($query, $this->type);

        return $resultList;
    }

    /**
     * action showSearchForm
     * dummy action for showSearchForm Action used in sidebar
     * @return void
     */
    public static function showSearchFormAction()
    {

    }

    /**
     * set session data
     * @return void
     */
    private function setSession()
    {
        $session[$this->action] = array (
                'query'       => $this->query,
                'currentPage' => $this->currentPage
            );
        $this->setSessionData('tx_dpf_frontendsearch', $session);
    }

    /**
     * create docTypes
     * @return void
     */
    private function docTypes()
    {
        // get document types
        $allTypes = $this->documentTypeRepository->findAll();
        // add empty field
        $docTypeArray[0] = ' ';
        foreach ($allTypes as $key => $value) {
            $docTypeArray[$value->getName()] = $value->getDisplayName();
        }
        asort($docTypeArray);
        $this->view->assign('docTypes', $docTypeArray);
    }

    /**
     * create view
     * @return void
     */
    private function viewAssign()
    {
        $this->view->assign('query',       $this->query);
        $this->view->assign('resultList',  $this->resultList);
        $this->view->assign('currentPage', $this->currentPage);
    }
}
