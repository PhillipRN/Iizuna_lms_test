<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class LmsCodeAmountModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'lms_code_amount';
    }

    /**
     * @param $lmsCodeId
     * @return bool
     */
    public function IncrementUseCount($lmsCodeId) {
        $sql = <<<SQL
UPDATE {$this->_tableName}
SET
  use_count = use_count + 1
WHERE
  lms_code_id = CAST(:lms_code_id AS DECIMAL(20))
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);

        $sth->bindValue(':lms_code_id', $lmsCodeId, \PDO::PARAM_STR);

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param $applicationAmount
     * @param $availableAmount
     * @param $lmsCodeId
     * @return bool
     */
    public function ApproveAndIncreaseAmount($applicationAmount, $availableAmount, $lmsCodeId) {
        $sql = <<<SQL
UPDATE {$this->_tableName}
SET
  available_total = available_total + :available_amount,
  application_total = application_total + :application_amount
WHERE
  lms_code_id = CAST(:lms_code_id AS DECIMAL(20))
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);

        $sth->bindValue(':available_amount', $availableAmount);
        $sth->bindValue(':application_amount', $applicationAmount);
        $sth->bindValue(':lms_code_id', $lmsCodeId, \PDO::PARAM_STR);

        return PDOHelper::ExecuteWithTry($sth);
    }
}