<?php

namespace IizunaLMS\Schools;

use IizunaLMS\Helpers\PageHelper;
use IizunaLMS\Models\SchoolViewModel;

class SchoolLoader
{
    /**
     * @param $keyWord
     * @param $page
     * @return array
     */
    public static function GetSchool($keyWord, $page=null): array
    {
        $keyWords = (!empty($keyWord)) ? explode(' ', trim($keyWord)) : [];

        $SchoolViewModel = new SchoolViewModel();

        $records = $SchoolViewModel->GetSchools($keyWords, $page);
        $recordCount = $SchoolViewModel->GetRecordCount($keyWords);

        return [
            'records' => $records,
            'maxPageNum' => PageHelper::GetMaxPageNum($recordCount)
        ];
    }
}