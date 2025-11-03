<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class StudentRefreshTokenModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'student_refresh_token';
    }

    /**
     * @param $refreshToken
     * @return mixed
     */
    public function GetByRefreshToken($refreshToken)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *  
FROM {$this->_tableName} 
WHERE
  refresh_token = :refresh_token AND
  expire_date > :expire_date
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':refresh_token', $refreshToken);
        $sth->bindValue(':expire_date', date("Y-m-d H:i:s"));

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }
}