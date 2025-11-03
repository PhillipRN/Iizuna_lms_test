<?php

namespace IizunaLMS\Requests;


class RequestParamStudentAddLmsCode extends RequestParams
{
    public $lms_code;
    function __construct()
    {
        $this->lms_code = $this->GetPostParam('lms_code');
    }
}