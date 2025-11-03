<?php

namespace IizunaLMS\Schools;

class SchoolGroup
{
    public $id;
    public $school_id;
    public $teacher_id;
    public $name;
    public $paid_application_status;
    public $lms_code_id;
    public $is_enable;
    public $is_paid;
    public $create_date;
    public $update_date;

    function __construct($data) {
        $this->id = $data['id'] ?? null;
        $this->school_id = $data['school_id'];
        $this->teacher_id = $data['teacher_id'];
        $this->name = $data['name'];
        $this->paid_application_status = $data['paid_application_status'] ?? 0;
        $this->lms_code_id = $data['lms_code_id'];
        $this->is_enable = $data['is_enable'] ?? 1;
        $this->is_paid = $data['is_paid'] ?? 0;
    }
}