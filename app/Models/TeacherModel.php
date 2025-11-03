<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class TeacherModel extends ModelBase
{
    function __construct() {
        $this->_tableName = "teacher";
    }

    /**
     * 指定のID以外でログインIDを持つレコードを取得する
     * @param $loginId
     * @param $id
     * @return array|false
     */
    function GetByLoginIdExceptId($loginId, $id)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT id
FROM {$this->_tableName} 
WHERE
  login_id = :login_id AND
  id != CAST(:id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':login_id', $loginId);
        $sth->bindValue(':id', $id, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    function GetsBySchoolId($schoolId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  id,
  login_id,
  school_id,
  name_1,
  name_2,
  kana_1,
  kana_2
FROM {$this->_tableName} 
WHERE
  school_id = CAST(:school_id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':school_id', $schoolId, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}