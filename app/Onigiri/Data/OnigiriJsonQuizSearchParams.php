<?php
namespace IizunaLMS\Onigiri\Data;

class OnigiriJsonQuizSearchParams
{
    public $title;
    public $is_manual_mode;
    public $teacher_id;
    public $start_date;
    public $end_date;
    public $parent_folder_id = 0;

    function __construct($data) {
        foreach($this as $key => $value) {
            if (isset($data[$key])) $this->$key = $data[$key];
        }
    }

}