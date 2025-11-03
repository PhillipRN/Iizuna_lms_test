<?php

namespace IizunaLMS\Onigiri;

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PeriodHelper;
use IizunaLMS\Models\OnigiriJsonQuizDeliveryModel;
use IizunaLMS\Models\OnigiriJsonQuizModel;
use IizunaLMS\Models\OnigiriJsonQuizResultModel;
use IizunaLMS\Models\StudentLmsCodeModel;
use IizunaLMS\Models\StudentModel;
use IizunaLMS\Models\TeacherModel;
use IizunaLMS\Onigiri\Data\OnigiriJsonQuizSearchParams;

class OnigiriJsonQuizLoader
{
    /**
     * @param $id
     * @return array
     */
    public function GetDisplayDataById($id)
    {
        $record = (new OnigiriJsonQuizModel())->GetById($id);

        if (empty($record)) return [];

        $record['open_date'] = PeriodHelper::ConvertDisplayOpenDate($record['open_date']);
        $record['expire_date'] = PeriodHelper::ConvertDisplayExpireDate($record['expire_date']);

        return $record;
    }

    public function GetAndResultNumById($id)
    {
        $record = (new OnigiriJsonQuizModel())->GetAndResultNumById($id);

        if (empty($record)) return [];

        $record['open_date'] = PeriodHelper::ConvertDisplayOpenDate($record['open_date']);
        $record['expire_date'] = PeriodHelper::ConvertDisplayExpireDate($record['expire_date']);

        return $record;
    }
//    public function GetsByTeacherId($teacherId, $page)
//    {
//        $records = (new OnigiriJsonQuizModel())->GetTeacherQuizzes($teacherId, $page);
//
//        $onigiriJsonQuizIds = [];
//        $existsOnigiriJsonQuizIds = [];
//        foreach ($records as $record) $onigiriJsonQuizIds[] = $record['id'];
//
//        if (!empty($onigiriJsonQuizIds)) {
//            $existsOnigiriJsonQuizzes = (new OnigiriJsonQuizDeliveryModel())->GetExistsOnigiriJsonQuizzes($onigiriJsonQuizIds);
//            foreach ($existsOnigiriJsonQuizzes as $record) $existsOnigiriJsonQuizIds[] = $record['onigiri_json_quiz_id'];
//        }
//
//        foreach ($records as $key => $record)
//        {
//            $records[$key]['is_delivery'] = (in_array($record['id'], $existsOnigiriJsonQuizIds));
//
//            $records[$key]['open_date'] = PeriodHelper::ConvertDisplayOpenDate($record['open_date']);
//            $records[$key]['expire_date'] = PeriodHelper::ConvertDisplayExpireDate($record['expire_date']);
//        }
//
//        return $records;
//    }

    public function GetsBySchoolId($schoolId, $page, $limit, OnigiriJsonQuizSearchParams $searchParams)
    {
        $records = (new OnigiriJsonQuizModel())->GetSchoolQuizzes($schoolId, $page, $limit, $searchParams);

        $onigiriJsonQuizIds = [];
        $existsOnigiriJsonQuizIds = [];
        foreach ($records as $record) $onigiriJsonQuizIds[] = $record['id'];

        if (!empty($onigiriJsonQuizIds)) {
            $existsOnigiriJsonQuizzes = (new OnigiriJsonQuizDeliveryModel())->GetExistsOnigiriJsonQuizzes($onigiriJsonQuizIds);
            foreach ($existsOnigiriJsonQuizzes as $record) $existsOnigiriJsonQuizIds[] = $record['onigiri_json_quiz_id'];
        }

        foreach ($records as $key => $record)
        {
            $records[$key]['is_delivery'] = (in_array($record['id'], $existsOnigiriJsonQuizIds));

            $records[$key]['open_date'] = PeriodHelper::ConvertDisplayOpenDate($record['open_date']);
            $records[$key]['expire_date'] = PeriodHelper::ConvertDisplayExpireDate($record['expire_date']);
        }

        return $records;
    }

//    public function GetMaxPageNumber($teacherId): int
//    {
//        return (new OnigiriJsonQuizModel())->GetMaxPageNumber($teacherId);
//    }

    /**
     * @param $schoolId
     * @param $limit
     * @param OnigiriJsonQuizSearchParams $searchParams
     * @return int
     */
    public function GetMaxPageNumberBySchoolId($schoolId, $limit, OnigiriJsonQuizSearchParams $searchParams): int
    {
        return (new OnigiriJsonQuizModel())->GetMaxPageNumberBySchoolId($schoolId, $limit, $searchParams);
    }

