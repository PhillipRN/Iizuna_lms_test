<?php

namespace IizunaLMS\EBook\Models;

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Models\ModelBase;

class EbookSchoolModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'school_book';
    }

    /**
     * @param $schoolId
     * @return bool
     */
    function DeleteBySchoolId($schoolId): bool
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
DELETE FROM {$this->_tableName} 
WHERE school_id = CAST(:school_id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(":school_id", $schoolId, \PDO::PARAM_STR);

        return PDOHelper::ExecuteWithTry($sth);
    }
}