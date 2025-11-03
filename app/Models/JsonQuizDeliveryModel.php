<?php

namespace IizunaLMS\Models;

use IizunaLMS\Datas\JsonQuizDelivery;
use IizunaLMS\Helpers\PageHelper;
use IizunaLMS\Helpers\PDOHelper;

class JsonQuizDeliveryModel extends ModelBase
{
    function __construct()
    {
        $this->_tableName = 'json_quiz_delivery';
    }

    /**
     * @param $lmsCordIds
     * @return array
     */
    public function GetJsonQuizIds($lmsCordIds): array
    {
        $records = $this->GetsByKeyInValues('lms_code_id', $lmsCordIds);

        if (empty($records)) return [];

        $tmpIds = [];

        foreach ($records as $record) $tmpIds[] = $record['json_quiz_id'];

        return array_values( array_unique($tmpIds) );
    }

    /**
     * @param JsonQuizDelivery $JsonQuizDelivery
     * @return bool
     */
    public function Delete(JsonQuizDelivery $JsonQuizDelivery): bool
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
DELETE FROM {$this->_tableName} 
WHERE json_quiz_id = CAST(:json_quiz_id AS DECIMAL(20))
  AND lms_code_id = CAST(:lms_code_id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':json_quiz_id', $JsonQuizDelivery->json_quiz_id, \PDO::PARAM_STR);
        $sth->bindValue(':lms_code_id', $JsonQuizDelivery->lms_code_id, \PDO::PARAM_STR);

        return PDOHelper::ExecuteWithTry($sth);
    }

    public function GetExistsJsonQuizzes($jsonQuizIds)
    {
        $pdo = $this->GetPDO();

        $bindKeyArray = [];

        for ($i=0; $i<count($jsonQuizIds); ++$i)
        {
            $bindKeyArray[] = "CAST(:key_{$i} AS DECIMAL(20))";
        }

        $bindKeys = implode(',', $bindKeyArray);

        $sql = <<<SQL
SELECT 
  json_quiz_id 
FROM {$this->_tableName} 
WHERE json_quiz_id IN ({$bindKeys}) 
GROUP BY json_quiz_id
SQL;

        $sth = $pdo->prepare($sql);

        for ($i=0; $i<count($jsonQuizIds); ++$i)
        {
            $sth->bindValue(":key_{$i}", $jsonQuizIds[$i], \PDO::PARAM_STR);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}