    /**
     * @param $onigiriJsonQuizId
     * @param $studentId
     * @return array
     */
    public function GetAvailableQuizForStudent($onigiriJsonQuizId, $studentId)
    {
        // LMSコード取得
        $studentLmsCords = (new StudentLmsCodeModel())->GetsByKeyValue('student_id', $studentId);
        $studentLmsCordIds = [];

        foreach ($studentLmsCords as $studentLmsCord)
        {
            $studentLmsCordIds[] = $studentLmsCord['lms_code_id'];
        }

        // クイズID取得
        $deliveryRecords = (new OnigiriJsonQuizDeliveryModel())->GetsByKeyValue('onigiri_json_quiz_id', $onigiriJsonQuizId);

        $isDelivery = false;
        foreach ($deliveryRecords as $deliveryRecord)
        {
            if (in_array($deliveryRecord['lms_code_id'], $studentLmsCordIds)) {
                $isDelivery = true;
                break;
            }
        }

        if (!$isDelivery) return ['error' => ERROR::ERROR_STUDENT_ONIGIRI_JSON_QUIZ_NOT_DELIVERY];

        // クイズデータ取得
        $onigiriJsonQuiz = (new OnigiriJsonQuizModel())->GetById($onigiriJsonQuizId);

        if (empty($onigiriJsonQuiz)) return ['error' => ERROR::ERROR_STUDENT_ONIGIRI_JSON_QUIZ_NOT_FOUND];

        // TODO 期限切れ判定

        return [
            'data' => $onigiriJsonQuiz
        ];
    }

//    public function GetAvailableQuizForTeacher($onigiriJsonQuizId, $teacherId)
//    {
//        // クイズデータ取得
//        $onigiriJsonQuiz = (new OnigiriJsonQuizModel())->GetById($onigiriJsonQuizId);
//
//        if (empty($onigiriJsonQuiz)) return ['error' => ERROR::ERROR_ONIGIRI_JSON_QUIZ_PREVIEW_NO_DATA];
//
//        if ($onigiriJsonQuiz['teacher_id'] != $teacherId) return ['error' => ERROR::ERROR_ONIGIRI_JSON_QUIZ_PREVIEW_PERMISSION];
//
//        return [
//            'data' => $onigiriJsonQuiz
//        ];
//    }

    public function GetAvailableQuizForSchool($onigiriJsonQuizId, $schoolId)
    {
        // クイズデータ取得
        $onigiriJsonQuiz = (new OnigiriJsonQuizModel())->GetById($onigiriJsonQuizId);

        if (empty($onigiriJsonQuiz)) return ['error' => ERROR::ERROR_ONIGIRI_JSON_QUIZ_PREVIEW_NO_DATA];

        $teacher = (new TeacherModel())->GetById($onigiriJsonQuiz['teacher_id']);

        if ($teacher['school_id'] != $schoolId) return ['error' => ERROR::ERROR_ONIGIRI_JSON_QUIZ_PREVIEW_PERMISSION];

        return [
            'data' => $onigiriJsonQuiz
        ];
    }

    /**
     * @param $quizId
     * @return array
     */
    public function GetResultsById($quizId): array
    {
        $deliveries = (new OnigiriJsonQuizDeliveryModel())->GetsByKeyValue('onigiri_json_quiz_id', $quizId);
        if (empty($deliveries)) return [];

        $lmsCodeIds = [];

        foreach ($deliveries as $delivery) $lmsCodeIds[] = $delivery['lms_code_id'];

        $studentsLmsCodes = (new StudentLmsCodeModel())->GetsByKeyInValues('lms_code_id', $lmsCodeIds);

        $studentIds = [];
        foreach ($studentsLmsCodes as $studentsLmsCode) $studentIds[] = $studentsLmsCode['student_id'];

        $students = (new StudentModel())->GetsByKeyInValues('id', $studentIds);

        $studentMap = [];
        foreach ($students as $student)
        {
            $studentMap[ $student['id'] ] = $student['name'];
        }

        $records = (new OnigiriJsonQuizResultModel())->GetsByKeyValues(
            ['onigiri_json_quiz_id', 'is_first_result'],
            [$quizId, 1],
            [],
            ['id' => 'DESC']
        );

        for ($i = 0; $i < count($records); ++$i)
        {
            $records[ $i ]['student_name'] =
                (isset($studentMap[ $records[ $i ]['student_id'] ]))
                ? $studentMap[ $records[ $i ]['student_id'] ]
                : "";
        }

        return $records;
    }
}