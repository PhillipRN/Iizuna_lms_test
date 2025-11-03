<?php

namespace IizunaLMS\JsonQuizzes;

use IizunaLMS\Models\JsonQuizDeliveryModel;
use IizunaLMS\Models\StudentLmsCodeModel;
use IizunaLMS\Models\StudentModel;

class JsonQuizStudentLoader
{
    /**
     * @param $id
     * @return array
     */
    public function GetStudentsById($id): array
    {
        $deliveries = (new JsonQuizDeliveryModel())->GetsByKeyValue('json_quiz_id', $id);
        if (empty($deliveries)) return [];

        $lmsCodeIds = [];

        foreach ($deliveries as $delivery) $lmsCodeIds[] = $delivery['lms_code_id'];

        $studentsLmsCodes = (new StudentLmsCodeModel())->GetsByKeyInValues('lms_code_id', $lmsCodeIds);

        $studentIds = [];
        foreach ($studentsLmsCodes as $studentsLmsCode) $studentIds[] = $studentsLmsCode['student_id'];

        // NOTE 配信先に選択されているLMSコードに誰も生徒が紐づいていない場合にエラーになってしまうため、ここで抜ける
        if (empty($studentIds)) return [];

        $students = (new StudentModel())->GetsByKeyInValues('id', $studentIds);

        $result = [];
        foreach ($students as $student)
        {
            $result[ $student['id'] ] = [
                'name' => $student['name'],
                'student_number' => $student['student_number']
            ];
        }

        return $result;
    }
}