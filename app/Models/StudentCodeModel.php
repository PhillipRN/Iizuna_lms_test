<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class StudentCodeModel extends ModelBase
{
    function __construct() {
        $this->_tableName = "student_code";
    }

    /**
     * @param $ids
     * @return bool
     */
    public function DeleteStudentCodeIds($ids)
    {
        $sqlValuesArray = [];

        for ($i=0; $i<count($ids); ++$i)
        {
            $tmpWheres[] = "CAST(:id_{$i} AS DECIMAL(20))";
        }

        $whereSql = 'id IN (' . implode(', ', $tmpWheres) . ')';

        $sql = <<<SQL
DELETE FROM {$this->_tableName}  
WHERE {$whereSql}
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);
        for ($i=0; $i<count($ids); ++$i)
        {
            $sth->bindValue(":id_{$i}", $ids[$i], \PDO::PARAM_STR);
        }

        return PDOHelper::ExecuteWithTry($sth);
    }
}