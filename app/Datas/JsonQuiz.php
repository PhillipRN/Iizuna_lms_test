<?php
namespace IizunaLMS\Datas;

use DateTimeImmutable;
use IizunaLMS\Helpers\PeriodHelper;

class JsonQuiz
{
    const TYPE_PAGE_BREAK_ITEM = "page_break_item";
    const TYPE_VERTICAL_MULTIPLE_CHOICE_QUESTION         = 'vertical_multiple_choice_question';
    const TYPE_VERTICAL_SHORT_ANSWER_QUESTION            = 'vertical_short_answer_question';
    const TYPE_MULTIPLE_CHOICE_QUESTION         = 'multiple_choice_question';
    const TYPE_SHORT_ANSWER_QUESTION            = 'short_answer_question';

    public $id;
    public $parent_folder_id;
    public $teacher_id;
    public $title_no;
    public $language_type;
    public $title;
    public $json;
    public $max_score;
    public $calc_correct_answer_rate;
    public $open_date;
    public $expire_date;
    public $time_limit;
    public $create_date;
    public $update_date;

    function __construct($data) {
        $date = date("Y-m-d H:i:s");
        $json = '{}';

        if (is_array($data['json']))
        {
            $json = json_encode($data['json'], JSON_UNESCAPED_UNICODE);
        }

        $this->id = $data['id'] ?? 0;
        $this->parent_folder_id = $data['parent_folder_id'] ?? 0;
        $this->teacher_id = $data['teacher_id'] ?? 0;
        $this->title_no = $data['title_no'] ?? 0;
        $this->language_type = $data['language_type'] ?? 0;
        $this->title = $data['title'] ?? '';
        $this->json = $json;
        $this->max_score = $data['max_score'] ?? 0;
        $this->calc_correct_answer_rate = $data['calc_correct_answer_rate'] ?? 0;
        $this->open_date = (!empty($data['open_date'])) ? $data['open_date'] : PeriodHelper::PERIOD_OPEN_DATE;
        $this->expire_date = (!empty($data['expire_date'])) ? $data['expire_date'] : PeriodHelper::PERIOD_EXPIRE_DATE;
        $this->time_limit = $data['time_limit'] ?? 0;
        $this->create_date = $data['create_date'] ?? $date;
        $this->update_date = $data['update_date'] ?? $date;
    }

    public function irregularDateFix() {
        $this->open_date = self::irregularOpenDateFix($this->open_date);
        $this->expire_date = self::irregularExpireDateFix($this->expire_date);
    }

    /**
     * @param $openDate
     * @return DateTimeImmutable|string
     * @throws \Exception
     */
    public static function irregularOpenDateFix($openDate) {
        if (new DateTimeImmutable($openDate) < new DateTimeImmutable('1900-01-01')) {
            $openDate = PeriodHelper::PERIOD_OPEN_DATE;
        }

        // 「00-1-11-30 00:00」 対応
        if (preg_match("/\d*\-\d*\-\d*\-\d*/", $openDate)) {
            $openDate = PeriodHelper::PERIOD_OPEN_DATE;
        }

        return $openDate;
    }

    /**
     * @param $expireDate
     * @return DateTimeImmutable|string
     * @throws \Exception
     */
    public static function irregularExpireDateFix($expireDate) {
        if (new DateTimeImmutable($expireDate) < new DateTimeImmutable('1900-01-01')) {
            $expireDate = PeriodHelper::PERIOD_EXPIRE_DATE;
        }

        if (preg_match("/\d*\-\d*\-\d*\-\d*/", $expireDate)) {
            $expireDate = PeriodHelper::PERIOD_EXPIRE_DATE;
        }

        return $expireDate;
    }
}