<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\StringHelper;

class UserModel
{
    private $_tableName = "user";

    /**
     * @return array
     */
    public function GetActiveUsers()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  * 
FROM {$this->_tableName} 
WHERE 
  status = :status
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':status', USER_STATUS_ACTIVE);
        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    public function GetActiveNoPasswordUsers()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  * 
FROM {$this->_tableName} 
WHERE 
  status = :status AND
  password = :password
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':status', USER_STATUS_ACTIVE);
        $sth->bindValue(':password', StringHelper::GetHashedString(""));
        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $loginID
     * @param $password
     * @return mixed
     */
    public function GetActiveUserWithPassword($loginID, $password)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT id, login_id 
FROM {$this->_tableName} 
WHERE
  login_id = :login_id AND
  password = :password AND    
  status = :status
LIMIT 1 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':login_id', $loginID);
        $sth->bindValue(':password', StringHelper::GetHashedString($password));
        $sth->bindValue(':status', USER_STATUS_ACTIVE);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function GetById($id)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT * 
FROM {$this->_tableName} 
WHERE id = :id
LIMIT 1 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':id', $id);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $loginID
     * @return mixed
     */
    public function GetActiveUserByLoginId($loginID)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT id, login_id 
FROM {$this->_tableName} 
WHERE
  login_id = :login_id AND
  status = :status
LIMIT 1 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':login_id', $loginID);
        $sth->bindValue(':status', USER_STATUS_ACTIVE);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @param $params
     * @return bool
     */
    public function UpdateUser($id, $params)
    {
        $password = $params['password'] ?? '';
        $sqlSetArray = [];

        foreach ($params as $key => $val)
        {
            if ($key == 'id' || $key == 'password' || !$this->IsValidKey($key)) continue;

            $sqlSetArray[] = "{$key} = :{$key},";
        }

        $sqlSet = implode(" ", $sqlSetArray);
        $sqlPassword = (!empty($password)) ? "password = :password, " : "";

        $sql = <<<SQL
UPDATE {$this->_tableName}
SET
  {$sqlSet}
  {$sqlPassword}
  update_date = :update_date
WHERE
  id = :id 
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);

        $sth->bindValue(':id', $id);
        $sth->bindValue(':update_date', date("Y-m-d H:i:s"));

        if (!empty($password))
        {
            $sth->bindValue(':password', StringHelper::GetHashedString($password));
        }

        foreach ($params as $key => $val)
        {
            if ($key == 'id' || $key == 'password' || !$this->IsValidKey($key)) continue;

            $sth->bindValue(":{$key}", $val);
        }

        return PDOHelper::ExecuteWithTry($sth);
    }

    private $_validKeys = [
        'id',
        'login_id',
        'password',
        'pref',
        'school',
        'sch_zip',
        'sch_add',
        'sch_phone',
        'name1',
        'name1_2',
        'name2',
        'name2_2',
        'mail',
        'gaccount'
    ];

    /**
     * @param $key
     * @return bool
     */
    private function IsValidKey($key)
    {
        return in_array($key, $this->_validKeys);
    }

    /**
     * @param $id
     * @return bool
     */
    public function DeleteUser($id)
    {
        $sql = <<<SQL
UPDATE {$this->_tableName}
SET
  status = :status,
  update_date = :update_date
WHERE
  id = :id 
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);
        $sth->bindValue(':id', $id);
        $sth->bindValue(':status', USER_STATUS_DELETED);
        $sth->bindValue(':update_date', date("Y-m-d H:i:s"));

        PDOHelper::ExecuteWithTry($sth);

        return true;
    }
}