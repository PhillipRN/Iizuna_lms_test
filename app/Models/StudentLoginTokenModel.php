<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class StudentLoginTokenModel extends ModelBase implements IStudentLoginTokenModel
{
    function __construct() {
        $this->_tableName = 'student_login_token';
    }

    /**
     * @param $loginToken
     * @return mixed
     */
    public function GetByLoginToken($loginToken)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *  
FROM {$this->_tableName} 
WHERE
  login_token = :login_token AND
  expire_date > :expire_date
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':login_token', $loginToken);
        $sth->bindValue(':expire_date', date("Y-m-d H:i:s"));

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }
}