<?php

namespace IizunaLMS\Students\Datas;

class StudentPageParameters
{
    public $current_lms_code_id;

    function __construct($data) {
        $this->current_lms_code_id = $data['current_lms_code_id'] ?? null;
    }
}