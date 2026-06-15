<?php
namespace EWW\Dpf\Services\ElasticSearch;

class PublicQueryBuilder
{
    private const ALLOWED_SORTS = [
        'title'     => ['title.keyword' => ['order' => 'asc']],
        'dateIssued'=> ['dateIssued' => ['order' => 'desc']],
        'year'      => ['year' => ['order' => 'desc']],
    ];

    private const MAX_SIZE = 100;

    /**
     * Builds a public-safe ES query. State filter NONE:ACTIVE is non-overridable.
     *
     * @param array  $criteria  Keys: q, doctype, year, yearFrom, yearTo, sort
     * @param int    $size      Results per page (clamped to MAX_SIZE)
     * @param int    $from      Offset (clamped to ≥0)
     * @return array            ES query params array
     */
    public function buildQuery(array $criteria, int $size = 10, int $from = 0): array
    {
        $size = min(max(1, $size), self::MAX_SIZE);
        $from = max(0, $from);

        $mustFilters = [
            ['term' => ['state' => 'NONE:ACTIVE']],
        ];

        if (!empty($criteria['doctype'])) {
            $mustFilters[] = ['term' => ['doctype' => $criteria['doctype']]];
        }

        if (!empty($criteria['year'])) {
            $mustFilters[] = ['term' => ['year' => $criteria['year']]];
        }

        if (!empty($criteria['yearFrom']) || !empty($criteria['yearTo'])) {
            $range = [];
            if (!empty($criteria['yearFrom'])) {
                $range['gte'] = $criteria['yearFrom'];
            }
            if (!empty($criteria['yearTo'])) {
                $range['lte'] = $criteria['yearTo'];
            }
            $mustFilters[] = ['range' => ['year' => $range]];
        }

        $filter = ['bool' => ['must' => $mustFilters]];

        if (!empty($criteria['q'])) {
            $escaped = $this->escapeQuery($criteria['q']);
            $queryNode = [
                'bool' => [
                    'must' => [
                        'query_string' => ['query' => $escaped],
                    ],
                    'filter' => $filter,
                ],
            ];
        } else {
            $queryNode = [
                'bool' => [
                    'filter' => $filter,
                ],
            ];
        }

        $sort = ['_score'];
        if (!empty($criteria['sort']) && isset(self::ALLOWED_SORTS[$criteria['sort']])) {
            $sort = [self::ALLOWED_SORTS[$criteria['sort']]];
        }

        return [
            'index' => null,
            'body'  => [
                'from'  => $from,
                'size'  => $size,
                'sort'  => $sort,
                'query' => $queryNode,
                'aggs'  => [
                    'doctype'   => ['terms' => ['field' => 'doctype', 'size' => 50]],
                    'year'      => ['terms' => ['field' => 'year', 'size' => 50, 'order' => ['_key' => 'desc']]],
                    'openAccess'=> ['terms' => ['field' => 'openAccess', 'size' => 10]],
                    'hasFiles'  => ['terms' => ['field' => 'hasFiles', 'size' => 10]],
                ],
            ],
        ];
    }

    private function escapeQuery(string $string): string
    {
        $luceneReservedCharacters = preg_quote('+-&|!(){}[]^~?:\\');
        $string = preg_replace_callback(
            '/([' . $luceneReservedCharacters . '])/',
            function ($matches) {
                return '\\' . $matches[0];
            },
            $string
        );
        return str_replace('/', '\/', $string);
    }
}
