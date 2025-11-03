<?php
namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

require_once('TeacherBookModel.php'); // ITeacherBookModel を参照できずにエラーになってしまうため直接読み込む

class TeacherBookTempModel extends ModelBase implements ITeacherBookModel
{
    private $_tableNameJoinTeacher = 'teacher_school_view';
    private $_tableNameJoinKey = 'registration_key';
    private $_tableNameJoinBook = 'book';

    const STATUS_REGISTERED = 1;

    function __construct() {
        $this->_tableName = 'teacher_book_temp';
    }

    /**
     * @return int
     */
    function CountTeacherId()
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  count(teacher_id) as cnt 
FROM {$this->_tableName} 
SQL;

        $sth = $pdo->prepare($sql);
        PDOHelper::ExecuteWithTry($sth);

        $row = $sth->fetch(\PDO::FETCH_ASSOC);
        return (int)$row["cnt"];
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
  {$this->_tableNameJoinTeacher}.school_name,
  {$this->_tableNameJoinTeacher}.name_1,
  {$this->_tableNameJoinTeacher}.name_2,
  {$this->_tableNameJoinKey}.id AS key_id,
  {$this->_tableNameJoinKey}.hash_key,
  {$this->_tableNameJoinKey}.status AS key_status,
  {$this->_tableNameJoinBook}.name
FROM {$this->_tableName} 
LEFT JOIN {$this->_tableNameJoinTeacher} 
  ON {$this->_tableName}.teacher_id = {$this->_tableNameJoinTeacher}.id
LEFT JOIN {$this->_tableNameJoinKey} 
  ON {$this->_tableName}.registration_key_id = {$this->_tableNameJoinKey}.id
LEFT JOIN {$this->_tableNameJoinBook} 
  ON {$this->_tableName}.title_no = {$this->_tableNameJoinBook}.title_no
ORDER BY {$this->_tableName}.create_date DESC 
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
     * @param $teacherId
     * @return mixed
     */
    function GetsByTeacherId($teacherId)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
WHERE teacher_id = CAST(:teacher_id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':teacher_id', $teacherId, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $teacherId
     * @param $titleNos
     * @return bool
     */
    public function AddTeacherBooks($teacherId, $titleNos)
    {
        $sqlValuesArray = [];

        foreach ($titleNos as $key => $val)
        {
            $sqlValuesArray[] = "(CAST(:teacher_id_{$key} AS DECIMAL(20)), :title_no_{$key}, :create_date_{$key}, :create_date_{$key})";
        }

        $sqlValues = implode(",", $sqlValuesArray);

        $sql = <<<SQL
INSERT INTO
{$this->_tableName} 
(teacher_id, title_no, create_date, update_date) VALUES
{$sqlValues}
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);

        $updateDate = date("Y-m-d H:i:s");

        foreach ($titleNos as $key => $val)
        {
            $sth->bindValue(":teacher_id_{$key}", $teacherId, \PDO::PARAM_STR);
            $sth->bindValue(":title_no_{$key}", $val);
            $sth->bindValue(":create_date_{$key}", $updateDate);
        }

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param $teacherId
     * @param $titleNos
     * @return bool
     */
    public function DeleteTeacherBooks($teacherId, $titleNos)
    {
        $sqlValuesArray = [];

        foreach ($titleNos as $key => $val)
        {
            $sqlValuesArray[] = ":title_no_{$key}";
        }

        $sqlValues = implode(",", $sqlValuesArray);

        $sql = <<<SQL
DELETE FROM {$this->_tableName}  
WHERE teacher_id = CAST(:teacher_id AS DECIMAL(20))
  AND title_no IN ($sqlValues)
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);
        $sth->bindValue(':teacher_id', $teacherId, \PDO::PARAM_STR);

        foreach ($titleNos as $key => $val)
        {
            $sth->bindValue(":title_no_{$key}", $val);
        }

        return PDOHelper::ExecuteWithTry($sth);
    }


    /**
     * @param $id
     * @param $status
     * @param $registTeacherId
     * @return bool
     */
    public function SetRegistrationKeyId($teacherId, $titleNo, $registrationKeyId)
    {
        $sql = <<<SQL
UPDATE {$this->_tableName}
SET
  status = :status,
  registration_key_id = :registration_key_id, 
  update_date = :update_date
WHERE
  teacher_id = CAST(:teacher_id AS DECIMAL(20)) AND
  title_no = :title_no 
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);

        $sth->bindValue(':status', self::STATUS_REGISTERED);
        $sth->bindValue(':registration_key_id', $registrationKeyId);
        $sth->bindValue(':teacher_id', $teacherId, \PDO::PARAM_STR);
        $sth->bindValue(':title_no', $titleNo);
        $sth->bindValue(':update_date', date("Y-m-d H:i:s"));

        return PDOHelper::ExecuteWithTry($sth);
    }
}