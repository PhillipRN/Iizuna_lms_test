<?php

namespace IizunaLMS\Students\Datas;

use IizunaLMS\Helpers\StringHelper;

class StudentAccessTokenData
{
    public $id;
    public $student_id;
    public $access_token;
    public $create_date;
    public $expire_date;

    function __construct($data) {
        $this->student_id = $data['student_id'];
        $this->access_token = StringHelper::GetHashedString($data['access_token']);
        $this->create_date = $data['create_date'];
        $this->expire_date = $data['expire_date'];
    }
}