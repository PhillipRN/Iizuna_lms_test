<?php

namespace IizunaLMS\Datas;

class TeacherLoginData
{
    public $id;
    public $login_id;
    public $school_id;
    public $name_1;
    public $name_2;
    public $kana_1;
    public $kana_2;
    public $mail;
    public $phone;
    public $school_name;
    public $school_zip;
    public $school_pref;
    public $school_address;
    public $school_phone;
    public $is_paid;
    public $is_juku;
    public $lms_code;

    function __construct($data) {
        $this->id = $data['id'];
        $this->login_id = $data['login_id'];
        $this->school_id = $data['school_id'];
        $this->name_1 = $data['name_1'];
        $this->name_2 = $data['name_2'];
        $this->kana_1 = $data['kana_1'];
        $this->kana_2 = $data['kana_2'];
        $this->mail = $data['mail'];
        $this->phone = $data['phone'];
        $this->school_name = $data['school_name'];
        $this->school_zip = $data['school_zip'];
        $this->school_pref = $data['school_pref'];
        $this->school_address = $data['school_address'];
        $this->school_phone = $data['school_phone'];
        $this->is_paid = $data['is_paid'];
        $this->is_juku = $data['is_juku'];
        $this->lms_code = $data['lms_code'];
    }
}