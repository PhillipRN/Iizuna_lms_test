<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class BookModel
{
    private $_tableName = "book";
    private $_tableNameJoin = "book_range";

    /**
     * @param $titleNo
     * @return mixed
     */
    function GetByTitleNo($titleNo)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
WHERE title_no = :title_no 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':title_no', $titleNo);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $titleNos
     * @return array
     */
    function GetBookListByTitleNos($titleNos)
    {
        $pdo = PDOHelper::GetPDO();

        $sqlColumn = array();

        for ($i=0; $i<count($titleNos); ++$i)
        {
            $sqlColumn[] = ":title_no_" . $i;
        }

        $sqlPart = join(",", $sqlColumn);

        $where = "WHERE {$this->_tableName}.title_no IN ({$sqlPart})";
        $sql = $this->GetBookSqlByWhere($where);
        $sth = $pdo->prepare($sql);

        // 変数の数だけバインド
        for ($i=0; $i<count($titleNos); ++$i)
        {
            $sth->bindValue(":title_no_" . $i, $titleNos[$i]);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $titleNo
     * @return mixed
     */
    function GetBook($titleNo)
    {
        $pdo = PDOHelper::GetPDO();

        $where = "WHERE {$this->_tableName}.title_no = :titleNo ";
        $sql = $this->GetBookSqlByWhere($where);
        $sth = $pdo->prepare($sql);
        $sth->bindValue(':titleNo', $titleNo);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $where
     * @return string
     */
    private function GetBookSqlByWhere($where)
    {
        return <<<SQL
SELECT 
  {$this->_tableName}.title_no,
  {$this->_tableName}.name,
  type,
  sort,
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
  ON {$this->_tableName}.title_no = {$this->_tableNameJoin}.title_no 
{$where}
ORDER BY sort ASC
SQL;
    }

    /**
     * @param $type
     * @return array
     */
    function GetsByType($type)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT title_no, name
FROM {$this->_tableName}
WHERE
  type = :type 
ORDER BY sort ASC
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':type', $type);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}