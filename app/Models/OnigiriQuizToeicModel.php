<?php

namespace IizunaLMS\Models;

class OnigiriQuizToeicModel extends ModelBase
{
    function __construct() {
        $this->_tableName ='quiz_toeic';
        $this->_pdoMode = self::PDO_MODE_ONIGIRI;
    }

    public function GetWords($learningRangeLevel, $stage)
    {
        return $this->GetsByKeyValues(
            ['learning_range_level', 'stage'],
            [$learningRangeLevel, $stage],
            ['id', 'word', 'mean']);
    }
}