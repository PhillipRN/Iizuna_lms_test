<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class SyubetuModel
{
    private $_tableName;

    function __construct($titleNo) {
        $this->_tableName = "TC" . $titleNo . "_TC02";
    }

    /**
     * @return array
     */
    function Gets()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  * 
FROM {$this->_tableName} 
ORDER BY SYUBETUNO ASC 
SQL;

        $sth = $pdo->prepare($sql);
        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    function GetRatedRecords()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  * 
FROM {$this->_tableName} 
WHERE RATE > 0
SQL;

        $sth = $pdo->prepare($sql);
        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}