<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class FrequencyModel
{
    private $_tableName;

    function __construct($titleNo) {
        $this->_tableName = "TC" . $titleNo . "_TC07";
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
ORDER BY FREQUENCYNO ASC
SQL;

        $sth = $pdo->prepare($sql);
        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}