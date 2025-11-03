<?php

namespace IizunaLMS\Teachers;

use IizunaLMS\Helpers\PageHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Models\JsonQuizResultModel;
use IizunaLMS\Students\StudentLoader;

class TeacherResultPageJsonQuizzes
{
    /**
     * @param $lmsCodeId
     * @return float|int
     */
    public function GetMaxPageNumber($lmsCodeId)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  COUNT(json_quiz_id) AS number
FROM json_quiz_delivery jqd
INNER JOIN json_quiz AS jq ON jq.id = jqd.json_quiz_id 
WHERE jqd.lms_code_id = CAST(:lms_code_id AS DECIMAL(20)) 

SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':lms_code_id', $lmsCodeId, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        $record = $sth->fetch(\PDO::FETCH_ASSOC);
        return PageHelper::GetMaxPageNum($record['number']);
    }

    /**
     * @param $lmsCodeId
     * @param $page
     * @return array|null
     */
    public function GetResultData($lmsCodeId)
    {
        $quizzes = $this->GetQuizzes($lmsCodeId);
        if (empty($quizzes)) return null;

        $tmpStudents = (new StudentLoader())->GetStudentsByLmsCodeId($lmsCodeId);

        return $this->CompileData($quizzes, $tmpStudents);
    }

    /**
     * @param $lmsCodeId
     * @param $page
     * @return array|null
     */
    public function GetResultPageData($lmsCodeId, $page)
    {
        $quizzes = $this->GetPageQuizzes($lmsCodeId, $page);
        if (empty($quizzes)) return null;

        $tmpStudents = (new StudentLoader())->GetStudentsByLmsCodeId($lmsCodeId);

        return $this->CompileData($quizzes, $tmpStudents);
    }

    /**
     * @param $quizzes
     * @param $tmpStudents
     * @return array|null
     */
    private function CompileData($quizzes, $tmpStudents)
    {
        $jsonQuizIds = [];
        $studentIds = [];

        foreach ($tmpStudents as $tmpStudent) {
            $studentIds[] = $tmpStudent['student_id'];
        }

        foreach ($quizzes as $quiz) {
            $jsonQuizIds[] = $quiz['json_quiz_id'];
        }

        // 生徒のデータを集める
        $students = [];
        $studentMap = [];

        // 未提出の生徒ばかりだった場合画面に表示されなくなるので、未提出用の生徒のデータも一旦集めマージする
        foreach ($tmpStudents as $tmpStudent) {
            $studentId = $tmpStudent['student_id'];

            if (!isset($students[$studentId])) {
                $students[$studentId] = [
                    'name' => $tmpStudent['student_name'],
                    'student_number' => $tmpStudent['student_number'],
                    'login_id' => $tmpStudent['login_id']
                ];
            }
        }

        unset($tmpStudents);

        $studentResultRecords = $this->GetStudentResults($jsonQuizIds);

        foreach ($studentResultRecords as $studentResult) {
            $studentId = $studentResult['student_id'];
            $jsonQuizId = $studentResult['json_quiz_id'];

            // 対象LMSの生徒以外はスキップ
            if (!in_array($studentId, $studentIds)) continue;

            // 生徒得点
            if (!isset($studentMap[$studentId])) {
                $studentMap[$studentId] = [];
            }

            $studentMap[$studentId][$jsonQuizId] = $studentResult['score'];
        }

        // 生徒データを画面用にまとめる
        $studentResults = [];

        foreach ($students as $studentId => $student) {
            $studentData = [
                'name' => $student['name'],
                'student_number' => $student['student_number'],
                'login_id' => $student['login_id'],
                'results' => []
            ];

            foreach ($jsonQuizIds as $jsonQuizId) {
                $studentData['results'][$jsonQuizId] = (!empty($studentMap[$studentId]) && isset($studentMap[$studentId][$jsonQuizId]))
                    ? $studentMap[$studentId][$jsonQuizId] : null;
            }

            $studentResults[$studentId] = $studentData;
        }

        return [
            'quizzes' => $quizzes,
            'studentResults' => $studentResults
        ];
    }

    /**
     * @param $lmsCodeId
     * @return array|false
     */
    private function GetQuizzes($lmsCodeId)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  jqd.json_quiz_id,
  jqd.lms_code_id,
  jq.open_date,
  jq.title,
  jq.max_score
FROM json_quiz_delivery jqd
INNER JOIN json_quiz AS jq ON jq.id = jqd.json_quiz_id 
WHERE lms_code_id = CAST(:lms_code_id AS DECIMAL(20))
ORDER BY open_date DESC 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':lms_code_id', $lmsCodeId, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $lmsCodeId
     * @param $page
     * @return array|false
     */
    private function GetPageQuizzes($lmsCodeId, $page)
    {
        $offset = ($page > 0) ? ($page - 1) * PageHelper::PAGE_LIMIT : 0;
        $limit = PageHelper::PAGE_LIMIT;

        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  jqd.json_quiz_id,
  jqd.lms_code_id,
  jq.open_date,
  jq.title,
  jq.max_score
FROM json_quiz_delivery jqd
INNER JOIN json_quiz AS jq ON jq.id = jqd.json_quiz_id 
WHERE lms_code_id = CAST(:lms_code_id AS DECIMAL(20))
ORDER BY open_date DESC 
LIMIT :limit 
OFFSET :offset 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':lms_code_id', $lmsCodeId, \PDO::PARAM_STR);
        $sth->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $sth->bindValue(':offset', $offset, \PDO::PARAM_INT);

        PDOHelper::ExecuteWithTry($sth);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $jsonQuizIds
     * @return array|false
     */
    private function GetStudentResults($jsonQuizIds)
    {
        return (new JsonQuizResultModel())->GetsFirstDataAndStudentData($jsonQuizIds);
    }
}