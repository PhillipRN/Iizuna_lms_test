<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class OtherAnswerModel
{
    private $_tableName;

    function __construct($titleNo) {
        $this->_tableName = "TC" . $titleNo . "_other_answer";
    }

    /**
     * @param $shomonNos
     * @return array
     */
    function GetsByShomonNos($shomonNos)
    {
        $pdo = PDOHelper::GetPDO();

        $sqlColumn = array();

        for ($i=0; $i<count($shomonNos); ++$i)
        {
            $sqlColumn[] = ":syomon_no_" . $i;
        }

        $sqlPart = join(",", $sqlColumn);

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName}  
WHERE syomon_no IN ({$sqlPart})
SQL;

        $sth = $pdo->prepare($sql);

        // 変数の数だけバインド
        for ($i=0; $i<count($shomonNos); ++$i)
        {
            $sth->bindValue(":syomon_no_" . $i, $shomonNos[$i]);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}