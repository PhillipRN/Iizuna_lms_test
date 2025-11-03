<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Schools\LmsCodeApplication;

class SchoolGroupModel extends ModelBase
{
    function __construct() {
        $this->_tableName = "school_group";
    }

    /**
     * @param $lmsCodeId
     * @return bool
     */
    public function ApproveApplication($lmsCodeId)
    {
        $sql = <<<SQL
UPDATE {$this->_tableName}
SET
  paid_application_status = :paid_application_status
WHERE
  lms_code_id = CAST(:lms_code_id AS DECIMAL(20))
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);

        $sth->bindValue(':paid_application_status', LmsCodeApplication::STATUS_ALLOWED, \PDO::PARAM_INT);
        $sth->bindValue(':lms_code_id', $lmsCodeId, \PDO::PARAM_STR);

        return PDOHelper::ExecuteWithTry($sth);
    }
}