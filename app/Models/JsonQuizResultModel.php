<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class JsonQuizResultModel extends ModelBase
{
    function __construct()
    {
        $this->_tableName = "json_quiz_result";
        $this->_tableNameJsonQuiz = "json_quiz";
    }

    public function GetsUsersQuizResult($jsonQuizId, $studentId)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  id,
  score,
  create_date
FROM {$this->_tableName} 
WHERE
  json_quiz_id = CAST(:json_quiz_id AS DECIMAL(20)) AND 
  student_id = CAST(:student_id AS DECIMAL(20))
ORDER BY id ASC
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':json_quiz_id', $jsonQuizId, \PDO::PARAM_STR);
        $sth->bindValue(':student_id', $studentId, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $studentId
     * @param $jsonQuizIds
     * @return array|false
     */
    public function GetsUserScore($studentId, $jsonQuizIds)
    {
        $sqlKeys = [];

        for ($i=0; $i<count($jsonQuizIds); ++$i)
        {
            $sqlKeys[] = "CAST(:key_{$i} AS DECIMAL(20))";
        }

        $sqlKeysString = implode(', ', $sqlKeys);

        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  json_quiz_id,
  score
FROM {$this->_tableName} 
WHERE 
  student_id = CAST(:student_id AS DECIMAL(20)) AND 
  json_quiz_id IN ({$sqlKeysString}) 
ORDER BY json_quiz_id ASC
SQL;

        $sth = $pdo->prepare($sql);

        for ($i=0; $i<count($jsonQuizIds); ++$i)
        {
            $sth->bindValue(":key_{$i}", $jsonQuizIds[$i], \PDO::PARAM_STR);
        }

        $sth->bindValue(":student_id", $studentId, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function GetsFirstDataAndStudentData($jsonQuizIds)
    {
        $sqlKeys = [];

        for ($i=0; $i<count($jsonQuizIds); ++$i)
        {
            $sqlKeys[] = "CAST(:key_{$i} AS DECIMAL(20))";
        }

        $sqlKeysString = implode(', ', $sqlKeys);

        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  {$this->_tableName}.json_quiz_id,
  {$this->_tableName}.student_id,
  {$this->_tableName}.score,
  student.name AS student_name,
  student.student_number 
FROM {$this->_tableName} 
INNER JOIN student ON student.id = {$this->_tableName}.student_id
WHERE 
  is_first_result = 1 AND 
  json_quiz_id IN ({$sqlKeysString}) 
ORDER BY json_quiz_id ASC
SQL;

        $sth = $pdo->prepare($sql);

        for ($i=0; $i<count($jsonQuizIds); ++$i)
        {
            $sth->bindValue(":key_{$i}", $jsonQuizIds[$i], \PDO::PARAM_STR);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $studentId
     * @return array|false
     */
    public function GetStudentAnsweredQuizIds($studentId)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  json_quiz_id 
FROM {$this->_tableName} 
WHERE
  student_id = CAST(:student_id AS DECIMAL(20))
GROUP BY json_quiz_id
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':student_id', $studentId, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $studentId
     * @return bool
     */
    function DeleteByStudentId($studentId): bool
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
DELETE FROM {$this->_tableName} 
WHERE student_id = CAST(:student_id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':student_id', $studentId, \PDO::PARAM_STR);

        return PDOHelper::ExecuteWithTry($sth);
    }
}