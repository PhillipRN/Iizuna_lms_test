<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class TCBookModel
{
    private $_tableName = "TCBook_TC00";
    private $_tableNameJoin = "book_range";

    /**
     * @param $flag
     * @return array
     */
    function GetBookListByFlag($flag)
    {
        $pdo = PDOHelper::GetPDO();

        $where = "WHERE VHFLG = :flag ";
        $sql = $this->GetBookSqlByWhere($where);
        $sth = $pdo->prepare($sql);
        $sth->bindValue(':flag', $flag);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $where
     * @return string
     */
    private function GetBookSqlByWhere($where)
    {
        return <<<SQL
SELECT 
  {$this->_tableName}.TITLENO,
  NAME,
  PRINTNAME,
  SORTNO,
  PUBLISH,
  MIDASILABELFLG,
  page_min,
  page_max,
  midasi_no_min,
  midasi_no_max,
  question_no_flg,
  chapter_flg,
  midasi_no_flg,
  midasi_flg,
  level_flg,
  frequency_flg
FROM {$this->_tableName} 
LEFT JOIN {$this->_tableNameJoin} 
  ON {$this->_tableName}.TITLENO = {$this->_tableNameJoin}.title_no 
{$where}
ORDER BY SORTNO ASC
SQL;
    }
}