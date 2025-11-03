<?php

namespace IizunaLMS\Schools;

class StudentCode
{
    public $id;
    public $school_id;
    public $teacher_id;
    public $name;
    public $lms_code_id;
    public $create_date;
    public $update_date;

    function __construct($data) {
        $this->id = $data['id'];
        $this->school_id = $data['school_id'];
        $this->teacher_id = $data['teacher_id'];
        $this->name = $data['name'];
        $this->lms_code_id = $data['lms_code_id'];
    }
}