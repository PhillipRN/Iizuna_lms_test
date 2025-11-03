<?php
namespace IizunaLMS\Datas;

class JsonQuizDelivery
{
    public $json_quiz_id;
    public $lms_code_id;

    function __construct($data) {
        $this->json_quiz_id = $data['json_quiz_id'];
        $this->lms_code_id = $data['lms_code_id'];
    }
}