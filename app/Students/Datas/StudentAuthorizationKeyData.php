<?php

namespace IizunaLMS\Students\Datas;

class StudentAuthorizationKeyData
{
    public $id;
    public $student_id;
    public $authorization_key;
    public $create_date;
    public $expire_date;

    function __construct($data) {
        $this->student_id = $data['student_id'];
        $this->authorization_key = $data['authorization_key'];
        $this->create_date = $data['create_date'];
        $this->expire_date = $data['expire_date'];
    }
}