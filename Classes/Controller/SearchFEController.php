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
     * documenTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @inject
     */
    protected $documentTypeRepository;

    /**
     * action search
     *
     * @return void
     */
    public function searchAction()
    {
        // get searchString
        $args = $this->getParametersSafely('search');

        // get extended search parameters
        $extSearch = $this->getParametersSafely('extSearch');

        $searchString = $args['query'];

        // get only as much records as will be shown
        $searchSize = $this->settings['list']['paginate']['itemsPerPage'];

        // default ist first page
        $currentPage = 1;

        if (!empty($searchString)) {

            $this->setSessionData('tx_dpf_searchString', $searchString);

            $searchFrom = 0;

        } else {

            // get last search
            $searchString = $this->getSessionData('tx_dpf_searchString');

            $pagination = $this->getParametersSafely('@widget_0');

            $currentPage = MathUtility::forceIntegerInRange(($pagination['currentPage']), 1);

            $searchFrom = ($currentPage - 1) * $searchSize;

        }

        // prepare search query
        if ($extSearch['extSearch']) {

            // extended search
            $query = $this->extendedSearch();

        } else {

            $query = $this->searchFulltext($searchString);

        }

        $extra = $query['extra'];
        unset($query['extra']);

        // execute search query
        if ($query) {

            $query['body']['from'] = $searchFrom;
            $query['body']['size'] = $searchSize;

            // set type local vs object
            $type = 'object';

            $results = $this->getResultList($query, $type);

        }

        // get ext search values
        $extSearch = $this->getParametersSafely('extSearch');

        // get document types
        $allTypes = $this->documentTypeRepository->findAll();

        // add empty field
        $docTypeArray[0] = ' ';

        foreach ($allTypes as $key => $value) {

            $docTypeArray[$value->getName()] = $value->getDisplayName();

        }

        if ($this->getParametersSafely('action') != 'extendedSearch' && empty($extSearch)) {

            $flag = true;

        } else {

            $flag = false;

        }

        // add empty select value
        $allTypes = $allTypes->toArray();
        $tempArray[0] = ' ';

        $allTypes = array_merge($tempArray, $allTypes);

        $this->view->assign('extendedSearch', $flag);
        $this->view->assign('extendedSearchValues', $extSearch);

        $this->view->assign('docTypes', $allTypes);
        $this->view->assign('results', $results);
        $this->view->assign('searchString', $searchString);
        $this->view->assign('currentPage', $currentPage);

    }

    public function extendedSearchAction() {

//        $this->view->assign('extendedSearch', true);

        $this->forward('search', NULL, NULL, array('extendedSearch' => true));
    }

    /**
     * action showSearchForm
     *
     * dummy action for showSearchForm Action used in sidebar
     *
     * @return void
     */
    public function showSearchFormAction()
    {

    }
}
