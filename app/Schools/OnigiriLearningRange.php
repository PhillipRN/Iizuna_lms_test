<?php

namespace IizunaLMS\Schools;

class OnigiriLearningRange
{
    public $lms_code_id;
    public $sequential_number;
    public $genre;
    public $learning_range_level;
    public $stage;
    public $create_date;

    function __construct($lmsCodeId, $sequentialNumber, $genre, $level, $stage) {
        $this->lms_code_id = $lmsCodeId;
        $this->sequential_number = $sequentialNumber;
        $this->genre = $genre;
        $this->learning_range_level = $level;
        $this->stage = $stage;
    }
}