<?php
namespace EWW\Dpf\Services;

class PaginationBuilder
{
    private const WINDOW = 3;

    /**
     * @param int $totalHits  Total ES hits
     * @param int $size       Results per page
     * @param int $from       Current offset
     * @return array{
     *   totalPages: int,
     *   currentPage: int,
     *   hasPrev: bool,
     *   hasNext: bool,
     *   pages: int[],
     *   prevOffset: int,
     *   nextOffset: int,
     *   offsetForPage: array<int,int>
     * }
     */
    public static function build(int $totalHits, int $size, int $from): array
    {
        if ($totalHits === 0 || $size === 0) {
            return [
                'totalPages'   => 0,
                'currentPage'  => 1,
                'hasPrev'      => false,
                'hasNext'      => false,
                'pages'        => [],
                'prevOffset'   => 0,
                'nextOffset'   => 0,
                'offsetForPage'=> [],
            ];
        }

        $totalPages  = (int) ceil($totalHits / $size);
        $currentPage = (int) floor($from / $size) + 1;
        $currentPage = max(1, min($currentPage, $totalPages));

        $windowStart = max(1, $currentPage - self::WINDOW);
        $windowEnd   = min($totalPages, $currentPage + self::WINDOW);
        $pages       = range($windowStart, $windowEnd);

        $offsetForPage = [];
        foreach ($pages as $page) {
            $offsetForPage[$page] = ($page - 1) * $size;
        }

        return [
            'totalPages'   => $totalPages,
            'currentPage'  => $currentPage,
            'hasPrev'      => $currentPage > 1,
            'hasNext'      => $currentPage < $totalPages,
            'pages'        => $pages,
            'prevOffset'   => max(0, $from - $size),
            'nextOffset'   => $from + $size,
            'offsetForPage'=> $offsetForPage,
        ];
    }
}
