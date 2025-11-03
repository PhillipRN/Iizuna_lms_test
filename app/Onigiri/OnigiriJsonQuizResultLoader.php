<?php

namespace IizunaLMS\Onigiri;

use IizunaLMS\Helpers\PageHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Models\OnigiriJsonQuizResultModel;
use IizunaLMS\Students\StudentLoader;

class OnigiriJsonQuizResultLoader
{
    /**
     * @param $lmsCodeId
     * @return int
     */
    public function GetMaxPageNumber($lmsCodeId)
    {
        $pdo = PDOHelper::GetPDO();

        $sql = <<<SQL
SELECT 
  COUNT(onigiri_json_quiz_id) AS number
FROM onigiri_json_quiz_delivery 
WHERE lms_code_id = CAST(:lms_code_id AS DECIMAL(20)) 
SQL;

        $sth = $pdo->prepare($sql);
        $sth->bindValue(':lms_code_id', $lmsCodeId, \PDO::PARAM_STR);

        PDOHelper::ExecuteWithTry($sth);

        $record = $sth->fetch(\PDO::FETCH_ASSOC);

        return PageHelper::GetMaxPageNum($record['number']);
    }

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
     * @return array
     */
    private function CompileData($quizzes, $tmpStudents)
    {
        $onigiriJsonQuizIds = [];
        $studentIds = [];

        foreach ($tmpStudents as $tmpStudent) {
            $studentIds[] = $tmpStudent['student_id'];
        }

        foreach ($quizzes as $quiz) {
            $onigiriJsonQuizIds[] = $quiz['onigiri_json_quiz_id'];
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
                    'student_number' => $tmpStudent['student_number']
                ];
            }
        }

        unset($tmpStudents);

        $studentResultRecords = $this->GetStudentResults($onigiriJsonQuizIds);

        foreach ($studentResultRecords as $studentResult) {
            $studentId = $studentResult['student_id'];
            $onigiriJsonQuizId = $studentResult['onigiri_json_quiz_id'];

            // 対象LMSの生徒以外はスキップ
            if (!in_array($studentId, $studentIds)) continue;

            // 生徒得点
            if (!isset($studentMap[$studentId])) {
                $studentMap[$studentId] = [];
            }

            $studentMap[$studentId][$onigiriJsonQuizId] = $studentResult['score'];
        }

        // 生徒データを画面用にまとめる
        $studentResults = [];

        foreach ($students as $studentId => $student) {
            $studentData = [
                'name' => $student['name'],
                'student_number' => $student['student_number'],
                'results' => []
            ];

            foreach ($onigiriJsonQuizIds as $onigiriJsonQuizId) {
                $studentData['results'][$onigiriJsonQuizId] = (!empty($studentMap[$studentId]) && isset($studentMap[$studentId][$onigiriJsonQuizId]))
                    ? $studentMap[$studentId][$onigiriJsonQuizId] : null;
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
  ojqd.onigiri_json_quiz_id,
  ojqd.lms_code_id,
  ojq.open_date,
  ojq.title,
  ojq.total
FROM onigiri_json_quiz_delivery ojqd
INNER JOIN onigiri_json_quiz AS ojq ON ojq.id = ojqd.onigiri_json_quiz_id 
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
  ojqd.onigiri_json_quiz_id,
  ojqd.lms_code_id,
  ojq.open_date,
  ojq.title,
  ojq.total
FROM onigiri_json_quiz_delivery ojqd
INNER JOIN onigiri_json_quiz AS ojq ON ojq.id = ojqd.onigiri_json_quiz_id 
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
     * @param $onigiriJsonQuizIds
     * @return array|false
     */
    private function GetStudentResults($onigiriJsonQuizIds)
    {
        return (new OnigiriJsonQuizResultModel())->GetsFirstDataAndStudentData($onigiriJsonQuizIds);
    }
}