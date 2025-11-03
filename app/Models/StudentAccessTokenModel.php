<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class StudentAccessTokenModel extends ModelBase implements IStudentAccessTokenModel
{
    function __construct() {
        $this->_tableName = 'student_access_token';
    }

    /**
     * @param $accessToken
     * @return mixed
     */
    public function GetByAccessToken($accessToken)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT *  
FROM {$this->_tableName} 
WHERE
  access_token = :access_token AND
  expire_date > :expire_date
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':access_token', $accessToken);
        $sth->bindValue(':expire_date', date("Y-m-d H:i:s"));

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }
}