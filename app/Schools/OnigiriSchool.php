<?php

namespace IizunaLMS\Schools;

class OnigiriSchool
{
    public $id;
    public $school_id;
    public $lms_code;
    public $create_date;

    function __construct($data) {
        $this->school_id = $data['school_id'];
        $this->lms_code = $data['lms_code'];
    }
}