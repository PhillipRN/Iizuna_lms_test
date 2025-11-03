<?php

namespace IizunaLMS\Students\Datas;

use IizunaLMS\Helpers\StringHelper;

class StudentData
{
    public $id;
    public $contact_user_id;
    public $school_name;
    public $school_grade;
    public $school_class;
    public $student_number;
    public $name;
    public $nickname;
    public $onigiri_user_id;
    public $ebook_user_id;
    public $create_date;
    public $update_date;
    public $login_id;
    public $password;
    public $is_change_password;

    function __construct($data) {
        $this->id = $data['id'] ?? null;
        $this->contact_user_id = $data['contact_user_id'];
        $this->school_name = $data['school_name'] ?? null;
        $this->school_grade = $data['school_grade'] ?? null;
        $this->school_class = $data['school_class'] ?? null;
        $this->student_number = $data['student_number'];
        $this->name = $data['name'];
        $this->nickname = $data['nickname'] ?? null;
        $this->onigiri_user_id = $data['onigiri_user_id'] ?? null;
        $this->ebook_user_id = $data['ebook_user_id'] ?? null;
        $this->login_id = $data['login_id'] ?? null;
        $this->password = (!empty($data['password'])) ? $data['password'] : null;
        $this->is_change_password = $data['is_change_password'] ?? 0;

        if (!empty($data['onigiri_user_id']))
        {
            $this->onigiri_user_id = $data['onigiri_user_id'];
        }
    }
}