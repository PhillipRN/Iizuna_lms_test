<?php

namespace IizunaLMS\Models;

class OnigiriQuizCollegeStandardModel extends ModelBase
{
    function __construct() {
        $this->_tableName ='quiz_college_standard';
        $this->_pdoMode = self::PDO_MODE_ONIGIRI;
    }

    public function GetWords($stage)
    {
        return $this->GetsByKeyValues(
            ['stage'],
            [$stage],
            ['id', 'word', 'mean']);
    }
}