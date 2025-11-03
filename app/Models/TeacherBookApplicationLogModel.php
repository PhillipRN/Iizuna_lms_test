<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class TeacherBookApplicationLogModel extends ModelBase
{
    function __construct() {
        $this->_tableName = "teacher_book_application_log";
    }

    /**
     * @param $titleNo
     * @param $teacherId
     * @return bool
     */
    function DeleteTeacherLog($titleNo, $teacherId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
DELETE FROM {$this->_tableName} 
WHERE
  title_no = :title_no AND
  teacher_id = :teacher_id
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':title_no', $titleNo);
        $sth->bindValue(':teacher_id', $teacherId);

        return PDOHelper::ExecuteWithTry($sth);
    }
}