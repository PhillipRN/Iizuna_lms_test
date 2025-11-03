<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class LmsTicketGroupUseCountModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'lms_ticket_group_use_count';
    }

    /**
     * @param $lmsTicketGroupId
     * @return bool
     */
    public function CountUp($lmsTicketGroupId): bool
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
UPDATE {$this->_tableName} 
SET
  use_count = use_count + 1
WHERE lms_ticket_group_id = CAST(:lms_ticket_group_id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);

        $sth->bindValue(':lms_ticket_group_id', $lmsTicketGroupId, \PDO::PARAM_STR);

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param $lmsTicketGroupId
     * @return bool
     */
    public function CountDown($lmsTicketGroupId): bool
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
UPDATE {$this->_tableName} 
SET
  use_count = use_count - 1
WHERE
  lms_ticket_group_id = CAST(:lms_ticket_group_id AS DECIMAL(20)) AND
  use_count >= 1
SQL;

        $sth = $pdo->prepare($sql);

        $sth->bindValue(':lms_ticket_group_id', $lmsTicketGroupId, \PDO::PARAM_STR);

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * デバッグ用メソッド
     * @param $lmsTicketGroupId
     * @param $useCount
     * @return bool
     */
    private function DebugCountSet($lmsTicketGroupId, $useCount): bool
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
UPDATE {$this->_tableName} 
SET
  use_count = :use_count
WHERE lms_ticket_group_id = CAST(:lms_ticket_group_id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);

        $sth->bindValue(':lms_ticket_group_id', $lmsTicketGroupId, \PDO::PARAM_STR);
        $sth->bindValue(':use_count', $useCount);

        return PDOHelper::ExecuteWithTry($sth);
    }
}