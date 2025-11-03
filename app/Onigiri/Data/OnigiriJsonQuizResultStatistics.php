<?php
namespace IizunaLMS\Onigiri\Data;

class OnigiriJsonQuizResultStatistics
{
    public $id;
    public $onigiri_json_quiz_id;
    public $answer_rates_json;
    public $create_date;
    public $update_date;

    function __construct($data) {
        $date = date("Y-m-d H:i:s");

        $this->id = $data['id'] ?? 0;
        $this->onigiri_json_quiz_id = $data['onigiri_json_quiz_id'] ?? 0;
        $this->answer_rates_json = $data['answer_rates_json'] ?? '';
        $this->create_date = $data['create_date'] ?? $date;
        $this->update_date = $data['update_date'] ?? $date;
    }
}