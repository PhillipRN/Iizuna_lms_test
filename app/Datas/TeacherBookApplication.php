<?php

namespace IizunaLMS\Datas;

class TeacherBookApplication
{
    const STATUS_OK = 0;
    const STATUS_WAITING_APPROVAL = 1;

    public $teacher_id;
    public $title_no;
    public $status;

    function __construct($data) {
        $this->teacher_id = $data['teacher_id'];
        $this->title_no = $data['title_no'];
        $this->status = $data['status'] ?? self::STATUS_WAITING_APPROVAL;
    }
}