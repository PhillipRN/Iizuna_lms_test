<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class ChapterModel
{
    private $_tableName;

    function __construct($titleNo) {
        $this->_tableName = "TC" . $titleNo . "_TC03";
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
ORDER BY CHAPNO ASC, SECNO ASC 
SQL;

        $sth = $pdo->prepare($sql);
        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}