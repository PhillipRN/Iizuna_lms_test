<?php
namespace IizunaLMS\Datas;

class JsonQuizSearchParams
{
    public $title;
    public $title_no;
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