<?php

namespace IizunaLMS\Datas;

use IizunaLMS\Helpers\StringHelper;

class Teacher
{
    public $id;
    public $login_id;
    public $password;
    public $school_id;
    public $name_1;
    public $name_2;
    public $kana_1;
    public $kana_2;
    public $mail;
    public $phone;
    public $is_e_onigiri;
    public $create_date;
    public $update_date;

    function __construct($data) {
        $password = $data['password'] ?? '';

        $this->login_id = $data['login_id'];
        $this->password = StringHelper::GetHashedString($password);
        $this->school_id = $data['school_id'];
        $this->name_1 = $data['name_1'];
        $this->name_2 = $data['name_2'];
        $this->kana_1 = $data['kana_1'];
        $this->kana_2 = $data['kana_2'];
        $this->mail = $data['mail'];
        $this->phone = $data['phone'];
        $this->is_e_onigiri = $data['is_e_onigiri'] ?? 0;
    }
}