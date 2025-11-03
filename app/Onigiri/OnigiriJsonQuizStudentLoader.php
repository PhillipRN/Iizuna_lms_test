<?php

namespace IizunaLMS\Onigiri;

use IizunaLMS\Models\OnigiriJsonQuizDeliveryModel;
use IizunaLMS\Models\StudentLmsCodeModel;
use IizunaLMS\Models\StudentModel;

class OnigiriJsonQuizStudentLoader
{
    /**
     * @param $id
     * @return array
     */
    public function GetStudentsByOnigiriJsonQuizId($id): array
    {
        $deliveries = (new OnigiriJsonQuizDeliveryModel())->GetsByKeyValue('onigiri_json_quiz_id', $id);
        if (empty($deliveries)) return [];

        $lmsCodeIds = [];

        foreach ($deliveries as $delivery) $lmsCodeIds[] = $delivery['lms_code_id'];

        $studentsLmsCodes = (new StudentLmsCodeModel())->GetsByKeyInValues('lms_code_id', $lmsCodeIds);

        $studentIds = [];
        foreach ($studentsLmsCodes as $studentsLmsCode) $studentIds[] = $studentsLmsCode['student_id'];

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