<?php

namespace IizunaLMS\Students\Datas;

use IizunaLMS\Helpers\StringHelper;

class StudentRefreshTokenData
{
    public $id;
    public $student_id;
    public $refresh_token;
    public $create_date;
    public $expire_date;

    function __construct($data) {
        $this->student_id = $data['student_id'];
        $this->refresh_token = StringHelper::GetHashedString($data['refresh_token']);
        $this->create_date = $data['create_date'];
        $this->expire_date = $data['expire_date'];
    }
}