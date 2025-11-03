<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\LmsTicket;

class LmsTicketModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'lms_ticket';
    }

    /**
     * @param $teacherId
     * @return array|false
     */
    function GetUndeletedTicketList($teacherId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *
FROM {$this->_tableName} 
WHERE 
  teacher_id = :teacher_id AND
  status < :status
ORDER BY
  expire_year DESC,
  expire_month DESC
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':teacher_id', $teacherId);
        $sth->bindValue(':status', LmsTicket::STATUS_DELETE_BY_TEACHER);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $teacherId
     * @param $titleNo
     * @param $expireYear
     * @param $expireMonth
     * @return mixed
     */
    function GetUndeletedTicketWithYearAndMonth($teacherId, $titleNo, $expireYear, $expireMonth)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *
FROM {$this->_tableName} 
WHERE 
  teacher_id = :teacher_id AND
  title_no = :title_no AND
  status < :status AND
  expire_year = :expire_year AND
  expire_month = :expire_month
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':teacher_id', $teacherId);
        $sth->bindValue(':title_no', $titleNo);
        $sth->bindValue(':status', LmsTicket::STATUS_DELETE_BY_TEACHER);
        $sth->bindValue(':expire_year', $expireYear);
        $sth->bindValue(':expire_month', $expireMonth);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }
}