<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class ModelBase
{
    const PDO_MODE_IIZUNA_LMS = 'pdo_mode_iizuna_lms';
    const PDO_MODE_ONIGIRI = 'pdo_mode_onigiri';
    protected $_tableName;
    protected $_pdoMode = self::PDO_MODE_IIZUNA_LMS;

    /**
     * @return \PDO
     */
    protected function GetPDO()
    {
        return ($this->_pdoMode == self::PDO_MODE_IIZUNA_LMS) ? PDOHelper::GetPDO() : PDOHelper::GetOnigiriPDO();
    }

    protected function CreateInsertSql($data)
    {
        $sqlKeys = [];
        $sqlValues = [];
        foreach ($data as $key => $val)
        {
            if ($key == 'id') continue;

            $sqlKeys[] = $key;
            $sqlValues[] = ":$key";
        }

        $sqlKeysString = implode(', ', $sqlKeys);
        $sqlValuesString = implode(', ', $sqlValues);

        $tableName = $this->_tableName;

        return <<<SQL
INSERT INTO
{$tableName} 
({$sqlKeysString}) 
VALUES 
({$sqlValuesString})
SQL;
    }

    protected function CreateMultiInsertSql($dataArray)
    {
        $sqlKeys = [];
        $sqlValues = [];
        foreach ($dataArray as $index => $data)
        {
            $tmpSqlValues = [];
            foreach ($data as $key => $val)
            {
                if ($key == 'id') continue;

                if ($index == 0) $sqlKeys[] = $key;

                $tmpSqlValues[] = ":{$key}_{$index}";
            }
            $sqlValues[] = '(' . implode(',', $tmpSqlValues) . ')';
        }

        $sqlKeysString = implode(', ', $sqlKeys);
        $sqlValuesString = implode(', ', $sqlValues);

        $tableName = $this->_tableName;

        return <<<SQL
INSERT INTO
{$tableName} 
({$sqlKeysString}) 
VALUES 
{$sqlValuesString}
SQL;
    }


    /**
     * @param $data
     * @return bool
     */
    public function Add($data)
    {
        $sql = $this->CreateInsertSql($data);
        $pdo = $this->GetPDO();
        $sth = $pdo->prepare($sql);

        foreach ($data as $key => $val)
        {
            if ($key == 'id') continue;

            if ($key == 'create_date' || $key == 'update_date') $val = date("Y-m-d H:i:s");

            $sth->bindValue(":$key", $val);
        }

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param $data
     * @return boolean
     */
    public function Update($data): bool
    {
        $sql = $this->CreateUpdateSql($data);
        $pdo = $this->GetPDO();
        $sth = $pdo->prepare($sql);

        foreach ($data as $key => $val)
        {
            if ($key == 'create_date') continue;

            if (($key == 'update_date') && empty($val))
            {
                $val = date("Y-m-d H:i:s");
            }

            if ($key == 'id')
            {
                $sth->bindValue(":id", $val, \PDO::PARAM_STR);
            }
            else {
                $sth->bindValue(":$key", $val);
            }
        }

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param array $jobHistoryArray
     * @return bool
     */
    public function MultipleAdd(array $jobHistoryArray)
    {
        $sql = $this->CreateMultiInsertSql($jobHistoryArray);
        $pdo = $this->GetPDO();
        $sth = $pdo->prepare($sql);

        foreach ($jobHistoryArray as $index => $data)
        {
            foreach ($data as $key => $val)
            {
                if ($key == 'id') continue;

                if ($key == 'create_date' || $key == 'update_date') $val = date("Y-m-d H:i:s");

                $sth->bindValue(":{$key}_{$index}", $val);
            }
        }

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param $column
     * @return int
     */
    function Count($column='id')
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  count({$column}) as cnt 
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
    function GetById($id)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
WHERE id = CAST(:id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':id', $id, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    function GetByKeyValue($key, $value)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
WHERE {$key} = :{$key} 
LIMIT 1
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(":{$key}", $value);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    function GetsByKeyInValues($key, $values, $orders=[])
    {
        $pdo = $this->GetPDO();

        $bindKeyArray = [];
        $orderSql = '';

        for ($i=0; $i<count($values); ++$i)
        {
            $bindKeyArray[] = ":key_{$i}";
        }

        $bindKeys = implode(',', $bindKeyArray);

        if (!empty($orders))
        {
            $tmpOrders = [];
            foreach ($orders as $orderKey => $orderValue)
            {
                $tmpOrders[] = "{$orderKey} {$orderValue}";
            }

            $orderSql = 'ORDER BY ' . implode(', ', $tmpOrders);
        }

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
WHERE {$key} IN ({$bindKeys}) 
{$orderSql}
SQL;

        $sth = $pdo->prepare($sql);

        for ($i=0; $i<count($values); ++$i)
        {
            $sth->bindValue(":key_{$i}", $values[$i]);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    function GetsByKeyValue($key, $value, $orders=[])
    {
        $pdo = $this->GetPDO();

        $orderSql = '';

        if (!empty($orders))
        {
            $tmpOrders = [];
            foreach ($orders as $orderKey => $orderValue)
            {
                $tmpOrders[] = "{$orderKey} {$orderValue}";
            }

            $orderSql = 'ORDER BY ' . implode(', ', $tmpOrders);
        }

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
WHERE {$key} = :{$key} 
{$orderSql}
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(":{$key}", $value);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    function GetsByKeyValues($keys, $values, array $columns=[], $orders=[])
    {
        $pdo = $this->GetPDO();
        $tmpWheres = [];
        $orderSql = '';

        for ($i=0; $i<count($keys); ++$i)
        {
            $tmpWheres[] = "{$keys[$i]} = :{$keys[$i]} ";
        }

        $whereSql = implode(' AND ', $tmpWheres);

        $queryColumns =
            (empty($columns))
                ? '*'
                : implode(',', $columns);

        if (!empty($orders))
        {
            $tmpOrders = [];
            foreach ($orders as $orderKey => $orderValue)
            {
                $tmpOrders[] = "{$orderKey} {$orderValue}";
            }

            $orderSql = 'ORDER BY ' . implode(', ', $tmpOrders);
        }

        $sql = <<<SQL
SELECT 
  {$queryColumns} 
FROM {$this->_tableName} 
WHERE {$whereSql} 
{$orderSql}
SQL;

        $sth = $pdo->prepare($sql);
        for ($i=0; $i<count($keys); ++$i)
        {
            $sth->bindValue(":{$keys[$i]}", $values[$i]);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    function GetByKeyValues($keys, $values, $orders=[])
    {
        $pdo = $this->GetPDO();
        $tmpWheres = [];

        for ($i=0; $i<count($keys); ++$i)
        {
            $tmpWheres[] = "{$keys[$i]} = :{$keys[$i]} ";
        }

        $whereSql = implode(' AND ', $tmpWheres);

        $orderSql = '';

        if (!empty($orders))
        {
            $tmpOrders = [];
            foreach ($orders as $key => $val)
            {
                $tmpOrders[] = "{$key} {$val}";
            }

            $orderSql = 'ORDER BY ' . implode(', ', $tmpOrders);
        }

        $queryColumns =
            (empty($columns))
                ? '*'
                : implode(',', $columns);

        $sql = <<<SQL
SELECT 
  {$queryColumns} 
FROM {$this->_tableName} 
WHERE {$whereSql} 
{$orderSql}
LIMIT 1
SQL;

        $sth = $pdo->prepare($sql);
        for ($i=0; $i<count($keys); ++$i)
        {
            $sth->bindValue(":{$keys[$i]}", $values[$i]);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    function Gets($orders=[])
    {
        $pdo = $this->GetPDO();

        $orderSql = '';

        if (!empty($orders))
        {
            $tmpOrders = [];
            foreach ($orders as $key => $val)
            {
                $tmpOrders[] = "{$key} {$val}";
            }

            $orderSql = 'ORDER BY ' . implode(', ', $tmpOrders);
        }

        $sql = <<<SQL
SELECT 
  *
FROM {$this->_tableName} 
{$orderSql}
SQL;

        $sth = $pdo->prepare($sql);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param array $columns
     * @return array|false
     */
    function GetsAll(array $columns=[], $sortKey='id')
    {
        $pdo = $this->GetPDO();

        $queryColumns =
            (empty($columns))
                ? '*'
                : implode(',', $columns);

        $sql = <<<SQL
SELECT 
  {$queryColumns}
FROM {$this->_tableName} 
ORDER BY {$sortKey} ASC
SQL;

        $sth = $pdo->prepare($sql);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function CreateUpdateSql($data)
    {
        $sqlItems = [];
        foreach ($data as $key => $val)
        {
            if ($key == 'create_date' || $key == 'id') continue;

            $sqlItems[] = "{$key}=:{$key}";
        }

        $sqlItemsString = implode(', ', $sqlItems);

        $tableName = $this->_tableName;

        return <<<SQL
UPDATE {$tableName} SET {$sqlItemsString} WHERE id=CAST(:id AS DECIMAL(20))
SQL;
    }

    /**
     * @return bool
     */
    function DeleteByKeyValue($key, $value)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
DELETE FROM {$this->_tableName} 
WHERE {$key} = :{$key} 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(":{$key}", $value);

        return PDOHelper::ExecuteWithTry($sth);
    }
}