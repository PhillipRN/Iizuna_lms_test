<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class StudentAutoLoginTokenModel extends ModelBase implements IStudentAutoLoginTokenModel
{
    function __construct() {
        $this->_tableName = 'student_auto_login_token';
    }

    /**
     * @param $autoLoginToken
     * @return mixed
     */
    public function GetByAutoLoginToken($autoLoginToken)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *  
FROM {$this->_tableName} 
WHERE
  auto_login_token = :auto_login_token AND
  expire_date > :expire_date
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':auto_login_token', $autoLoginToken);
        $sth->bindValue(':expire_date', date("Y-m-d H:i:s"));

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }
}