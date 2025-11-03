<?php

namespace IizunaLMS\JsonQuizzes;

use IizunaLMS\Datas\JsonQuizSearchParams;
use IizunaLMS\Datas\Teacher;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PageHelper;
use IizunaLMS\Helpers\PeriodHelper;
use IizunaLMS\Models\JsonQuizDeliveryModel;
use IizunaLMS\Models\JsonQuizModel;
use IizunaLMS\Models\StudentLmsCodeModel;
use IizunaLMS\Models\TeacherModel;
use IizunaLMS\Teachers\TeacherLoader;

class JsonQuizLoader
{
    /**
     * @param $teacherId
     * @param $page
     * @return array
     */
    public function GetsByTeacherId($teacherId, $page)
    {
        $records = $this->GetJsonQuizModel()->GetTeacherQuizzes($teacherId, $page);

        $jsonQuizIds = [];
        $existsJsonQuizIds = [];
        foreach ($records as $record) $jsonQuizIds[] = $record['id'];

        if (!empty($jsonQuizIds)) {
            $existsJsonQuizzes = (new JsonQuizDeliveryModel())->GetExistsJsonQuizzes($jsonQuizIds);
            foreach ($existsJsonQuizzes as $record) $existsJsonQuizIds[] = $record['json_quiz_id'];
        }

        // 期限切れ判定
        $currentTime = time();
        foreach ($records as $key => $record)
        {
            $records[$key]['is_expired'] = ($currentTime > strtotime($record['expire_date']));
            $records[$key]['is_delivery'] = (in_array($record['id'], $existsJsonQuizIds));

            $records[$key]['open_date'] = PeriodHelper::ConvertDisplayOpenDate($record['open_date']);
            $records[$key]['expire_date'] = PeriodHelper::ConvertDisplayExpireDate($record['expire_date']);
        }

        return $records;
    }

    /**
     * @param $schoolId
     * @param $page
     * @param JsonQuizSearchParams $searchParams
     * @return array
     */
    public function GetsBySchoolId($schoolId, $page, $limit, JsonQuizSearchParams $searchParams)
    {
        $records = $this->GetJsonQuizModel()->GetSchoolQuizzes($schoolId, $page, $limit, $searchParams);

        $jsonQuizIds = [];
        $existsJsonQuizIds = [];
        foreach ($records as $record) $jsonQuizIds[] = $record['id'];

        if (!empty($jsonQuizIds)) {
            $existsJsonQuizzes = (new JsonQuizDeliveryModel())->GetExistsJsonQuizzes($jsonQuizIds);
            foreach ($existsJsonQuizzes as $record) $existsJsonQuizIds[] = $record['json_quiz_id'];
        }

        // 期限切れ判定
        $currentTime = time();
        foreach ($records as $key => $record)
        {
            $records[$key]['is_expired'] = ($currentTime > strtotime($record['expire_date']));
            $records[$key]['is_delivery'] = (in_array($record['id'], $existsJsonQuizIds));

            $records[$key]['open_date'] = PeriodHelper::ConvertDisplayOpenDate($record['open_date']);
            $records[$key]['expire_date'] = PeriodHelper::ConvertDisplayExpireDate($record['expire_date']);
        }

        return $records;
    }

    /**
     * @param $teacherId
     * @return int
     */
    public function GetMaxPageNumber($teacherId): int
    {
        return $this->GetJsonQuizModel()->GetMaxPageNumber($teacherId);
    }

    /**
     * @param $teacherId
     * @param JsonQuizSearchParams $searchParams
     * @return int
     */
    public function GetMaxPageNumberBySchoolId($teacherId, $limit, JsonQuizSearchParams $searchParams): int
    {
        return $this->GetJsonQuizModel()->GetMaxPageNumberBySchoolId($teacherId, $limit, $searchParams);
    }

    /**
     * @param $jsonQuizId
     * @param $studentId
     * @return array
     */
    public function GetAvailableQuizForStudent($jsonQuizId, $studentId)
    {
        // LMSコード取得
        $studentLmsCords = (new StudentLmsCodeModel())->GetsByKeyValue('student_id', $studentId);
        $studentLmsCordIds = [];

        foreach ($studentLmsCords as $studentLmsCord)
        {
            $studentLmsCordIds[] = $studentLmsCord['lms_code_id'];
        }

        // クイズID取得
        $deliveryRecords = (new JsonQuizDeliveryModel())->GetsByKeyValue('json_quiz_id', $jsonQuizId);

        $isDelivery = false;
        foreach ($deliveryRecords as $deliveryRecord)
        {
            if (in_array($deliveryRecord['lms_code_id'], $studentLmsCordIds)) {
                $isDelivery = true;
                break;
            }
        }

        if (!$isDelivery) return ['error' => ERROR::ERROR_JSON_QUIZ_NOT_DELIVERY];

        // クイズデータ取得
        $jsonQuiz = (new JsonQuizModel())->GetById($jsonQuizId);

        if (empty($jsonQuiz)) return ['error' => ERROR::ERROR_JSON_QUIZ_NO_DATA];

        // TODO 期限切れ判定

        return [
            'data' => $jsonQuiz
        ];
    }

    /**
     * @param $jsonQuizId
     * @param $teacherId
     * @return array
     */
    public function GetAvailableQuizForTeacher($jsonQuizId, $teacherId)
    {
        // クイズデータ取得
        $jsonQuiz = (new JsonQuizModel())->GetById($jsonQuizId);

        if (empty($jsonQuiz)) return ['error' => ERROR::ERROR_QUIZ_PREVIEW_NO_DATA];

        if ($jsonQuiz['teacher_id'] != $teacherId) return ['error' => ERROR::ERROR_QUIZ_PREVIEW_PERMISSION];

        return [
            'data' => $jsonQuiz
        ];
    }

    /**
     * @param $jsonQuizId
     * @param $schoolId
     * @return array
     */
    public function GetAvailableQuizForSchool($jsonQuizId, $schoolId)
    {
        // クイズデータ取得
        $jsonQuiz = (new JsonQuizModel())->GetById($jsonQuizId);

        if (empty($jsonQuiz)) return ['error' => ERROR::ERROR_QUIZ_PREVIEW_NO_DATA];

        $teacher = (new TeacherModel())->GetById($jsonQuiz['teacher_id']);

        if ($teacher['school_id'] != $schoolId) return ['error' => ERROR::ERROR_QUIZ_PREVIEW_PERMISSION];

        return [
            'data' => $jsonQuiz
        ];
    }

    private ?JsonQuizModel $_JsonQuizModel = null;

    private function GetJsonQuizModel(): JsonQuizModel
    {
        if ($this->_JsonQuizModel != null) return $this->_JsonQuizModel;

        $this->_JsonQuizModel = new JsonQuizModel();

        return $this->_JsonQuizModel;
    }
}