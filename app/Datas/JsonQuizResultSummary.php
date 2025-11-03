<?php
namespace IizunaLMS\Datas;

class JsonQuizResultSummary
{
    public $id;
    public $json_quiz_id;
    public $average;
    public $highest_score;
    public $lowest_score;
    public $correct_answer_rates_json;
    public $create_date;
    public $update_date;

    function __construct($data) {
        $date = date("Y-m-d H:i:s");

        $this->id = $data['id'] ?? 0;
        $this->json_quiz_id = $data['json_quiz_id'] ?? 0;
        $this->average = $data['average'] ?? 0;
        $this->highest_score = $data['highest_score'] ?? 0;
        $this->lowest_score = $data['lowest_score'] ?? 0;
        $this->correct_answer_rates_json = $data['correct_answer_rates_json'] ?? '';
        $this->create_date = $data['create_date'] ?? $date;
        $this->update_date = $data['update_date'] ?? $date;
    }
}