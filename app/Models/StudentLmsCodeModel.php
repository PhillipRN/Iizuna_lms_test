<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class StudentLmsCodeModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'student_lms_code';
    }

    /**
     * @param $studentId
     * @return bool
     */
    function DeleteByStudentId($studentId): bool
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
DELETE FROM {$this->_tableName} 
WHERE student_id = CAST(:student_id AS DECIMAL(20))
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':student_id', $studentId, \PDO::PARAM_STR);

        return PDOHelper::ExecuteWithTry($sth);
    }

    /**
     * 生徒IDとLMSコードIDの組み合わせが既に存在するか確認
     *
     * @param int $studentId 生徒ID
     * @param int $lmsCodeId LMSコードID
     * @return bool 存在する場合はtrue
     */
    public function Exists($studentId, $lmsCodeId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  COUNT(*) as count
FROM {$this->_tableName} 
WHERE student_id = :student_id AND lms_code_id = :lms_code_id
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':student_id', $studentId, \PDO::PARAM_INT);
        $sth->bindValue(':lms_code_id', $lmsCodeId, \PDO::PARAM_INT);

        PDOHelper::ExecuteWithTry($sth);
        $result = $sth->fetch(\PDO::FETCH_ASSOC);

        return $result['count'] > 0;
    }

    /**
     * 生徒IDとLMSコードIDの関連を追加
     *
     * @param int $studentId 生徒ID
     * @param int $lmsCodeId LMSコードID
     * @return bool 成功時はtrue
     */
    public function AddRelation($studentId, $lmsCodeId)
    {
        $data = [
            'student_id' => $studentId,
            'lms_code_id' => $lmsCodeId,
            'create_date' => date("Y-m-d H:i:s")
        ];

        return $this->Add($data);
    }

    /**
     * LMSコードIDに基づいて関連する生徒を取得
     *
     * @param int $lmsCodeId LMSコードID
     * @return array 生徒ID配列
     */
    public function GetStudentIdsByLmsCodeId($lmsCodeId)
    {
        $pdo = $this->GetPDO();

        $sql = <<<SQL
SELECT 
  student_id
FROM {$this->_tableName} 
WHERE lms_code_id = :lms_code_id
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':lms_code_id', $lmsCodeId, \PDO::PARAM_INT);

        PDOHelper::ExecuteWithTry($sth);

        $results = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $studentIds = [];

        foreach ($results as $result) {
            $studentIds[] = $result['student_id'];
        }

        return $studentIds;
    }
}