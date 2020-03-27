<?php
namespace EWW\Dpf\Services\ElasticSearch;

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

use Elasticsearch\ClientBuilder;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Security\Security;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class QueryBuilder
{
    /**
     *
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     * @inject
     */
    protected $configurationManager;


    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @inject
     */
    protected $documentTypeRepository = null;

    /**
     * security
     *
     * @var \EWW\Dpf\Security\Security
     * @inject
     */
    protected $security = null;


    /**
     * Get typoscript settings
     *
     * @return mixed
     */
    public function getSettings()
    {
        $frameworkConfiguration = $this->configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );
        return $frameworkConfiguration['settings'];
    }


    /**
     * Builds the document list query.
     *
     * @param int $itemsPerPage
     * @param array $workspaceFilter
     * @param int $from
     * @param array $bookmarkIdentifiers
     * @param array $filters
     * @param array $excludeFilters
     * @param string $sortField
     * @param string $sortOrder
     * @param string $queryString
     *
     * @return array
     */
    public function buildQuery(
        $itemsPerPage, $workspaceFilter, $from = 0, $bookmarkIdentifiers = [], $filters = [],
        $excludeFilters = [], $sortField = null, $sortOrder = null, $queryString = null
    )
    {
        // The base filter.
        $queryFilter = [
            'bool' => [
                'must' => [
                    [
                        'bool' => [
                            'should' => [
                                0 => $workspaceFilter
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if (!($excludeFilters && array_key_exists('bookmarks', $excludeFilters))) {
            // Add user document bookmarks.

            if ($bookmarkIdentifiers && is_array($bookmarkIdentifiers)) {
                $queryFilter['bool']['must'][0]['bool']['should'][] = [
                    'terms' => [
                        '_id' => array_values(array_filter($bookmarkIdentifiers))
                    ]
                ];
            }
        } else {
            // Show only user document bookmarks.
            $queryFilter['bool']['must'][0] = [
                'terms' => [
                    '_id' => $bookmarkIdentifiers
                ]
            ];
        }

        $filterPart = $this->buildFilterQueryPart($filters, $excludeFilters);

        if ($filterPart) {
            $queryFilter['bool']['must'][] = $filterPart;
        }

        if (!is_null($queryString)) {
            $query = [
                'bool' => [
                    'must' => [
                        'query_string' => [
                            'query' => $queryString
                        ]
                    ],
                    'filter' => $queryFilter
                ]
            ];
        } else {
            $query = [
                'bool' => [
                    'must' => [
                        'match_all' => (object)[]
                    ],
                    'filter' => $queryFilter
                ]
            ];

        }


        // Put together the complete query.
        $fullQuery = [
            'body' => [
                'size' => $itemsPerPage,
                'from' => $from,
                'query' => $query,
                'sort' => $this->buildSortQueryPart($sortField, $sortOrder),
                'aggs' => [
                    'simpleState' => [
                        'terms' => [
                            'field' => 'simpleState'
                        ]
                    ],
                    'year' => [
                        'terms' => [
                            'field' => 'year'
                        ]
                    ],
                    'doctype' => [
                        'terms' => [
                            'field' => 'doctype'
                        ]
                    ],
                    'hasFiles' => [
                        'terms' => [
                            'field' => 'hasFiles'
                        ]
                    ],
                    'universityCollection' => [
                        'terms' => [
                            'script' => [
                                'lang' => 'painless',
                                'source' =>
                                    "for (int i = 0; i < doc['collections'].length; ++i) {".
                                    "    if(doc['collections'][i] =='".$this->getSettings()['universityCollection']."') {".
                                    "        return 'true';".
                                    "    }".
                                    "}".
                                    "return 'false';"
                            ]
                        ]
                    ],
                    'authorAndPublisher' => [
                        'terms' => [
                            'field' => 'authorAndPublisher'
                        ]
                    ],
                    'creatorRole' => [
                        'terms' => [
                            'script' => [
                                'lang' => 'painless',
                                'source' =>
                                    "if (".
                                    "    doc['creator'].size() > 0 &&".
                                    "    doc['creator'].value == '".$this->security->getUser()->getUid()."') {".
                                    "    return 'self';".
                                    "}".
                                    "if (".
                                    "    doc['creatorRole'].size() > 0 &&".
                                    "    doc['creatorRole'].value == '".Security::ROLE_LIBRARIAN."'".
                                    ") {".
                                    "    return 'librarian';".
                                    "}".
                                    "if (".
                                    "    doc['creatorRole'].size() > 0 &&".
                                    "    doc['creatorRole'].value == '".Security::ROLE_RESEARCHER."'".
                                    ") {".
                                    "    return 'user';".
                                    "}".
                                    "return 'unknown';"
                            ]
                        ]
                    ]

                ]
            ]
        ];


        //echo "<pre>"; print_r($fullQuery); echo "</pre>"; die();

        return $fullQuery;

    }

    /**
     * Composes the filter part based on the given filters.
     *
     * @param array $filters
     * @param array $excludeFilters
     * @return array
     */
    protected function buildFilterQueryPart($filters, $excludeFilters = []) {

        $queryFilter = [];

        // Build the column filter part.
        if ($filters && is_array($filters)) {

            $validKeys = [
                'simpleState', 'authorAndPublisher', 'doctype', 'hasFiles', 'year', 'universityCollection', 'creatorRole'
            ];

            foreach ($filters as $key => $filterValues) {
                $queryFilterPart = [];
                if (in_array($key, $validKeys, true)) {
                    if ($key == 'universityCollection') {
                        if ($filterValues && is_array($filterValues)) {
                            if (in_array("true", $filterValues)) {
                                $filterValue = $this->getSettings()['universityCollection'];
                                $queryFilterPart['bool']['should'][] = [
                                    'term' => [
                                        'collections' => $filterValue
                                    ]
                                ];
                            } else {
                                $filterValue = $this->getSettings()['universityCollection'];
                                $queryFilterPart['bool']['should'][] = [
                                    'bool' => [
                                        'must_not' => [
                                            'term' => [
                                                'collections' => $filterValue
                                            ]
                                        ]
                                    ]
                                ];
                            }
                            $queryFilter['bool']['must'][] = $queryFilterPart;
                        }
                    } elseif ($key == 'creatorRole') {
                        $queryFilterPart = [];
                        if ($filterValues && is_array($filterValues)) {
                            if (in_array("librarian", $filterValues)) {
                                $creatorRolePart['bool']['must'] = [
                                    [
                                        'term' => [
                                            'creatorRole' => Security::ROLE_LIBRARIAN
                                        ]
                                    ],
                                    [
                                        'bool' => [
                                            'must_not' => [
                                                'term' => [
                                                    'creator' => $this->security->getUser()->getUid()
                                                ]
                                            ]
                                        ]
                                    ]
                                ];
                                $queryFilterPart['bool']['should'][] = $creatorRolePart;
                            } elseif (in_array("user", $filterValues)) {
                                $creatorRolePart['bool']['must'] = [
                                    [
                                        'term' => [
                                            'creatorRole' => Security::ROLE_RESEARCHER
                                        ]
                                    ],
                                    [
                                        'bool' => [
                                            'must_not' => [
                                                'term' => [
                                                    'creator' => $this->security->getUser()->getUid()
                                                ]
                                            ]
                                        ]
                                    ]
                                ];
                                $queryFilterPart['bool']['should'][] = $creatorRolePart;
                            } elseif (in_array("self", $filterValues)) {
                                $creatorRolePart['bool']['must'] = [
                                    [
                                        'term' => [
                                            'creator' =>  $this->security->getUser()->getUid()
                                        ]
                                    ]
                                ];
                                $queryFilterPart['bool']['should'][] = $creatorRolePart;
                            } else {
                                $creatorRolePart['bool']['must'] = [
                                    [
                                        'bool' => [
                                            'must_not' => [
                                                'term' => [
                                                    'creator' => $this->security->getUser()->getUid()
                                                ]
                                            ]
                                        ]
                                    ],
                                    [
                                        'bool' => [
                                            'must_not' => [
                                                'term' => [
                                                    'creatorRole' => Security::ROLE_LIBRARIAN
                                                ]
                                            ]
                                        ]
                                    ],
                                    [
                                        'bool' => [
                                            'must_not' => [
                                                'term' => [
                                                    'creatorRole' => Security::ROLE_RESEARCHER
                                                ]
                                            ]
                                        ]
                                    ]
                                ];
                                $queryFilterPart['bool']['should'][] = $creatorRolePart;
                            }

                            if ($queryFilterPart) {
                                $queryFilter['bool']['must'][] = $queryFilterPart;
                            }
                        }
                    } else {
                        if ($filterValues && is_array($filterValues)) {
                            foreach ($filterValues as $filterValue) {
                                $queryFilterPart['bool']['should'][] = [
                                    'term' => [
                                        $key => $filterValue
                                    ]
                                ];
                            }
                            $queryFilter['bool']['must'][] = $queryFilterPart;
                        }
                    }
                }
            }
        }

        if ($excludeFilters && array_key_exists('simpleState', $excludeFilters)) {
            if ($excludeFilters['simpleState']) {
                foreach ($excludeFilters['simpleState'] as $simpleStateExclude) {
                    $queryFilter['bool']['must'][] = [
                        'bool' => [
                            'must_not' => [
                                'bool' => [
                                    'must' => [
                                        [
                                            'term' => [
                                                'simpleState' => $simpleStateExclude
                                            ]
                                        ],
                                        [
                                            'term' => [
                                                'creator' => $this->security->getUser()->getUid()
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];
                }
            }
        }

        return $queryFilter;
    }


    /**
     * Composes the sort query part based on the given sort field and order.
     *
     * @param string $sortField
     * @param string $sortOrder
     * @return array
     */
    protected function buildSortQueryPart($sortField, $sortOrder) {

        $sortField = ($sortField)? $sortField : 'title';
        $sortOrder = ($sortOrder)? $sortOrder : 'asc';

        // Build the sorting part.
        $script = "";
        if ($sortField == "simpleState") {
            $script = $this->getSortScriptState();
        } elseif ($sortField == "universityCollection") {
            $script = $this->getSortScriptUniversityCollection($this->getSettings()['universityCollection']);
        } elseif ($sortField == "hasFiles") {
            $script = $this->getSortScriptHasFiles();
        } elseif ($sortField == "creatorRole") {
            $script = $this->getSortScriptCreatorRole($this->security->getUser()->getUid());
        }

        if ($script) {
            $sort = [
                "_script" => [
                    "type" => "string",
                    "order" => $sortOrder,
                    "script" => [
                        "lang" => "painless",
                        "source" => $script
                    ]
                ],
                "title.keyword" => [
                    "order" => "asc"
                ]
            ];
        } else {
            if ($sortField == 'title') {
                $sortField.= ".keyword";
            }

            $sort = [
                $sortField => [
                    'order' => $sortOrder
                ]
            ];
        }

        return $sort;
    }


    protected function getSortScriptUniversityCollection($collection)
    {
        $script  = "for (int i = 0; i < doc['collections'].length; ++i) {";
        $script .= "    if (doc['collections'][i] == '".$collection."') {";
        $script .= "        return '1';";
        $script .= "    }";
        $script .= "}";
        $script .= "return '2'";

        return $script;
    }

    protected function getSortScriptHasFiles()
    {
        $script = "if (doc['hasFiles'].value == 'true') {";
        $script .= "    return '1';";
        $script .= "}";
        $script .= "return '2'";

        return $script;
    }

    protected function getSortScriptCreatorRole($feUserUid)
    {
        $script = "if (doc['creator'].value == '".$feUserUid."') {";
        $script .= "    return '1';";
        $script .= "}";
        $script .= "if (doc['creatorRole'].value == '".Security::ROLE_LIBRARIAN."') {";
        $script .= "return '2';";
        $script .= "}";
        $script .= "if (doc['creatorRole'].value == '".Security::ROLE_RESEARCHER."') {";
        $script .= "    return '3';";
        $script .= "}";
        $script .= "return '4';";

        return $script;
    }


    protected function getSortScriptState()
    {
        $sortStates = [];
        foreach (DocumentWorkflow::PLACES as $state) {
            if (array_key_exists($state, DocumentWorkflow::STATE_TO_SIMPLESTATE_MAPPING)) {
                $simpleState = DocumentWorkflow::STATE_TO_SIMPLESTATE_MAPPING[$state];
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:manager.documentList.state.'.$simpleState;
                $stateName = LocalizationUtility::translate($key, 'dpf');
                $sortStates[] = "if (doc['state'].value == '".$state."') return '".$stateName."';";
            }
        }

        $sortStates = implode(" ", $sortStates);

        return $sortStates." return '';";
    }


    protected function getSortScriptDoctype()
    {
        $sortDoctypes = [];
        foreach ($this->documentTypeRepository->findAll() as $documentType) {
            if ($documentType->getName() && $documentType->getDisplayname()) {
                $sortDoctypes[] = "if (doc['doctype'].value == '".$documentType->getName()."')"
                    ." return '".$documentType->getDisplayname()."';";
            }
        }

        $sortDoctypes = implode(" ", $sortDoctypes);

        return $sortDoctypes." return '';";
    }

}
