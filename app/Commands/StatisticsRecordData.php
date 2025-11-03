<?php

namespace IizunaLMS\Commands;

class StatisticsRecordData
{
    public $answer;
    public $answerCount = 0;
    public $isCorrect = false;

    public function __construct($answer, $isCorrect)
    {
        $this->answer = $answer;
        $this->isCorrect = $isCorrect;
    }
}