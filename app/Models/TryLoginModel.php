<?php
namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class TryLoginModel
{
    private $_tableName = "try_login";

    /**
     * @param $userId
     * @return array
     */
    public function GetByUserId($userId)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT *  
FROM {$this->_tableName} 
WHERE
  user_id = :user_id AND
  expire_date > :expire_date
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':user_id', $userId);
        $sth->bindValue(':expire_date', date("Y-m-d H:i:s"));

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $ip
     * @param $isAdmin
     * @return mixed
     */
    public function GetByIp($ip, $isAdmin)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT *  
FROM {$this->_tableName} 
WHERE
  ip = :ip AND 
  is_admin = :is_admin AND
  expire_date > :expire_date
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':ip', $ip);
        $sth->bindValue(':is_admin', ($isAdmin) ? 1 : 0);
        $sth->bindValue(':expire_date', date("Y-m-d H:i:s"));

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $userId
     * @param $ip
     * @param $isAdmin
     * @param $expireDate
     * @return bool
     */
    public function Add($userId, $ip, $isAdmin, $expireDate)
    {
        $sql = <<<SQL
INSERT INTO
{$this->_tableName}  
(user_id, ip, count, is_admin, expire_date, create_date) VALUES
(:user_id, :ip, :count, :is_admin, :expire_date, :create_date)
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);
        $sth->bindValue(':user_id', $userId);
        $sth->bindValue(':ip', $ip);
        $sth->bindValue(':count', 1);
        $sth->bindValue(':is_admin', ($isAdmin) ? 1 : 0);
        $sth->bindValue(':expire_date', $expireDate);
        $sth->bindValue(':create_date', date("Y-m-d H:i:s"));

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param $id
     * @return bool
     */
    public function CountUpById($id)
    {
        $sql = <<<SQL
UPDATE {$this->_tableName}
SET
  count = count + 1 
WHERE
  id = :id 
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);
        $sth->bindValue(':id', $id);

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param $ip
     * @return bool
     */
    public function DeleteByUserId($userId)
    {
        $sql = <<<SQL
DELETE FROM {$this->_tableName}
WHERE user_id = :user_id 
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);
        $sth->bindValue(':user_id', $userId);

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param $ip
     * @param $isAdmin
     * @return bool
     */
    public function DeleteByIp($ip, $isAdmin)
    {
        $sql = <<<SQL
DELETE FROM {$this->_tableName}
WHERE
  ip = :ip AND 
  is_admin = :is_admin
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);
        $sth->bindValue(':ip', $ip);
        $sth->bindValue(':is_admin', ($isAdmin) ? 1 : 0);

        return PDOHelper::ExecuteWithTry($sth);
    }
}