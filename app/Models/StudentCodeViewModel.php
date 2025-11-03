<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PageHelper;
use IizunaLMS\Helpers\PDOHelper;

class StudentCodeViewModel extends ModelBase
{
    function __construct() {
        $this->_tableName = "student_code_view";
    }

    /**
     * @param $teacherId
     * @param $page
     * @return array|false
     */
    public function GetsByTeacherId($teacherId, $page)
    {
        $offset = ($page > 0) ? ($page - 1) * PageHelper::PAGE_LIMIT : 0;
        $limit = PageHelper::PAGE_LIMIT;

        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
WHERE teacher_id = CAST(:teacher_id AS DECIMAL(20))
ORDER BY create_date DESC 
LIMIT :limit 
OFFSET :offset 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':teacher_id', $teacherId, \PDO::PARAM_STR);
        $sth->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $sth->bindValue(':offset', $offset, \PDO::PARAM_INT);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $teacherId
     * @return int
     */
    public function GetMaxPageNumber($teacherId)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  COUNT(id) AS number
FROM {$this->_tableName} 
WHERE teacher_id = CAST(:teacher_id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':teacher_id', $teacherId, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        $record = $sth->fetch(\PDO::FETCH_ASSOC);

        return PageHelper::GetMaxPageNum($record['number']);
    }
}