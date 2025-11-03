<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class TeacherEbookModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'teacher_ebook';
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
    public function AddTeacherBooks($teacherId, $titleNos): bool
    {
        $sqlValuesArray = [];

        foreach ($titleNos as $key => $val)
        {
            $sqlValuesArray[] = "(CAST(:teacher_id_{$key} AS DECIMAL(20)), :title_no_{$key})";
        }

        $sqlValues = implode(",", $sqlValuesArray);

        $sql = <<<SQL
INSERT INTO
{$this->_tableName} 
(teacher_id, title_no) VALUES
{$sqlValues}
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);

        foreach ($titleNos as $key => $val)
        {
            $sth->bindValue(":teacher_id_{$key}", $teacherId, \PDO::PARAM_STR);
            $sth->bindValue(":title_no_{$key}", $val);
        }

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param $teacherId
     * @param $titleNos
     * @return bool
     */
    public function DeleteTeacherBooks($teacherId, $titleNos): bool
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