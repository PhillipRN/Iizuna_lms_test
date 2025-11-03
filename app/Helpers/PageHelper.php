<?php

namespace IizunaLMS\Helpers;

class PageHelper
{
    const PAGE_LIMIT = 10;

    public static function GetMaxPageNum($count, $limit=null)
    {
        if ($count <= 1) return 1;

        if (empty($limit)) $limit = self::PAGE_LIMIT;

        return (int)(floor(($count - 1) / $limit)) + 1;
    }

    /**
     * @param $count
     * @param $limit
     * @return int
     */
    public static function CalculateMaxPageNum($count, $limit): int
    {
        if ($count <= 1) return 1;

        return (int)(floor(($count - 1) / $limit)) + 1;
    }

    /**
     * @param $page
     * @param $limit
     * @return int
     */
    public static function CalculateOffset($page, $limit): int
    {
        return (int)(($page > 0) ? ($page - 1) * $limit : 0);
    }
}