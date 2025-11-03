<?php
namespace IizunaLMS\Students;

use IizunaLMS\Helpers\PageHelper;
use IizunaLMS\Helpers\PDOHelper;
use PDO;
use PDOException;

class StudentLoader
{
    // プリペアドステートメントを保存するためのプロパティ
    private $pdo;

    public function __construct()
    {
        $this->pdo = PDOHelper::GetPDO();
    }

    /**
     * LMSコードIDに基づいて生徒のリストを取得する
     *
     * @param int $lmsCodeId LMSコードID
     * @return array 生徒データの配列
     * @throws PDOException データベースエラー時
     */
    public function GetStudentsByLmsCodeId($lmsCodeId)
    {
        $sql = "
            SELECT 
              slc.lms_code_id,
              slc.student_id,
              s.name AS student_name, 
              s.student_number,
              s.login_id
            FROM student_lms_code slc
            INNER JOIN student AS s ON s.id = slc.student_id 
            WHERE slc.lms_code_id = CAST(:lms_code_id AS DECIMAL(20)) 
            ORDER BY student_name ASC
        ";

        return $this->executeQuery($sql, [
            ':lms_code_id' => [$lmsCodeId, PDO::PARAM_STR]
        ]);
    }

    /**
     * LMSコードIDとページ番号に基づいて生徒のリストを取得する
     *
     * @param int $lmsCodeId LMSコードID
     * @param int $page ページ番号
     * @param int $limit ページ最大表示数
     * @param array $sortParams ソートパラメータ
     * @return array 生徒データの配列
     * @throws PDOException データベースエラー時
     */
    public function getStudentsByLmsCodeIdAndPage($lmsCodeId, $page, $limit, $sortParams = [])
    {
        $offset = PageHelper::CalculateOffset($page, $limit);
        $orders = $this->buildOrderClause($sortParams);

        $sql = "
        SELECT 
          slc.lms_code_id,
          slc.student_id,
          s.name AS student_name, 
          s.student_number,
          s.login_id,
          s.school_name,
          s.school_grade,
          s.school_class,
          v.lms_code_ids,
          v.lms_code_names
        FROM student_lms_code slc
        INNER JOIN student AS s ON s.id = slc.student_id 
        INNER JOIN student_with_lms_codes_view v ON v.student_id = slc.student_id
        WHERE slc.lms_code_id = CAST(:lms_code_id AS DECIMAL(20)) 
        ORDER BY {$orders}
        LIMIT :limit 
        OFFSET :offset 
    ";

        return $this->executeQuery($sql, [
            ':lms_code_id' => [$lmsCodeId, PDO::PARAM_STR],
            ':limit' => [$limit, PDO::PARAM_INT],
            ':offset' => [$offset, PDO::PARAM_INT]
        ]);
    }

    /**
     * LMSコードIDに基づく最大ページ数を取得する
     *
     * @param int $lmsCodeId LMSコードID
     * @param int $limit ページ最大表示数
     * @return int 最大ページ数
     * @throws PDOException データベースエラー時
     */
    public function getMaxPageNumber($lmsCodeId, $limit)
    {
        $sql = "
            SELECT 
              COUNT(student_id) AS number
            FROM student_lms_code
            WHERE lms_code_id = CAST(:lms_code_id AS DECIMAL(20)) 
        ";

        $result = $this->executeQuery($sql, [
            ':lms_code_id' => [$lmsCodeId, PDO::PARAM_STR]
        ]);

        $record = $result[0] ?? ['number' => 0];
        return PageHelper::CalculateMaxPageNum($record['number'], $limit);
    }

    /**
     * ソートパラメータからORDER BY句を構築する
     *
     * @param array $sortParams ソートパラメータ
     * @return string ORDER BY句
     */
    private function buildOrderClause($sortParams)
    {
        $orders = [];

        if (isset($sortParams['student_number']) &&
            in_array($sortParams['student_number'], ['ASC', 'DESC'], true)) {
            $orders[] = 'student_number ' . $sortParams['student_number'];
        }

        $orders[] = 'student_name ASC';

        return implode(', ', $orders);
    }

    /**
     * SQLクエリを実行し、結果を取得する
     *
     * @param string $sql SQLクエリ
     * @param array $params パラメータとそのタイプの配列
     * @return array クエリ結果
     * @throws PDOException データベースエラー時
     */
    private function executeQuery($sql, $params = [])
    {
        $sth = $this->pdo->prepare($sql);

        foreach ($params as $key => [$value, $type]) {
            $sth->bindValue($key, $value, $type);
        }

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }
}