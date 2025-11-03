<?php
namespace IizunaLMS\Datas;

class JsonQuizResultSendError
{
    public $id;
    public $json_quiz_result_id;
    public $error_message;
    public $create_date;

    function __construct($data) {
        $date = date("Y-m-d H:i:s");

        $this->id = $data['id'] ?? 0;
        $this->json_quiz_result_id = $data['json_quiz_result_id'] ?? 0;
        $this->error_message = $data['error_message'] ?? 0;
        $this->create_date = $data['create_date'] ?? $date;
    }
}