<?php

namespace IizunaLMS\Models;

use IizunaLMS\Datas\JsonQuizDelivery;
use IizunaLMS\Helpers\PageHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Onigiri\Data\OnigiriJsonQuizDeliveryData;

class OnigiriJsonQuizDeliveryModel extends ModelBase
{
    function __construct()
    {
        $this->_tableName = 'onigiri_json_quiz_delivery';
    }

    /**
     * @param $lmsCordIds
     * @return array
     */
    public function GetOnigiriJsonQuizIds($lmsCordIds): array
    {
        $records = $this->GetsByKeyInValues('lms_code_id', $lmsCordIds);

        if (empty($records)) return [];

        $tmpIds = [];

        foreach ($records as $record) $tmpIds[] = $record['onigiri_json_quiz_id'];

        return array_values( array_unique($tmpIds) );
    }

    /**
     * @param OnigiriJsonQuizDeliveryData $OnigiriJsonQuizDelivery
     * @return mixed
     */
    public function Get(OnigiriJsonQuizDeliveryData $OnigiriJsonQuizDelivery)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT * FROM {$this->_tableName} 
WHERE onigiri_json_quiz_id = CAST(:onigiri_json_quiz_id AS DECIMAL(20))
  AND lms_code_id = CAST(:lms_code_id AS DECIMAL(20))
LIMIT 1
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':onigiri_json_quiz_id', $OnigiriJsonQuizDelivery->onigiri_json_quiz_id, \PDO::PARAM_STR);
        $sth->bindValue(':lms_code_id', $OnigiriJsonQuizDelivery->lms_code_id, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param OnigiriJsonQuizDeliveryData $OnigiriJsonQuizDelivery
     * @return bool
     */
    public function Delete(OnigiriJsonQuizDeliveryData $OnigiriJsonQuizDelivery): bool
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
DELETE FROM {$this->_tableName} 
WHERE onigiri_json_quiz_id = CAST(:onigiri_json_quiz_id AS DECIMAL(20))
  AND lms_code_id = CAST(:lms_code_id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':onigiri_json_quiz_id', $OnigiriJsonQuizDelivery->onigiri_json_quiz_id, \PDO::PARAM_STR);
        $sth->bindValue(':lms_code_id', $OnigiriJsonQuizDelivery->lms_code_id, \PDO::PARAM_STR);

        return PDOHelper::ExecuteWithTry($sth);
    }

    public function GetExistsOnigiriJsonQuizzes($onigiriJsonQuizIds)
    {
        $pdo = $this->GetPDO();

        $bindKeyArray = [];

        for ($i=0; $i<count($onigiriJsonQuizIds); ++$i)
        {
            $bindKeyArray[] = "CAST(:key_{$i} AS DECIMAL(20))";
        }

        $bindKeys = implode(',', $bindKeyArray);

        $sql = <<<SQL
SELECT 
  onigiri_json_quiz_id 
FROM {$this->_tableName} 
WHERE onigiri_json_quiz_id IN ({$bindKeys}) 
GROUP BY onigiri_json_quiz_id
SQL;

        $sth = $pdo->prepare($sql);

        for ($i=0; $i<count($onigiriJsonQuizIds); ++$i)
        {
            $sth->bindValue(":key_{$i}", $onigiriJsonQuizIds[$i], \PDO::PARAM_STR);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }



    /**
     * @param OnigiriJsonQuizDeliveryData $OnigiriJsonQuizDelivery
     * @return bool
     */
    public function UpdateNoticeId(OnigiriJsonQuizDeliveryData $OnigiriJsonQuizDelivery): bool
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
UPDATE {$this->_tableName}
SET notice_id = :notice_id 
WHERE onigiri_json_quiz_id = CAST(:onigiri_json_quiz_id AS DECIMAL(20))
  AND lms_code_id = CAST(:lms_code_id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':notice_id', $OnigiriJsonQuizDelivery->notice_id);
        $sth->bindValue(':onigiri_json_quiz_id', $OnigiriJsonQuizDelivery->onigiri_json_quiz_id, \PDO::PARAM_STR);
        $sth->bindValue(':lms_code_id', $OnigiriJsonQuizDelivery->lms_code_id, \PDO::PARAM_STR);

        return PDOHelper::ExecuteWithTry($sth);
    }
}