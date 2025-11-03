<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class LogModel
{
    private $_tableName = "log";

    /**
     * @param $userId
     * @return array
     */
    public function GetsByUserId($userId)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT *  
FROM {$this->_tableName} 
WHERE user_id = :user_id 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':user_id', $userId);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $userId
     * @param $action
     * @param $parameterJson
     * @param $userAgent
     * @return bool
     */
    public function Add($userId, $action, $parameterJson, $userAgent)
    {
        $sql = <<<SQL
INSERT INTO
{$this->_tableName}  
(user_id, action, parameter_json, user_agent, create_date) VALUES
(:user_id, :action, :parameter_json, :user_agent, :create_date)
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);
        $sth->bindValue(':user_id', $userId);
        $sth->bindValue(':action', $action);
        $sth->bindValue(':parameter_json', $parameterJson);
        $sth->bindValue(':user_agent', $userAgent);
        $sth->bindValue(':create_date', date("Y-m-d H:i:s"));

        return PDOHelper::ExecuteWithTry($sth);
    }
}