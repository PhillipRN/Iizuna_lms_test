<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PageHelper;
use IizunaLMS\Helpers\PDOHelper;

class SchoolViewModel extends ModelBase
{
    function __construct() {
        $this->_tableName = "school_view";
    }

    public function GetSchools($keyWords, $page=null)
    {
        $whereString = '';
        $nameWheres = [];

        for ($i=0; $i<count($keyWords); ++$i)
        {
            $nameWheres[] = "name LIKE :name_{$i}";
        }

        if (!empty($nameWheres)) {
            $whereString = 'WHERE (' . implode(' AND ', $nameWheres) . ')';
        }

        if ($page != null) {
            $offset = ($page > 0) ? ($page - 1) * PageHelper::PAGE_LIMIT : 0;
            $limit = PageHelper::PAGE_LIMIT;

            $sql = <<<SQL
SELECT 
 * 
FROM {$this->_tableName} 
{$whereString}
ORDER BY create_date DESC 
LIMIT :limit 
OFFSET :offset 
SQL;

        }
        else {
            $sql = <<<SQL
SELECT 
 * 
FROM {$this->_tableName} 
{$whereString}
ORDER BY create_date DESC 
SQL;
        }
        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);

        for ($i=0; $i<count($keyWords); ++$i)
        {
            $sth->bindValue(":name_{$i}", "%{$keyWords[$i]}%");
        }

        if ($page != null) {
            $sth->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $sth->bindValue(':offset', $offset, \PDO::PARAM_INT);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $keyWords
     * @return mixed
     */
    public function GetRecordCount($keyWords)
    {
        $whereString = '';
        $nameWheres = [];

        for ($i=0; $i<count($keyWords); ++$i)
        {
            $nameWheres[] = "name LIKE :name_{$i}";
        }

        if (!empty($nameWheres)) {
            $whereString = 'WHERE (' . implode(' AND ', $nameWheres) . ')';
        }

        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  Count(id) as record_count
FROM {$this->_tableName} 
{$whereString}
SQL;

        $sth = $pdo->prepare($sql);

        for ($i=0; $i<count($keyWords); ++$i)
        {
            $sth->bindValue(":name_{$i}", "%{$keyWords[$i]}%");
        }

        PDOHelper::ExecuteWithTry($sth);

        $record = $sth->fetch(\PDO::FETCH_ASSOC);

        return $record['record_count'];
    }
}