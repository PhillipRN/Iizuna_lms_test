<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class JsonQuizCombinedViewModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'json_quiz_combined_view';
    }

    /**
     * @param $jsonQuizIds
     * @param $onigiriJsonQuizIds
     * @return array|false
     */
    public function GetRecords($jsonQuizIds, $onigiriJsonQuizIds)
    {
        $pdo = $this->GetPDO();

        $whereJsonQuiz = '';
        $whereOnigiriJsonQuiz = '';

        if (!empty($jsonQuizIds))
        {
            $bindKeyArray = [];

            for ($i=0; $i<count($jsonQuizIds); ++$i)
            {
                $bindKeyArray[] = "CAST(:key_1_{$i} AS DECIMAL(20))";
            }

            $whereJsonQuiz = 'json_quiz_id IN (' . implode(',', $bindKeyArray) .')';
        }

        if (!empty($onigiriJsonQuizIds))
        {
            $bindKeyArray = [];

            for ($i=0; $i<count($onigiriJsonQuizIds); ++$i)
            {
                $bindKeyArray[] = "CAST(:key_2_{$i} AS DECIMAL(20))";
            }

            $whereOnigiriJsonQuiz = 'onigiri_json_quiz_id IN (' . implode(',', $bindKeyArray) .')';
        }

        $where = '';

        if (!empty($whereJsonQuiz) && !empty($whereOnigiriJsonQuiz))
        {
            $where = "{$whereJsonQuiz} OR {$whereOnigiriJsonQuiz}";
        }
        else if (!empty($whereJsonQuiz))
        {
            $where = $whereJsonQuiz;
        }
        else if (!empty($whereOnigiriJsonQuiz))
        {
            $where = $whereOnigiriJsonQuiz;
        }

        $sql = <<<SQL
SELECT 
  * 
FROM {$this->_tableName} 
WHERE {$where} 
ORDER BY create_date DESC
SQL;

        $sth = $pdo->prepare($sql);

        if (!empty($jsonQuizIds))
        {
            for ($i=0; $i<count($jsonQuizIds); ++$i)
            {
                $sth->bindValue(":key_1_{$i}", $jsonQuizIds[$i], \PDO::PARAM_STR);
            }
        }

        if (!empty($onigiriJsonQuizIds))
        {
            for ($i=0; $i<count($onigiriJsonQuizIds); ++$i)
            {
                $sth->bindValue(":key_2_{$i}", $onigiriJsonQuizIds[$i], \PDO::PARAM_STR);
            }
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}