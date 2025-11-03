<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class DaimonModel
{
    private $_tableName;

    function __construct($titleNo) {
        $this->_tableName = "TC" . $titleNo . "_TC04";
    }

    /**
     * @return array
     */
    function Gets($daimonNos)
    {
        $pdo = PDOHelper::GetPDO();

        $sqlColumn = array();

        for ($i=0; $i<count($daimonNos); ++$i)
        {
            $sqlColumn[] = ":daimonNo_" . $i;
        }

        $sqlPart = join(",", $sqlColumn);

        $sql = <<<SQL
SELECT 
  * 
FROM {$this->_tableName} 
WHERE DAIMONNO IN ({$sqlPart})
SQL;

        $sth = $pdo->prepare($sql);

        // 変数の数だけバインド
        for ($i=0; $i<count($daimonNos); ++$i)
        {
            $sth->bindValue(":daimonNo_" . $i, $daimonNos[$i]);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}