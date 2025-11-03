<?php
namespace IizunaLMS\Onigiri\Data;

class OnigiriJsonQuizDeliveryData
{
    public $onigiri_json_quiz_id;
    public $lms_code_id;
    public $notice_id;

    function __construct($data) {
        $this->onigiri_json_quiz_id = $data['onigiri_json_quiz_id'];
        $this->lms_code_id = $data['lms_code_id'];
        $this->notice_id = $data['notice_id'] ?? null;
    }
}