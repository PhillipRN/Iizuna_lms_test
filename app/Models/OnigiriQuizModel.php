<?php

namespace IizunaLMS\Models;

class OnigiriQuizModel extends ModelBase
{
    function __construct() {
        $this->_tableName ='quiz';
        $this->_pdoMode = self::PDO_MODE_ONIGIRI;
    }
}