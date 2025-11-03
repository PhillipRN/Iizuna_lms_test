<?php

namespace IizunaLMS\Students;

use IizunaLMS\Models\LmsTicketGroupViewModel;
use IizunaLMS\Models\SchoolGroupModel;
use IizunaLMS\Models\SchoolModel;
use IizunaLMS\Models\StudentLmsCodeModel;

class StudentLmsCodeLoader
{
    /**
     * @param $studentId
     * @return array
     */
    public function GetsByStudentId($studentId)
    {
        $lmsCodes = (new StudentLmsCodeModel())->GetsByKeyValue('student_id', $studentId);

        $lmsCodeIds = [];
        foreach ($lmsCodes as $lmsCode) $lmsCodeIds[] = $lmsCode['lms_code_id'];

        $schools = (new SchoolModel())->GetsByKeyInValues('lms_code_id', $lmsCodeIds);
        $schoolGroups = (new SchoolGroupModel())->GetsByKeyInValues('lms_code_id', $lmsCodeIds);
        $lmsTickets = (new LmsTicketGroupViewModel())->GetsByKeyInValues('lms_code_id', $lmsCodeIds);

        $result = [];

        foreach ($schools as $school)
        {
            $result[] = [
                'type' => 'school',
                'name' => $school['name'],
                'lms_code_id' => $school['lms_code_id']
            ];
        }

        foreach ($schoolGroups as $schoolGroup)
        {
            $result[] = [
                'type' => 'school_group',
                'name' => $schoolGroup['name'],
                'lms_code_id' => $schoolGroup['lms_code_id']
            ];
        }

        foreach ($lmsTickets as $lmsTicket)
        {
            $result[] = [
                'type' => 'lms_ticket',
                'name' => $lmsTicket['name'],
                'lms_code_id' => $lmsTicket['lms_code_id']
            ];
        }

        return $result;
    }
}