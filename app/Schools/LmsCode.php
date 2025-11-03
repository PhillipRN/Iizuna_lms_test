<?php

namespace IizunaLMS\Schools;

class LmsCode
{
    const TYPE_SCHOOL = 1;
    const TYPE_LMS_TICKET = 2;

    public $id;
    public $lms_code;
    public $type;        // 1:学校・クラス・グループ, 2:LMSチケット
    public $create_date;
    public $update_date;

    function __construct($data) {
        $this->id = $data['id'] ?? null;
        $this->type = $data['type'] ?? self::TYPE_SCHOOL;
        $this->lms_code = $data['lms_code'];
    }
}