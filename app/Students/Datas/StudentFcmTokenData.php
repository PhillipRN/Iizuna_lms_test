<?php

namespace IizunaLMS\Students\Datas;

use IizunaLMS\Helpers\StringHelper;

class StudentFcmTokenData
{
    public $student_id;
    public $fcm_token;
    public $expire_date;

    function __construct($data) {
        $this->student_id = $data['student_id'];
        $this->fcm_token = $data['fcm_token'];
        $this->expire_date = $data['expire_date'];
    }
}