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

use EWW\Dpf\Services\ElasticSearch\PublicElasticSearch;
use EWW\Dpf\Services\ElasticSearch\PublicQueryBuilder;
use EWW\Dpf\Services\PaginationBuilder;

class SearchFEController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentTypeRepository = null;

    private const PAGE_SIZE = 10;

    /**
     * @param int $from Pagination offset
     */
    public function searchAction(int $from = 0): void
    {
        $criteria = $this->collectCriteria();
        $this->renderResults($criteria, $from);
    }

    /**
     * @param int $from Pagination offset
     */
    public function extendedSearchAction(int $from = 0): void
    {
        $criteria = $this->collectCriteria();
        $this->view->assign('extended', true);
        $this->renderResults($criteria, $from);
    }

    public function showSearchFormAction(): void
    {
        $this->view->assign('docTypes', $this->getDocTypes());
    }

    // ── private ──────────────────────────────────────────────────────────────

    private function collectCriteria(): array
    {
        $criteria = [];

        // slub_web_qucosa form submits query[fulltext], query[doctype], query[from], query[till]
        if ($this->request->hasArgument('query')) {
            $queryArg = $this->request->getArgument('query');
            if (is_array($queryArg)) {
                $fulltext = trim($queryArg['fulltext'] ?? $queryArg['search'] ?? '');
                if ($fulltext !== '') {
                    $criteria['q'] = $fulltext;
                }
                if (!empty($queryArg['doctype'])) {
                    $criteria['doctype'] = (string) $queryArg['doctype'];
                }
                if (!empty($queryArg['from'])) {
                    $criteria['yearFrom'] = (string) $queryArg['from'];
                }
                if (!empty($queryArg['till'])) {
                    $criteria['yearTo'] = (string) $queryArg['till'];
                }
            }
        }

        // Flat args from direct URL params or our own simple form
        foreach (['q', 'doctype', 'year', 'yearFrom', 'yearTo', 'sort'] as $key) {
            if (!isset($criteria[$key]) && $this->request->hasArgument($key)) {
                $criteria[$key] = (string) $this->request->getArgument($key);
            }
        }

        return $criteria;
    }

    private function renderResults(array $criteria, int $from): void
    {
        $queryBuilder = new PublicQueryBuilder();
        $esQuery = $queryBuilder->buildQuery($criteria, self::PAGE_SIZE, $from);
        $esQuery['index'] = null;

        $es = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(PublicElasticSearch::class);
        $results = $es->search($esQuery);

        $totalHits = (int) ($results['hits']['total']['value'] ?? $results['hits']['total'] ?? 0);
        $documents = $results['hits']['hits'] ?? [];
        $pagination = PaginationBuilder::build($totalHits, self::PAGE_SIZE, $from);
        $aggregations = $results['aggregations'] ?? [];

        // Bridge vars for slub_web_qucosa Search.html template
        $legacyQuery = [
            'fulltext'    => $criteria['q'] ?? '',
            'doctype'     => $criteria['doctype'] ?? '',
            'title'       => '',
            'author'      => '',
            'abstract'    => '',
            'tag'         => '',
            'corporation' => '',
            'from'        => $criteria['yearFrom'] ?? '',
            'till'        => $criteria['yearTo'] ?? '',
        ];

        $this->view->assignMultiple([
            'criteria'        => $criteria,
            'documents'       => $documents,
            'documentCount'   => $totalHits,
            'pagination'      => $pagination,
            'aggregations'    => $aggregations,
            'docTypes'        => $this->getDocTypes(),
            // slub_web_qucosa template expects these names
            'resultList'      => ['total' => $totalHits, 'hits' => $documents],
            'paginatedResults' => $documents,
            'currentPage'     => $pagination['currentPage'],
            'query'           => $legacyQuery,
        ]);
    }

    private function getDocTypes(): array
    {
        $docTypes = [];
        foreach ($this->documentTypeRepository->findAllSorted() as $docType) {
            $docTypes[$docType->getName()] = $docType->getDisplayName();
        }
        return $docTypes;
    }
}
