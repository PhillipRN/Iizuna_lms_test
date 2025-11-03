<?php

namespace IizunaLMS\Students\Datas;

class StudentLmsCodeData
{
    public $student_id;
    public $lms_code_id;

    function __construct($data) {
        $this->student_id = $data['student_id'];
        $this->lms_code_id = $data['lms_code_id'];
    }
}