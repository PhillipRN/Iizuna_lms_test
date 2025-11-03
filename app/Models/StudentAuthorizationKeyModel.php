<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class StudentAuthorizationKeyModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'student_authorization_key';
    }

    /**
     * @param $authorizationKey
     * @return mixed
     */
    public function GetByAuthorizationKey($authorizationKey)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *  
FROM {$this->_tableName} 
WHERE
  authorization_key = :authorization_key AND
  expire_date > :expire_date
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':authorization_key', $authorizationKey);
        $sth->bindValue(':expire_date', date("Y-m-d H:i:s"));

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    public function DeleteExpiredData()
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
DELETE FROM {$this->_tableName} 
WHERE expire_date < :expire_date
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':expire_date', date("Y-m-d H:i:s"));

        return PDOHelper::ExecuteWithTry($sth);
    }
}