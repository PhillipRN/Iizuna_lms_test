<?php
namespace IizunaLMS\Onigiri\Data;

class OnigiriJsonQuizResult
{
    public $id;
    public $onigiri_json_quiz_id;
    public $student_id;
    public $answers_json;
    public $score;
    public $is_first_result;

    function __construct($data) {
        $this->onigiri_json_quiz_id = $data['onigiri_json_quiz_id'] ?? 0;
        $this->student_id = $data['student_id'] ?? 0;
        $this->answers_json = $data['answers_json'] ?? '';
        $this->score = $data['score'] ?? -1;
        $this->is_first_result = $data['is_first_result'] ?? 0;
    }
}