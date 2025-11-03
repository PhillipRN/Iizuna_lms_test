<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

/**
 * Interface ITeacherBookModel
 */
interface ITeacherBookModel
{
    public function GetsByTeacherId($teacherId);
    public function AddTeacherBooks($teacherId, $titleNos);
    public function DeleteTeacherBooks($teacherId, $titleNos);
}

class TeacherBookModel extends ModelBase implements ITeacherBookModel
{
    private $_tableNameJoin = 'book';

    function __construct() {
        $this->_tableName = 'teacher_book';
    }

    /**
     * @param $teacherId
     * @param $titleNo
     * @return bool
     */
    function IsRegisterd($teacherId, $titleNo)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
WHERE
  teacher_id = CAST(:teacher_id AS DECIMAL(20)) AND
  title_no = :title_no
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':teacher_id', $teacherId, \PDO::PARAM_STR);
        $sth->bindValue(':title_no', $titleNo);

        PDOHelper::ExecuteWithTry($sth);

        $row = $sth->fetch(\PDO::FETCH_ASSOC);

        return !empty($row);
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
     * @return mixed
     */
    function GetBooksWithNameByTeacherId($teacherId)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  {$this->_tableName}.*,
  {$this->_tableNameJoin}.name
FROM {$this->_tableName} 
LEFT JOIN {$this->_tableNameJoin} 
  ON {$this->_tableName}.title_no = {$this->_tableNameJoin}.title_no 
WHERE {$this->_tableName}.teacher_id = CAST(:teacher_id AS DECIMAL(20)) 
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
    public function AddTeacherBook($teacherId, $titleNo)
    {
        $sql = <<<SQL
INSERT INTO
{$this->_tableName} 
(teacher_id, title_no, update_date) VALUES
(CAST(:teacher_id AS DECIMAL(20)), :title_no, :update_date)
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);

        $sth->bindValue(':teacher_id', $teacherId, \PDO::PARAM_STR);
        $sth->bindValue(':title_no', $titleNo);
        $sth->bindValue(':update_date', date("Y-m-d H:i:s"));

        return PDOHelper::ExecuteWithTry($sth);
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
            $sqlValuesArray[] = "(CAST(:teacher_id_{$key} AS DECIMAL(20)), :title_no_{$key}, :update_date_{$key})";
        }

        $sqlValues = implode(",", $sqlValuesArray);

        $sql = <<<SQL
INSERT INTO
{$this->_tableName} 
(teacher_id, title_no, update_date) VALUES
{$sqlValues}
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);

        $updateDate = date("Y-m-d H:i:s");

        foreach ($titleNos as $key => $val)
        {
            $sth->bindValue(":teacher_id_{$key}", $teacherId, \PDO::PARAM_STR);
            $sth->bindValue(":title_no_{$key}", $val);
            $sth->bindValue(":update_date_{$key}", $updateDate);
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
}