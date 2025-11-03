<?php

namespace IizunaLMS\Students\Datas;

use IizunaLMS\Helpers\StringHelper;

class StudentAutoLoginTokenData
{
    public $id;
    public $student_id;
    public $auto_login_token;
    public $create_date;
    public $expire_date;

    function __construct($data) {
        $this->student_id = $data['student_id'];
        $this->auto_login_token = StringHelper::GetHashedString($data['auto_login_token']);
        $this->create_date = $data['create_date'];
        $this->expire_date = $data['expire_date'];
    }
}