<?php

namespace IizunaLMS\Models;

class OnigiriQuizHighSchoolModel extends ModelBase
{
    function __construct() {
        $this->_tableName ='quiz_high_school';
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