<?php
namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class RegistrationModel
{
    private $_tableName;
    private $_tableNameJoin = "book";

    function __construct() {
        $this->_tableName = "registration_key";
    }

    /**
     * @return array
     */
    function GetsByLimitAndOffset($limit, $offset)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  {$this->_tableName}.*,
  {$this->_tableNameJoin}.name
FROM {$this->_tableName} 
LEFT JOIN {$this->_tableNameJoin} 
  ON {$this->_tableName}.title_no = {$this->_tableNameJoin}.title_no
ORDER BY {$this->_tableName}.id DESC 
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
     * @return int
     */
    function Count()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  count(id) as cnt 
FROM {$this->_tableName} 
SQL;

        $sth = $pdo->prepare($sql);
        PDOHelper::ExecuteWithTry($sth);

        $row = $sth->fetch(\PDO::FETCH_ASSOC);
        return (int)$row["cnt"];
    }

    /**
     * @param $id
     * @return mixed
     */
    function GetByid($id)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
WHERE id = :id 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':id', $id);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $hashKey
     * @return mixed
     */
    function GetByHashKey($hashKey)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
WHERE hash_key = :hash_key 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':hash_key', $hashKey);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $titleNo
     * @param $hashKey
     * @return bool
     */
    public function AddRecord($titleNo, $hashKey)
    {
        $sql = <<<SQL
INSERT INTO
{$this->_tableName}  
(title_no, hash_key, status, create_date, update_date) VALUES
(:title_no, :hash_key, :status, :create_date, :update_date)
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);

        $sth->bindValue(':title_no', $titleNo);
        $sth->bindValue(':hash_key', $hashKey);
        $sth->bindValue(':status', 0);
        $sth->bindValue(':create_date', date("Y-m-d H:i:s"));
        $sth->bindValue(':update_date', date("Y-m-d H:i:s"));

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param $id
     * @param $status
     * @param $registerTeacher
     * @return bool
     */
    public function UpdateRecordStatus($id, $status, $registerTeacher)
    {
        $sql = <<<SQL
UPDATE {$this->_tableName}
SET
  status = :status,
  regist_teacher_id = CAST(:regist_teacher_id AS DECIMAL(20)), 
  update_date = :update_date
WHERE
  id = :id 
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);

        $sth->bindValue(':id', $id);
        $sth->bindValue(':status', $status);
        $sth->bindValue(':regist_teacher_id', $registerTeacher, \PDO::PARAM_STR);
        $sth->bindValue(':update_date', date("Y-m-d H:i:s"));

        return PDOHelper::ExecuteWithTry($sth);
    }
}