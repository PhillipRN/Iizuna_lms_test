<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class TeacherBookApplicationViewModel extends ModelBase
{
    function __construct() {
        $this->_tableName = "teacher_book_application_view";
    }

    function GetsByLimitAndOffset($limit, $offset)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
ORDER BY id DESC 
LIMIT :limit 
OFFSET :offset 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $sth->bindValue(':offset', $offset, \PDO::PARAM_INT);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}