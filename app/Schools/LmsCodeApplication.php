<?php

namespace IizunaLMS\Schools;

class LmsCodeApplication
{
    const STATUS_ALLOWED = 0;
    const STATUS_APPLICATION_WAITING_NEW_APPROVAL = 1;
    const STATUS_APPLICATION_WAITING_UPDATE_APPROVAL = 2;

    public $lms_code_id;
    public $paid_application_status;
    public $available_amount;
    public $application_amount;

    function __construct($data) {
        $this->lms_code_id = $data['lms_code_id'];
        $this->paid_application_status = $data['paid_application_status'] ?? self::STATUS_ALLOWED;
        $this->available_amount = $data['available_amount'] ?? 0;
        $this->application_amount = $data['application_amount'] ?? 0;
    }
}