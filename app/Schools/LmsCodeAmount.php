<?php

namespace IizunaLMS\Schools;

class LmsCodeAmount
{
    public $lms_code_id;

    function __construct($lmsCodeId) {
        $this->lms_code_id = $lmsCodeId;
    }
}