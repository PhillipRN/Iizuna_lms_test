<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class QuestionModel
{
    private $_tableName;

    function __construct($titleNo) {
        $this->_tableName = "TC" . $titleNo . "_TC05";
        $this->_syubetuTableName = "TC" . $titleNo . "_TC02";
        $this->_chapterTableName = "TC" . $titleNo . "_TC03";
        $this->_levelTableName = "TC" . $titleNo . "_TC06";
        $this->_frequencyTableName = "TC" . $titleNo . "_TC07";
        $this->_midasiTableName = "TC" . $titleNo . "_TC08";
        $this->_otherAnswerTableName = "TC" . $titleNo . "_other_answer";
    }

    /**
     * @return mixed
     */
    function ExistTable()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SHOW TABLES LIKE '{$this->_tableName}'
SQL;

        $sth = $pdo->prepare($sql);

        PDOHelper::ExecuteWithTry($sth);

        $record = $sth->fetch(\PDO::FETCH_ASSOC);

        return !empty($record);
    }

    /**
     * @return mixed
     */
    function ExistChapterTable()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SHOW TABLES LIKE '{$this->_chapterTableName}'
SQL;

        $sth = $pdo->prepare($sql);

        PDOHelper::ExecuteWithTry($sth);

        $record = $sth->fetch(\PDO::FETCH_ASSOC);

        return !empty($record);
    }

    /**
     * @return mixed
     */
    function ExistFrequencyTable()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SHOW TABLES LIKE '{$this->_frequencyTableName}'
SQL;

        $sth = $pdo->prepare($sql);

        PDOHelper::ExecuteWithTry($sth);

        $record = $sth->fetch(\PDO::FETCH_ASSOC);

        return !empty($record);
    }

    /**
     * @return mixed
     */
    function ExistLevelTable()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SHOW TABLES LIKE '{$this->_levelTableName}'
SQL;

        $sth = $pdo->prepare($sql);

        PDOHelper::ExecuteWithTry($sth);

        $record = $sth->fetch(\PDO::FETCH_ASSOC);

        return !empty($record);
    }

    /**
     * @return mixed
     */
    function ExistMidasiTable()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SHOW TABLES LIKE '{$this->_midasiTableName}'
SQL;

        $sth = $pdo->prepare($sql);

        PDOHelper::ExecuteWithTry($sth);

        $record = $sth->fetch(\PDO::FETCH_ASSOC);

        return !empty($record);
    }

    /**
     * @return mixed
     */
    function ExistOtherAnswerTable()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SHOW TABLES LIKE '{$this->_otherAnswerTableName}'
SQL;

        $sth = $pdo->prepare($sql);

        PDOHelper::ExecuteWithTry($sth);

        $record = $sth->fetch(\PDO::FETCH_ASSOC);

        return !empty($record);
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
            $sqlColumn[] = ":SYOMONNO_" . $i;
        }

        $sqlPart = join(",", $sqlColumn);

        $sql = <<<SQL
SELECT 
  SYOMONNO,
  DAIMONNO,
  BUN,
  ANSWERFROM,
  MIDASINO,
  ANSBUN,
  CHOICES
