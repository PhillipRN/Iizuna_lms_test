<?php

namespace IizunaLMS\Students;

use IizunaLMS\Models\StudentLmsCodeModel;
use IizunaLMS\Schools\SchoolGroupLoader;

class StudentSchool
{
    public function Check($studentId, $schoolId)
    {
        $schoolGroups = SchoolGroupLoader::GetSchoolAndGroups($schoolId);
        $schoolLmsCodeIds = array_column($schoolGroups, 'lms_code_id');

        $studentLmsCords = (new StudentLmsCodeModel())->GetsByKeyValue('student_id', $studentId);
        $lmsCordIds = array_column($studentLmsCords, 'lms_code_id');

        foreach ($lmsCordIds as $lmsCordId)
        {
            if (in_array($lmsCordId, $schoolLmsCodeIds)) return true;
        }

        return false;
    }
}