<?php

namespace IizunaLMS\Requests;


class RequestParamStudentRegisterForWeb extends RequestParams
{
    public $login_id;
    public $password;
    public $password_confirm;
    public $lms_code;
    public $school_name;
    public $school_grade;
    public $school_class;
    public $student_number;
    public $name;
    public $nickname;

    function __construct()
    {
        $this->login_id = $this->GetPostParam('login_id');
        $this->password = $this->GetPostParam('password');
        $this->password_confirm = $this->GetPostParam('password_confirm');
        $this->lms_code = $this->GetPostParam('lms_code');
        $this->school_name = $this->GetPostParam('school_name');
        $this->school_grade = $this->GetPostParam('school_grade');
        $this->school_class = $this->GetPostParam('school_class');
        $this->student_number = $this->GetPostParam('student_number');
        $this->name = $this->GetPostParam('name');
        $this->nickname = $this->GetPostParam('nickname');
    }
}