FROM {$this->_tableName} question 
WHERE SYOMONNO IN ({$sqlPart})
SQL;

        $sth = $pdo->prepare($sql);

        // 変数の数だけバインド
        for ($i=0; $i<count($shomonNos); ++$i)
        {
            $sth->bindValue(":SYOMONNO_" . $i, $shomonNos[$i]);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $shomonNos
     * @return array
     */
    function GetsForManualIndividualByShomonNos($shomonNos)
    {

        $pdo = PDOHelper::GetPDO();

        $sqlColumn = array();

        for ($i=0; $i<count($shomonNos); ++$i)
        {
            $sqlColumn[] = ":SYOMONNO_" . $i;
        }

        $sqlPart = join(",", $sqlColumn);

        $sql = <<<SQL
SELECT 
  SYOMONNO,
  DAIMONNO,
  BUN,
  ANSWERFROM,
  MIDASINO,
  ANSBUN,
  CHOICES,
  SYUBETUNO,
  LEVELNO
FROM {$this->_tableName} question 
WHERE SYOMONNO IN ({$sqlPart}) 
ORDER BY
  SYOMONNO ASC,
  LEVELNO ASC
SQL;

        $sth = $pdo->prepare($sql);

        // 変数の数だけバインド
        for ($i=0; $i<count($shomonNos); ++$i)
        {
            $sth->bindValue(":SYOMONNO_" . $i, $shomonNos[$i]);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $rangeData
     * @return array
     */
    function GetsForExtractWithRangeData($rangeData)
    {
        $pdo = PDOHelper::GetPDO();

        $sqlWhere = $this->CreateWhereForSQLByRangeData($rangeData);

        if (!empty($sqlWhere))
        {
            $sqlWhere = "WHERE {$sqlWhere}";
        }

        $sql = <<<SQL
SELECT 
  SYOMONNO,
  SYUBETUNO,
  REVNO,
  REVPNO
FROM {$this->_tableName} question 
{$sqlWhere} 
SQL;

        $sth = $pdo->prepare($sql);

        if (!empty($sqlWhere))
        {
            // 変数のバインド
            $this->BindValuesByRangeData($sth, $rangeData);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $shomonNos
     * @return array
     */
    function GetsForExtractByShomonNos($shomonNos)
    {
        $pdo = PDOHelper::GetPDO();

        $sqlColumn = array();

        for ($i=0; $i<count($shomonNos); ++$i)
        {
            $sqlColumn[] = ":SYOMONNO_" . $i;
        }

        $sqlPart = join(",", $sqlColumn);

        $sql = <<<SQL
SELECT
  SYOMONNO,
  SYUBETUNO,
  REVNO,
  REVPNO
FROM {$this->_tableName} question
WHERE SYOMONNO IN ({$sqlPart})
SQL;

        $sth = $pdo->prepare($sql);

        // 変数の数だけバインド
        for ($i=0; $i<count($shomonNos); ++$i)
        {
            $sth->bindValue(":SYOMONNO_" . $i, $shomonNos[$i]);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $rangeData
     * @return string
     */
    private function CreateWhereForSQLByRangeData($rangeData)
    {
        if (empty($rangeData["column"]) || (empty($rangeData["range"]) && empty($rangeData["values"])))
        {
            return "";
        }

        $sqlPart = "";
        $column = $rangeData["column"];

        // 範囲指定の場合
        if (!empty($rangeData["range"]))
        {
            $sqlColumn = [];

            for ($i=0; $i<count($rangeData["range"]); ++$i)
            {
                $range = $rangeData["range"][$i];
                if (isset($range["from"]) && isset($range["to"]))
                {
                    $sqlColumn[] = "(:from_{$i} <= {$column} AND {$column} <= :to_{$i})";
                }
                elseif (isset($range["from"]) && !isset($range["to"]))
                {
                    $sqlColumn[] = ":from_{$i} <= {$column}";
                }
                else
                {
                    $sqlColumn[] = "{$column} <= :to_{$i}";
                }
            }

            $sqlPart = join(" OR ", $sqlColumn);
        }
        // 値指定の場合
        else if (!empty($rangeData["values"]))
        {
            $sqlColumn = [];

            for ($i=0; $i<count($rangeData["values"]); ++$i)
            {
                $sqlColumn[] = ":VALUE_" . $i;
            }

            $sqlPart = join(",", $sqlColumn);
            $sqlPart = "{$column} IN ({$sqlPart})";
        }

        return $sqlPart;
    }

    /**
     * @param $frequency
     * @return string
     */
    private function CreateWhereForSQLByFrequency($frequency)
    {
        $sqlWhereFrequency = "";

        // 出題頻度
        if (!empty($frequency))
        {
            $columns = [];

            for ($i=0; $i<count($frequency); ++$i)
            {
                $columns[] = ":FREQUENCY_" . $i;
            }

            $sqlWhereFrequency = "FREQENCYNO IN (" . (join(",", $columns)) . ")";
        }

        return $sqlWhereFrequency;
    }

    /**
     * @param $sth
     * @param $rangeData
     */
    private function BindValuesByRangeData($sth, $rangeData)
    {
        // 範囲指定の場合
        if (!empty($rangeData["range"]))
        {
            for ($i = 0; $i < count($rangeData["range"]); ++$i)
            {
                $range = $rangeData["range"][$i];

                if (isset($range["from"]))
                {
                    $sth->bindValue(":from_{$i}", $range["from"]);
                }

                if (isset($range["to"]))
                {
                    $sth->bindValue(":to_{$i}", $range["to"]);
                }
            }
        }
        // 値指定の場合
        else if (!empty($rangeData["values"]))
        {
            // 変数の数だけバインド
            for ($i=0; $i<count($rangeData["values"]); ++$i)
            {
                $sth->bindValue(":VALUE_" . $i, $rangeData["values"][$i]);
            }
        }
    }

    /**
     * @param $sth
     * @param $frequency
     */
    private function BindValuesByFrequency($sth, $frequency)
    {
        if (!empty($frequency))
        {
            for ($i=0; $i<count($frequency); ++$i)
            {
                $sth->bindValue(":FREQUENCY_" . $i, $frequency[$i], \PDO::PARAM_INT);
            }
        }
    }

    /**
     * @return mixed
     */
    function GetPageMinMAx()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  MIN(PAGE) as page_min,
  MAX(PAGE) as page_max 
FROM {$this->_tableName} question 
WHERE PAGE != 0 
SQL;

        $sth = $pdo->prepare($sql);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @return mixed
     */
    function GetMidasiNoMinMAx()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  MIN(MIDASINO) as midasi_no_min,
  MAX(MIDASINO) as midasi_no_max 
FROM {$this->_tableName} question 
WHERE MIDASINO != 0 
SQL;

        $sth = $pdo->prepare($sql);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @return mixed
     */
    function GetNonEmptyMidasiNoRecords()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  MIDASINO 
FROM {$this->_tableName} question 
WHERE MIDASINO IS NOT NULL 
  AND MIDASINO != 0 
SQL;

        $sth = $pdo->prepare($sql);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @return mixed
     */
    function GetNonEmptyMidasiNameRecords()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  MIDASINO 
FROM {$this->_tableName} question 
WHERE MIDASINO IS NOT NULL 
  AND MIDASINO != 0 
  AND MIDASINAME IS NOT NULL 
  AND MIDASINAME != '' 
SQL;

        $sth = $pdo->prepare($sql);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @return mixed
     */
    function GetLevelCount()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  COUNT(*) as count
FROM {$this->_levelTableName} 
SQL;

        $sth = $pdo->prepare($sql);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @return mixed
     */
    function GetFrequencyCount()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  COUNT(*) as count
FROM {$this->_frequencyTableName} 
SQL;

        $sth = $pdo->prepare($sql);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $rangeData
     * @return array
     */
    function GetSyubetuNums($rangeData, $frequency)
    {
        $pdo = PDOHelper::GetPDO();

        $sqlWhere = $this->CreateWhereForSQLByRangeData($rangeData);
        $sqlWhereFrequency = $this->CreateWhereForSQLByFrequency($frequency);

        if (!empty($sqlWhere))
        {
            $sqlWhere = "WHERE {$sqlWhere}";
        }

        if (!empty($sqlWhereFrequency))
        {
            if (!empty($sqlWhere))
            {
                $sqlWhere .= " AND " . $sqlWhereFrequency;
            }
            else
            {
                $sqlWhere = "WHERE " . $sqlWhereFrequency;
            }
        }

        $sql = <<<SQL
SELECT
  question.SYUBETUNO,
  syubetu.NAME,
  COUNT(question.SYUBETUNO) AS NUM 
FROM {$this->_tableName} question
LEFT JOIN {$this->_syubetuTableName} syubetu 
  ON question.SYUBETUNO = syubetu.SYUBETUNO 
{$sqlWhere} 
GROUP BY question.SYUBETUNO
ORDER BY question.SYUBETUNO ASC
SQL;

        $sth = $pdo->prepare($sql);

        if (!empty($sqlWhere))
        {
            // 変数のバインド
            $this->BindValuesByRangeData($sth, $rangeData);
        }

        if (!empty($sqlWhereFrequency))
        {
            $this->BindValuesByFrequency($sth, $frequency);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $rangeData
     * @param $frequency
     * @param $changeDisplay
     * @return array
     */
    function GetSyubetuNumsWithLevel($rangeData, $frequency, $changeDisplay)
    {
        $pdo = PDOHelper::GetPDO();

        $sqlWhere = $this->CreateWhereForSQLByRangeData($rangeData);
        $sqlWhereFrequency = $this->CreateWhereForSQLByFrequency($frequency);

        if (!empty($sqlWhere))
        {
            $sqlWhere = " AND ({$sqlWhere})";
        }

        if (!empty($sqlWhereFrequency))
        {
            $sqlWhere .= " AND " . $sqlWhereFrequency;
        }

        $groupBy = "";
        $orderBy = "";

        switch ($changeDisplay)
        {
            case CHANGE_DISPLAY_TYPE_QUESTION:
                $groupBy = "GROUP BY question.SYUBETUNO, question.LEVELNO";
                $orderBy = "ORDER BY question.SYUBETUNO ASC, question.LEVELNO ASC";
                break;

            case CHANGE_DISPLAY_TYPE_LEVEL:
                $groupBy = "GROUP BY question.LEVELNO, question.SYUBETUNO";
                $orderBy = "ORDER BY question.LEVELNO ASC, question.SYUBETUNO ASC";
                break;
        }

        $sql = <<<SQL
SELECT
  question.SYUBETUNO,
  syubetu.NAME,
  question.LEVELNO as LEVELNO,
  level.NAME as LEVEL,
  COUNT(question.SYUBETUNO) AS NUM
FROM {$this->_tableName} question
LEFT JOIN {$this->_syubetuTableName} syubetu
  ON question.SYUBETUNO = syubetu.SYUBETUNO
LEFT JOIN {$this->_levelTableName} level
  ON question.LEVELNO = level.LEVELNO
WHERE
  question.LEVELNO > 0
  {$sqlWhere}
{$groupBy}
{$orderBy}
SQL;

        $sth = $pdo->prepare($sql);

        if (!empty($sqlWhere))
        {
            // 変数のバインド
            $this->BindValuesByRangeData($sth, $rangeData);
        }

        if (!empty($sqlWhereFrequency))
        {
            $this->BindValuesByFrequency($sth, $frequency);
        }

        PDOHelper::ExecuteWithTry($sth);

         return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $syubetuNo
     * @param $levelNo
     * @param $frequency
     * @param $rangeData
     * @param $book
     * @return array
     */
    function GetIndividualQuestions($syubetuNo, $levelNo, $frequency, $rangeData, $book)
    {
        $pdo = PDOHelper::GetPDO();

        $sqlWhere = $this->CreateWhereForSQLByRangeData($rangeData);
        $sql = $this->GetIndividualQuestionsSql($levelNo, $frequency, $sqlWhere, $book);
        $sth = $pdo->prepare($sql);

        $sth->bindValue(":SYUBETUNO", $syubetuNo, \PDO::PARAM_INT);

        if (!empty($sqlWhere))
        {
            // 変数のバインド
            $this->BindValuesByRangeData($sth, $rangeData);
        }

        if (!empty($levelNo))
        {
            $sth->bindValue(":LEVELNO", $levelNo, \PDO::PARAM_INT);
        }

        if (!empty($frequency))
        {
            $this->BindValuesByFrequency($sth, $frequency);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $levelNo
     * @param $frequency
     * @param $sqlWhere
     * @param $book
     * @return string
     */
    private function GetIndividualQuestionsSql($levelNo, $frequency, $sqlWhere, $book)
    {
        if (!empty($sqlWhere))
        {
            $sqlWhere = " AND ({$sqlWhere})";
        }

        if (!empty($levelNo))
        {
            $sqlWhere .= " AND question.LEVELNO = :LEVELNO";
        }

        if (!empty($frequency))
        {
            $sqlWhereFrequency = $this->CreateWhereForSQLByFrequency($frequency);

            if (!empty($sqlWhereFrequency))
            {
                $sqlWhere .= " AND " . $sqlWhereFrequency;
            }
        }

        $levelSelect = "";
        $levelJoin = "";
        $frequencySelect = "";
        $frequencyJoin = "";
        $midasiSelect = "";
        $midasiJoin = "";

        if ($book["level_flg"] == 1) {
            $levelSelect = ", level.NAME as LEVEL";
            $levelJoin = "LEFT JOIN {$this->_levelTableName} level ON question.LEVELNO = level.LEVELNO ";
        }

        if ($book["frequency_flg"] == 1) {
            $frequencySelect = ", frequency.NAME as FREQUENCY";
            $frequencyJoin = "LEFT JOIN {$this->_frequencyTableName} frequency ON question.FREQENCYNO = frequency.FREQUENCYNO ";
        }

        if ($book["midasi_flg"] == 1) {
            $midasiSelect = ", midasi.NAME as MIDASINAME";
            $midasiJoin = "LEFT JOIN {$this->_midasiTableName} midasi ON question.MIDASINO = midasi.MIDASINO ";
        }

        $sql = <<<SQL
SELECT
  question.SYOMONNO,
  question.LEVELNO,
  question.FREQENCYNO,
  question.MIDASINO,
  question.BUN,
  question.CHOICES,
  question.ANSWERFROM,
  question.REVNO,
  question.REVPNO
  {$levelSelect}
  {$frequencySelect}
  {$midasiSelect}
FROM
  {$this->_tableName} question
  {$levelJoin}
  {$frequencyJoin}
  {$midasiJoin}
WHERE
  question.SYUBETUNO = :SYUBETUNO 
  {$sqlWhere}
ORDER BY
  question.MIDASINO = 0,
  question.MIDASINO ASC,
  question.SYOMONNO ASC
SQL;

        return $sql;
    }

    /**
     * @param $syubetuNo
     * @param $levelNo
     * @param $frequency
     * @param $limit
     * @param $rangeData
     * @return array
     */
    function GetRandomRecordsBySyubetuNo($syubetuNo, $levelNo, $frequency, $limit, $rangeData)
    {
        $pdo = PDOHelper::GetPDO();

        $sqlWhere = $this->CreateWhereForSQLByRangeData($rangeData);
        $sqlWhereFrequency = $this->CreateWhereForSQLByFrequency($frequency);

        if (!empty($sqlWhere))
        {
            $sqlWhere = " AND ({$sqlWhere})";
        }

        if (!empty($sqlWhereFrequency))
        {
            $sqlWhere .= " AND " . $sqlWhereFrequency;
        }

        $sqlLevelNo = "";

        if (!empty($levelNo))
        {
            $sqlLevelNo = " AND LEVELNO = :LEVELNO";
        }

        $sql = <<<SQL
SELECT SYOMONNO 
FROM {$this->_tableName} question 
WHERE SYUBETUNO = :syubetuNo 
{$sqlLevelNo}
{$sqlWhere} 
ORDER BY RAND() 
LIMIT :limit
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':syubetuNo', $syubetuNo);
        $sth->bindValue(':limit', $limit, \PDO::PARAM_INT);

        if (!empty($sqlWhere))
        {
            // 変数のバインド
            $this->BindValuesByRangeData($sth, $rangeData);
        }

        if (!empty($levelNo))
        {
            $sth->bindValue(":LEVELNO", $levelNo, \PDO::PARAM_INT);
        }

        if (!empty($sqlWhereFrequency))
        {
            $this->BindValuesByFrequency($sth, $frequency);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }


    function GetRandomRecordBySyubetuNoAndNoDuplication($syubetuNo, $levelNo, $frequency, $rangeData, $shomonNos, $revNos)
    {
        $pdo = PDOHelper::GetPDO();

        $sqlWhere = $this->CreateWhereForSQLByRangeData($rangeData);
        $sqlWhereFrequency = $this->CreateWhereForSQLByFrequency($frequency);
        $sqlWhereWithoutShomonNos = $this->CreateWhereForSQLWithoutShomonNos($shomonNos);
        $sqlWhereNoDuplicationRevNos = $this->CreateWhereForSQLNoDuplicationRevNos($revNos);

        // 個別重複禁止：既に選択している問題のSYOMONNOとREVPNOが同じものは除外する
        $sqlWhereNoDuplicationRevpNos = $this->CreateWhereForSQLNoDuplicationRevpnos($shomonNos);

        if (!empty($sqlWhere))
        {
            $sqlWhere = " AND ({$sqlWhere})";
        }

        if (!empty($sqlWhereFrequency))
        {
            $sqlWhere .= " AND " . $sqlWhereFrequency;
        }

        if (!empty($sqlWhereWithoutShomonNos))
        {
            $sqlWhere .= " AND " . $sqlWhereWithoutShomonNos;
        }

        if (!empty($sqlWhereNoDuplicationRevNos))
        {
            $sqlWhere .= " AND " . $sqlWhereNoDuplicationRevNos;
        }

        if (!empty($sqlWhereNoDuplicationRevpNos))
        {
            $sqlWhere .= " AND " . $sqlWhereNoDuplicationRevpNos;
        }

        $sqlLevelNo = "";

        if (!empty($levelNo))
        {
            $sqlLevelNo = " AND LEVELNO = :LEVELNO";
        }

        $sql = <<<SQL
SELECT 
  SYOMONNO,
  SYUBETUNO,
  REVNO,
  REVPNO
FROM {$this->_tableName} question 
WHERE SYUBETUNO = :syubetuNo 
{$sqlLevelNo}
{$sqlWhere} 
ORDER BY RAND() 
LIMIT 1
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':syubetuNo', $syubetuNo);

        if (!empty($sqlWhere))
        {
            // 変数のバインド
            $this->BindValuesByRangeData($sth, $rangeData);
        }

        if (!empty($levelNo))
        {
            $sth->bindValue(":LEVELNO", $levelNo, \PDO::PARAM_INT);
        }

        if (!empty($sqlWhereFrequency))
        {
            $this->BindValuesByFrequency($sth, $frequency);
        }

        if (!empty($sqlWhereWithoutShomonNos))
        {
            $this->BindValuesByWithoutShomonNos($sth, $shomonNos);
        }

        if (!empty($sqlWhereNoDuplicationRevNos))
        {
            $this->BindValuesByNoDuplicationRevNos($sth, $revNos);
        }

        if (!empty($sqlWhereNoDuplicationRevpNos))
        {
            $this->BindValuesByNoDuplicationRevpNos($sth, $shomonNos);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $shomonNos
     * @return string
     */
    private function CreateWhereForSQLWithoutShomonNos($shomonNos)
    {
        $sqlWhere = "";

        if (!empty($shomonNos))
        {
            $columns = [];

            for ($i=0; $i<count($shomonNos); ++$i)
            {
                $columns[] = "SYOMONNO != :SYOMONNO_" . $i;
            }

            $sqlWhere = "(" . (join(" AND ", $columns)) . ")";
        }

        return $sqlWhere;
    }

    /**
     * @param $sth
     * @param $shomonNos
     */
    private function BindValuesByWithoutShomonNos($sth, $shomonNos)
    {
        if (!empty($shomonNos))
        {
            for ($i=0; $i<count($shomonNos); ++$i)
            {
                $sth->bindValue(":SYOMONNO_" . $i, $shomonNos[$i], \PDO::PARAM_INT);
            }
        }
    }

    /**
     * @param $revNos
     * @return string
     */
    private function CreateWhereForSQLNoDuplicationRevNos($revNos)
    {
        $sqlWhere = "";

        if (!empty($revNos))
        {
            $columns = [];

            for ($i=0; $i<count($revNos); ++$i)
            {
                $columns[] = "REVNO != :REVNO_" . $i;
            }

            $sqlWhere = "(" . (join(" AND ", $columns)) . ")";
        }

        return $sqlWhere;
    }

    /**
     * @param $sth
     * @param $revNos
     */
    private function BindValuesByNoDuplicationRevNos($sth, $revNos)
    {
        if (!empty($revNos))
        {
            for ($i=0; $i<count($revNos); ++$i)
            {
                $sth->bindValue(":REVNO_" . $i, $revNos[$i], \PDO::PARAM_INT);
            }
        }
    }

    /**
     * @param $shomonNos
     * @return string
     */
    private function CreateWhereForSQLNoDuplicationRevpNos($shomonNos)
    {
        $sqlWhere = "";

        if (!empty($shomonNos))
        {
            $columns = [];

            for ($i=0; $i<count($shomonNos); ++$i)
            {
                $columns[] = "REVPNO != :REVPNO_" . $i;
            }

            $sqlWhere = "(" . (join(" AND ", $columns)) . ")";
        }

        return $sqlWhere;
    }

    /**
     * @param $sth
     * @param $shomonNos
     */
    private function BindValuesByNoDuplicationRevpNos($sth, $shomonNos)
    {
        if (!empty($shomonNos))
        {
            for ($i=0; $i<count($shomonNos); ++$i)
            {
                $sth->bindValue(":REVPNO_" . $i, $shomonNos[$i], \PDO::PARAM_INT);
            }
        }
    }
}