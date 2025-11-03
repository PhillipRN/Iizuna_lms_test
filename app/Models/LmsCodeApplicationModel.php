<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class LmsCodeApplicationModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'lms_code_application';
    }

    /**
     * @param $id
     * @return mixed
     */
    public function Get($id)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  lca.*,
  sg.name AS group_name,
  t.name_1 AS teacher_name_1, t.name_2 AS teacher_name_2,
  s.name AS school_name
FROM {$this->_tableName} AS lca 
INNER JOIN school_group AS sg ON lca.lms_code_id = sg.lms_code_id
INNER JOIN teacher AS t ON sg.teacher_id = t.id
INNER JOIN school AS s ON t.school_id = s.id
WHERE lca.id = CAST(:id AS DECIMAL(20))
LIMIT 1 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':id', $id, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    function GetsByLimitAndOffset($limit, $offset)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  lca.*,
  sg.name AS group_name,
  t.name_1 AS teacher_name_1, t.name_2 AS teacher_name_2,
  s.name AS school_name
FROM {$this->_tableName} AS lca 
INNER JOIN school_group AS sg ON lca.lms_code_id = sg.lms_code_id
INNER JOIN teacher AS t ON sg.teacher_id = t.id
INNER JOIN school AS s ON t.school_id = s.id
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