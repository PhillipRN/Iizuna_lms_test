<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\StringHelper;

class TeacherSchoolModel extends ModelBase
{
    function __construct() {
        $this->_tableName = "teacher_school_view";
    }

    function GetsByLimitAndOffset($limit, $offset)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
ORDER BY id DESC 
LIMIT :limit 
OFFSET :offset 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $sth->bindValue(':offset', $offset, \PDO::PARAM_INT);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $loginId
     * @param $password
     * @return mixed
     */
    function GetWithLoginIdAndPassword($loginId, $password)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  * 
FROM {$this->_tableName} 
WHERE 
  login_id = :login_id AND 
  password = :password 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':login_id', $loginId);
        $sth->bindValue(':password', $password);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    public function GetActiveNoPasswordTeachers()
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  * 
FROM {$this->_tableName} 
WHERE 
  password = :password
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':password', StringHelper::GetHashedString(""));
        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}