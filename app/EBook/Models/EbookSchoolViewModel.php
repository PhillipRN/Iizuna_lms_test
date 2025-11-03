<?php

namespace IizunaLMS\EBook\Models;

use IizunaLMS\Models\ModelBase;

class EbookSchoolViewModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'school_book_view';
    }
}