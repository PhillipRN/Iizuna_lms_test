<?php

namespace IizunaLMS\Models;

class OnigiriLearningRangeMaxStageModel extends ModelBase
{
    function __construct() {
        $this->_tableName ='learning_range_max_stage';
        $this->_pdoMode = self::PDO_MODE_ONIGIRI;
    }
}