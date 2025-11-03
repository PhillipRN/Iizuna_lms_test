<?php

namespace IizunaLMS\Requests;


class RequestParamStudentChangePassword extends RequestParams
{
    public $password;
    public $password_confirm;

    function __construct()
    {
        $this->password = $this->GetPostParam('password');
        $this->password_confirm = $this->GetPostParam('password_confirm');
    }
}