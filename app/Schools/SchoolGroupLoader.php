<?php

namespace IizunaLMS\Schools;

use IizunaLMS\Models\SchoolGroupViewModel;
use IizunaLMS\Models\SchoolViewModel;

class SchoolGroupLoader
{
    /**
     * @param $schoolId
     * @return array
     */
    public static function GetSchoolAndGroups($schoolId): array
    {
        $records = [];

        $school = (new SchoolViewModel())->GetById($schoolId);
        $schoolGroups = (new SchoolGroupViewModel())->GetsByKeyValue('school_id', $schoolId);

        // 学校のレコードを先に入れる
        $records[] = [
            'id' => $school['id'],
            'name' => $school['name'],
            'paid_application_status' => 0, // NOTE: 学校は常に0
            'teacher_name_1' => '',
            'teacher_name_2' => '',
            'lms_code_id' => $school['lms_code_id'],
            'lms_code' => $school['lms_code'],
            'is_paid' => $school['is_paid'],
            'available_total' => 0, // NOTE: 学校は常に0
            'application_total' => 0, // NOTE: 学校は常に0
            'is_enable' => 1, // NOTE: 学校は常に有効
            'is_school' => 1,
        ];

        // グループを後ろに追加
        foreach ($schoolGroups as $schoolGroup) {
            $records[] = [
                'id' => $schoolGroup['id'],
                'name' => $schoolGroup['name'],
                'paid_application_status' => $schoolGroup['paid_application_status'],
                'teacher_name_1' => $schoolGroup['teacher_name_1'],
                'teacher_name_2' => $schoolGroup['teacher_name_2'],
                'lms_code_id' => $schoolGroup['lms_code_id'],
                'lms_code' => $schoolGroup['lms_code'],
                'is_paid' => $schoolGroup['is_paid'],
                'available_total' => $schoolGroup['available_total'],
                'application_total' => $schoolGroup['application_total'],
                'is_enable' => $schoolGroup['is_enable'],
                'is_school' => 0,
            ];
        }



        return $records;
    }

    /**
     * @param $schoolId
     * @return bool
     */
    public static function HavePaidGroupInSchool($schoolId): bool
    {
        $schoolGroups = self::GetSchoolAndGroups($schoolId);

        foreach ($schoolGroups as $schoolGroup)
        {
            if ( isset($schoolGroup['is_paid']) &&
                 $schoolGroup['is_paid'] &&
                 isset($schoolGroup['paid_application_status']) &&
                 $schoolGroup['paid_application_status'] == LmsCodeApplication::STATUS_ALLOWED )
            {
                return true;
            }
        }

        return false;
    }
}