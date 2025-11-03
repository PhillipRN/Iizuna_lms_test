<?php
namespace IizunaLMS\Onigiri\Data;

use DateTimeImmutable;
use IizunaLMS\Helpers\PeriodHelper;

class OnigiriJsonQuizData
{
    public $id;
    public $parent_folder_id;
    public $teacher_id;
    public $range_lms_code_id;
    public $range_stage;
    public $ranges;
    public $title;
    public $type;
    public $json;
    public $total;
    public $open_date;
    public $expire_date;
    public $time_limit;
    public $is_manual_mode;

    function __construct($data) {

        $this->id = $data['id'] ?? 0;
        $this->parent_folder_id = $data['parent_folder_id'] ?? 0;
        $this->teacher_id = $data['teacher_id'] ?? 0;
        $this->range_lms_code_id = $data['range_lms_code_id'] ?? 0;
        $this->range_stage = $data['range_stage'] ?? 0;
        $this->ranges = $data['ranges'] ?? '';
        $this->title = $data['title'] ?? '';
        $this->type = $data['type'] ?? '';
        $this->json = $data['json'] ?? '';
        $this->total = $data['total'] ?? 0;
        $this->open_date = (!empty($data['open_date'])) ? $data['open_date'] : PeriodHelper::PERIOD_OPEN_DATE;
        $this->expire_date = (!empty($data['expire_date'])) ? $data['expire_date'] : PeriodHelper::PERIOD_EXPIRE_DATE;
        $this->time_limit = $data['time_limit'] ?? 0;
        $this->is_manual_mode = $data['is_manual_mode'] ?? 0;
    }
}