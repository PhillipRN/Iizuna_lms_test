<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\LmsTickets\LmsTicketApplication;

class LmsTicketApplicationViewModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'lms_ticket_application_view';
    }

    /**
     * @param $lmsTicketId
     * @return mixed
     */
    function GetUndeletedTicketListByLmsTicketId($lmsTicketId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *
FROM {$this->_tableName} 
WHERE 
  lms_ticket_id = :lms_ticket_id AND
  lms_ticket_status < :lms_ticket_status AND
  lms_ticket_application_status < :lms_ticket_application_status
ORDER BY
  expire_year DESC,
  expire_month DESC
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':lms_ticket_id', $lmsTicketId);
        $sth->bindValue(':lms_ticket_status', LmsTicket::STATUS_DELETE_BY_TEACHER);
        $sth->bindValue(':lms_ticket_application_status', LmsTicketApplication::STATUS_CANCELLED_BY_TEACHER);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $teacherId
     * @return mixed
     */
    function GetUndeletedTicketListByTeacherId($teacherId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *
FROM {$this->_tableName} 
WHERE 
  teacher_id = :teacher_id AND
  lms_ticket_status < :lms_ticket_status AND
  lms_ticket_application_status < :lms_ticket_application_status
ORDER BY
  expire_year DESC,
  expire_month DESC
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':teacher_id', $teacherId);
        $sth->bindValue(':lms_ticket_status', LmsTicket::STATUS_DELETE_BY_TEACHER);
        $sth->bindValue(':lms_ticket_application_status', LmsTicketApplication::STATUS_CANCELLED_BY_TEACHER);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $limit
     * @param $offset
     * @return array|false
     */
    function GetUndeletedTicketListByLimitAndOffset($limit, $offset)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *
FROM {$this->_tableName} 
WHERE 
  lms_ticket_status < :lms_ticket_status AND
  lms_ticket_application_status < :lms_ticket_application_status
ORDER BY id DESC 
LIMIT :limit 
OFFSET :offset 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':lms_ticket_status', LmsTicket::STATUS_DELETE_BY_TEACHER);
        $sth->bindValue(':lms_ticket_application_status', LmsTicketApplication::STATUS_CANCELLED_BY_TEACHER);
        $sth->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $sth->bindValue(':offset', $offset, \PDO::PARAM_INT);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $schoolId
     * @return array|false
     */
    function GetUndeletedSchoolTeacherTicketList($schoolId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *
FROM {$this->_tableName} 
WHERE 
  school_id = CAST(:school_id AS DECIMAL(20)) AND
  lms_ticket_status < :lms_ticket_status AND
  lms_ticket_application_status < :lms_ticket_application_status
ORDER BY id DESC 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':school_id', $schoolId, \PDO::PARAM_STR);
        $sth->bindValue(':lms_ticket_status', LmsTicket::STATUS_DELETE_BY_TEACHER);
        $sth->bindValue(':lms_ticket_application_status', LmsTicketApplication::STATUS_CANCELLED_BY_TEACHER);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return int
     */
    function CountUndeletedApplicationCount()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT count(id) as cnt 
FROM {$this->_tableName} 
WHERE 
  lms_ticket_status < :lms_ticket_status AND
  lms_ticket_application_status < :lms_ticket_application_status
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':lms_ticket_status', LmsTicket::STATUS_DELETE_BY_TEACHER);
        $sth->bindValue(':lms_ticket_application_status', LmsTicketApplication::STATUS_CANCELLED_BY_TEACHER);
        PDOHelper::ExecuteWithTry($sth);

        $row = $sth->fetch(\PDO::FETCH_ASSOC);
        return (int)$row["cnt"];
    }

    /**
     * @return array|false
     */
    function GetUndeletedTicketList()
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *
FROM {$this->_tableName} 
WHERE 
  lms_ticket_status < :lms_ticket_status AND
  lms_ticket_application_status < :lms_ticket_application_status
ORDER BY id DESC 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':lms_ticket_status', LmsTicket::STATUS_DELETE_BY_TEACHER);
        $sth->bindValue(':lms_ticket_application_status', LmsTicketApplication::STATUS_CANCELLED_BY_TEACHER);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}