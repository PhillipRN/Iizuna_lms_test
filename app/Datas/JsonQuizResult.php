<?php
namespace IizunaLMS\Datas;

class JsonQuizResult
{
    public $id;
    public $json_quiz_id;
    public $student_id;
    public $answers_json;
    public $score;
    public $is_first_result;
    public $create_date;
    public $update_date;

    function __construct($data) {
        $date = date("Y-m-d H:i:s");

        $this->json_quiz_id = $data['json_quiz_id'] ?? 0;
        $this->student_id = $data['student_id'] ?? 0;
        $this->answers_json = $data['answers_json'] ?? '';
        $this->score = $data['score'] ?? -1;
        $this->is_first_result = $data['is_first_result'] ?? 0;
        $this->create_date = $data['create_date'] ?? $date;
        $this->update_date = $data['update_date'] ?? $date;
    }
}