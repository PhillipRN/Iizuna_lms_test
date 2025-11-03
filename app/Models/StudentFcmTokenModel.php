<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class StudentFcmTokenModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'student_fcm_token';
    }

    /**
     * @param $studentId
     * @param $fcmToken
     * @param $expireDate
     * @return bool
     */
    public function UpdateExpireDate($studentId, $fcmToken, $expireDate): bool
    {
        $sql = <<<SQL
UPDATE {$this->_tableName}
SET
  expire_date = :expire_date 
WHERE
  student_id = CAST(:student_id AS DECIMAL(20)) AND 
  fcm_token = :fcm_token 
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);
        $sth->bindValue(':expire_date', $expireDate);
        $sth->bindValue(':student_id', $studentId, \PDO::PARAM_STR);
        $sth->bindValue(':fcm_token', $fcmToken);

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @param $studentId
     * @param $fcmToken
     * @return bool
     */
    public function IncrementFailedCount($studentId, $fcmToken)
    {
        $sql = <<<SQL
UPDATE {$this->_tableName}
SET
  failed_count = failed_count + 1 
WHERE
  student_id = CAST(:student_id AS DECIMAL(20)) AND 
  fcm_token = :fcm_token 
SQL;

        $pdo = PDOHelper::GetPDO();
        $sth = $pdo->prepare($sql);
        $sth->bindValue(':student_id', $studentId, \PDO::PARAM_STR);
        $sth->bindValue(':fcm_token', $fcmToken);

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @return bool
     */
    public function DeleteExpiredData()
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
DELETE FROM {$this->_tableName} 
WHERE expire_date < :expire_date
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':expire_date', date("Y-m-d H:i:s"));

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * @return bool
     */
    public function DeleteFailedData()
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
DELETE FROM {$this->_tableName} 
WHERE failed_count > 0
SQL;

        $sth = $pdo->prepare($sql);

        return PDOHelper::ExecuteWithTry($sth);
    }
}