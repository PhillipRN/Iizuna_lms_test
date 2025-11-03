<?php

namespace IizunaLMS\Datas;

class TeacherBookApplicationLog
{
    const TYPE_ADMIN = 1;
    const TYPE_CREATE_TEACHER = 2;
    const TYPE_ADD = 3;

    public $teacher_id;
    public $title_no;
    public $type;

    function __construct($data) {
        $this->teacher_id = $data['teacher_id'];
        $this->title_no = $data['title_no'];
        $this->type = $data['type'] ?? 0;
    }
}