<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\LmsTicketGroup;

class LmsTicketGroupViewModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'lms_ticket_group_view';
    }


    /**
     * @param $lmsTicketId
     * @return array|false
     */
    function GetUndeletedTicketGroupList($lmsTicketId)
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
        $sth->bindValue(':status', LmsTicketGroup::STATUS_DELETE_BY_TEACHER);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $schoolId
     * @return array|false
     */
    function GetUndeletedSchoolsTicketGroupList($schoolId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *
FROM {$this->_tableName} 
WHERE 
  school_id = CAST(:school_id AS DECIMAL(20)) AND
  teacher_id = 0 AND
  status < :status
ORDER BY
  id DESC
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':school_id', $schoolId, \PDO::PARAM_STR);
        $sth->bindValue(':status', LmsTicketGroup::STATUS_DELETE_BY_TEACHER);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $teacherId
     * @return array|false
     */
    function GetUndeletedTeachersTicketGroupList($teacherId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *
FROM {$this->_tableName} 
WHERE 
  teacher_id = CAST(:teacher_id AS DECIMAL(20)) AND
  status < :status
ORDER BY
  id DESC
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':teacher_id', $teacherId, \PDO::PARAM_STR);
        $sth->bindValue(':status', LmsTicketGroup::STATUS_DELETE_BY_TEACHER);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $teacherId
     * @param $titleNo
     * @return array|false
     */
    function GetUndeletedTeachersTicketGroupListWithTitleNo($teacherId, $titleNo)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *
FROM {$this->_tableName} 
WHERE 
  teacher_id = CAST(:teacher_id AS DECIMAL(20)) AND
  status < :status AND
  title_no = :title_no
ORDER BY
  id DESC
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':teacher_id', $teacherId, \PDO::PARAM_STR);
        $sth->bindValue(':status', LmsTicketGroup::STATUS_DELETE_BY_TEACHER);
        $sth->bindValue(':title_no', $titleNo);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $lmsTicketIds
     * @return array|false
     */
    function GetUseCountsByLmsTicketIds($lmsTicketIds)
    {
        $bindKeyArray = [];
        for ($i=0; $i<count($lmsTicketIds); ++$i)
        {
            $bindKeyArray[] = "CAST(:key_{$i} AS DECIMAL(20))";
        }

        $bindKeys = implode(',', $bindKeyArray);

        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT lms_ticket_id, SUM(use_count) AS use_count_total
FROM {$this->_tableName} 
WHERE lms_ticket_id IN ({$bindKeys}) 
GROUP BY lms_ticket_id
SQL;

        $sth = $pdo->prepare($sql);
        for ($i=0; $i<count($lmsTicketIds); ++$i)
        {
            $sth->bindValue(":key_{$i}", $lmsTicketIds[$i], \PDO::PARAM_STR);
        }

        PDOHelper::ExecuteWithTry($sth);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}