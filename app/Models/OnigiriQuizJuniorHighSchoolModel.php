<?php

namespace IizunaLMS\Models;

class OnigiriQuizJuniorHighSchoolModel extends ModelBase
{
    function __construct() {
        $this->_tableName ='quiz_junior_high_school';
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