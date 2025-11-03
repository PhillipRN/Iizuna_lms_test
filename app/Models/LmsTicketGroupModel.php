<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\LmsTicketGroup;

class LmsTicketGroupModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'lms_ticket_group';
    }

    /**
     * @param $lmsTicketId
     * @return array|false
     */
    function GetUndeletedGroupList($lmsTicketId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *
FROM {$this->_tableName} 
WHERE 
  lms_ticket_id = :lms_ticket_id AND
  status < :status
ORDER BY
  expire_year DESC,
  expire_month DESC
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':lms_ticket_id', $lmsTicketId);
        $sth->bindValue(':status', LmsTicketGroup::STATUS_DELETE_BY_TEACHER);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}