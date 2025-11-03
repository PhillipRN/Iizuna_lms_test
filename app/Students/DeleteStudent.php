<?php

namespace IizunaLMS\Students;

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Models\JsonQuizModel;
use IizunaLMS\Models\JsonQuizResultModel;
use IizunaLMS\Models\OnigiriJsonQuizModel;
use IizunaLMS\Models\OnigiriJsonQuizResultModel;
use IizunaLMS\Models\StudentLmsCodeModel;
use IizunaLMS\Models\StudentModel;

class DeleteStudent
{
    /**
     * @param $studentId
     * @param $schoolId
     * @return bool
     */
    public function Delete($studentId, $schoolId)
    {
        if (!(new StudentSchool())->Check($studentId, $schoolId)) return false;

        PDOHelper::GetPDO()->beginTransaction();

        // json_quiz_result の削除準備
        // 1-1. 該当生徒が回答しているクイズの ID を取得
        $records = (new JsonQuizResultModel())->GetStudentAnsweredQuizIds($studentId);
        $quizIds = [];
        foreach ($records as $record) $quizIds[] = $record['json_quiz_id'];

        // 1-2. 該当生徒が回答しているクイズを再計算対象にする
        if ( !empty($quizIds) && !(new JsonQuizModel())->SetCalculateAnswerRateByIds($quizIds) )
        {
            PDOHelper::GetPDO()->rollBack();
            return false;
        }

        // 2. 該当生徒の json_quiz_result の削除
        if ( !(new JsonQuizResultModel())->DeleteByStudentId($studentId) )
        {
            PDOHelper::GetPDO()->rollBack();
            return false;
        }

        // onigiri_json_quiz_result の削除準備
        // 1-1. 該当生徒が回答しているクイズの ID を取得
        $records = (new OnigiriJsonQuizResultModel())->GetStudentAnsweredQuizIds($studentId);
        $quizIds = [];
        foreach ($records as $record) $quizIds[] = $record['onigiri_json_quiz_id'];

        // 1-2. 該当生徒が回答しているクイズを再計算対象にする
        if ( !empty($quizIds) && !(new OnigiriJsonQuizModel())->SetCalculateAnswerRateByIds($quizIds) )
        {
            PDOHelper::GetPDO()->rollBack();
            return false;
        }

        // 2. 該当生徒の onigiri_json_quiz_result の削除
        if ( !(new OnigiriJsonQuizResultModel())->DeleteByStudentId($studentId) )
        {
            PDOHelper::GetPDO()->rollBack();
            return false;
        }

        // student_lms_code 削除
        if ( !(new StudentLmsCodeModel())->DeleteByStudentId($studentId) )
        {
            PDOHelper::GetPDO()->rollBack();
            return false;
        }

        // student 削除
        if ( !(new StudentModel())->DeleteByKeyValue('id', $studentId) )
        {
            PDOHelper::GetPDO()->rollBack();
            return false;
        }

        PDOHelper::GetPDO()->commit();
        return true;
    }
}