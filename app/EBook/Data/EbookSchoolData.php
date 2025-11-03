<?php
namespace IizunaLMS\EBook\Data;

class EbookSchoolData
{
    public $school_id;
    public $title_no;
    public $is_buy;
    public $is_display;

    function __construct($schoolId, $titleNo, $data) {
        $this->school_id = $schoolId;
        $this->title_no = $titleNo;
        $this->is_buy = $data['is_buy'] ?? 0;
        $this->is_display = $data['is_display'] ?? 0;
    }
}