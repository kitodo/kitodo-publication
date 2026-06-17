<?php
namespace EWW\Dpf\Services\ElasticSearch;

class PublicQueryBuilder
{
    private const ALLOWED_SORTS = [
        'title_asc'  => ['titleSort' => ['order' => 'asc']],
        'title_desc' => ['titleSort' => ['order' => 'desc']],
        'dateIssued' => ['dateIssued' => ['order' => 'desc']],
        'year'       => ['year' => ['order' => 'desc']],
    ];

    /**
     * Maps user-facing field selector keys to ES fields. Some targets (abstract, tag,
     * corporation) have no backing field in the index yet and will simply match nothing
     * until they're wired up (planned follow-up).
     */
    private const SEARCHABLE_FIELDS = [
        'title'       => 'title',
        'author'      => 'persons',
        'abstract'    => 'abstract',
        'tag'         => 'tag',
        'corporation' => 'corporation',
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

        $filter     = ['bool' => ['must' => $this->buildFilters($criteria)]];
        $mustQueries = $this->buildTextQueries($criteria);

        if (!empty($mustQueries)) {
            $queryNode = ['bool' => ['must' => $mustQueries, 'filter' => $filter]];
        } else {
            $queryNode = ['bool' => ['filter' => $filter]];
        }

        $sort = ['_score'];
        if (!empty($criteria['sort']) && isset(self::ALLOWED_SORTS[$criteria['sort']])) {
            $sort = [self::ALLOWED_SORTS[$criteria['sort']]];
        }

        return [
            'index' => null,
            'body'  => [
                'track_total_hits' => true,
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

    private function buildFilters(array $criteria): array
    {
        $filters = [['term' => ['state' => 'NONE:ACTIVE']]];

        if (!empty($criteria['doctype'])) {
            $filters[] = ['term' => ['doctype' => $criteria['doctype']]];
        }
        if (!empty($criteria['year'])) {
            $filters[] = ['term' => ['year' => $criteria['year']]];
        }

        $range = $this->buildYearRange($criteria);
        if (!empty($range)) {
            $filters[] = ['range' => ['year' => $range]];
        }

        return $filters;
    }

    private function buildYearRange(array $criteria): array
    {
        $range = [];
        if (!empty($criteria['yearFrom'])) {
            $range['gte'] = $criteria['yearFrom'];
        }
        if (!empty($criteria['yearTo'])) {
            $range['lte'] = $criteria['yearTo'];
        }
        return $range;
    }

    private function buildTextQueries(array $criteria): array
    {
        $queries = [];

        if (!empty($criteria['q'])) {
            $queries[] = ['query_string' => ['query' => $this->escapeQuery($criteria['q'])]];
        }
        if (!empty($criteria['title'])) {
            $queries[] = ['query_string' => ['query' => $this->escapeQuery($criteria['title']), 'fields' => ['title']]];
        }
        if (!empty($criteria['author'])) {
            $queries[] = ['query_string' => ['query' => $this->escapeQuery($criteria['author']), 'fields' => ['persons']]];
        }

        foreach ($criteria['fieldQueries'] ?? [] as $fieldQuery) {
            $field = $fieldQuery['field'] ?? '';
            $value = $fieldQuery['value'] ?? '';
            if ($value === '' || !isset(self::SEARCHABLE_FIELDS[$field])) {
                continue;
            }
            $queries[] = [
                'query_string' => [
                    'query'  => $this->escapeQuery($value),
                    'fields' => [self::SEARCHABLE_FIELDS[$field]],
                ],
            ];
        }

        return $queries;
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
