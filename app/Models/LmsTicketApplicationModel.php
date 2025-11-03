<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\LmsTicketApplication;

class LmsTicketApplicationModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'lms_ticket_application';
    }

    /**
     * @param $lmsTicketId
     * @return array|false
     */
    function GetUndeletedApplicationList($lmsTicketId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *
FROM {$this->_tableName} 
WHERE 
  lms_ticket_id = :lms_ticket_id AND
  status < :status 
ORDER BY
  id DESC
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':lms_ticket_id', $lmsTicketId);
        $sth->bindValue(':status', LmsTicketApplication::STATUS_CANCELLED_BY_TEACHER);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $lmsTicketId
     * @return array|false
     */
    function GetOldestApplicationRecord($lmsTicketId)
    {
        $pdo = $this->GetPDO();

        // status が APPROVED で更新日付が一番古いものが対象のレコード
        $sql = <<<SQL
SELECT *
FROM {$this->_tableName} 
WHERE 
  lms_ticket_id = :lms_ticket_id AND
  status = :status 
ORDER BY
  update_date ASC
LIMIT 1
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':lms_ticket_id', $lmsTicketId);
        $sth->bindValue(':status', LmsTicketApplication::STATUS_APPROVED);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }
}