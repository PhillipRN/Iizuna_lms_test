<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;
use PDO;

class StudentModel extends ModelBase
{
    private $keys = 'id, contact_user_id, school_name, school_grade, school_class, student_number, name, nickname, onigiri_user_id, ebook_user_id, login_id, is_change_password';

    function __construct() {
        $this->_tableName = 'student';
    }

    /**
     * @param $onigiriUserId
     * @return array
     */
    function GetStudentByOnigiriUserId($onigiriUserId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  {$this->keys} 
FROM {$this->_tableName} 
WHERE 
  onigiri_user_id = :onigiri_user_id
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':onigiri_user_id', $onigiriUserId);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $ebookUserId
     * @return array
     */
    function GetStudentByEbookUserId($ebookUserId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  {$this->keys} 
FROM {$this->_tableName} 
WHERE 
  ebook_user_id = :ebook_user_id
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':ebook_user_id', $ebookUserId);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $loginId
     * @param $password
     * @return mixed
     */
    function GetWithLoginIdAndPassword($loginId, $password)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  {$this->keys} 
FROM {$this->_tableName} 
WHERE 
  login_id = :login_id AND 
  password = :password 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':login_id', $loginId);
        $sth->bindValue(':password', $password);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * 同姓同名の生徒が存在するか確認する
     *
     * @param string $name 確認する生徒名
     * @param int $lmsCodeId LMSコードID
     * @return array 同姓同名の生徒データの配列
     */
    public function CheckDuplicateNames($name, $lmsCodeId)
    {
        // 生徒名でマッチする生徒を検索
        $sql = "
        SELECT 
            s.*
        FROM 
            {$this->_tableName} s
        INNER JOIN
            student_lms_code slc ON s.id = slc.student_id
        WHERE 
            s.name = :name
            AND slc.lms_code_id = :lms_code_id
    ";

        $params = [
            ':name' => $name,
            ':lms_code_id' => $lmsCodeId
        ];

        $pdo = $this->GetPDO();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * ログインIDで生徒を取得する
     *
     * @param string $loginId ログインID
     * @return array|null 生徒データ
     */
    public function GetStudentByLoginId($loginId)
    {
        $sql = "SELECT * FROM {$this->_tableName} WHERE login_id = :login_id LIMIT 1";
        $params = [':login_id' => $loginId];

        $pdo = $this->GetPDO();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : null;
    }
}