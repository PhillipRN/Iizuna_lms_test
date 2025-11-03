<?php

namespace IizunaLMS\Models;

class OnigiriQuizCollegeEliteModel extends ModelBase
{
    function __construct() {
        $this->_tableName ='quiz_college_elite';
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