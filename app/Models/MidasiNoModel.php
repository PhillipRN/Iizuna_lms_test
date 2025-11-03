<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class MidasiNoModel
{
    private $_tableName;

    function __construct($titleNo) {
        $this->_tableName = "TC" . $titleNo . "_TC08";
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
ORDER BY MIDASINO ASC 
SQL;

        $sth = $pdo->prepare($sql);
        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}