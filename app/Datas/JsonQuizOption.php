<?php
namespace IizunaLMS\Datas;

class JsonQuizOption
{
    const SORT_RANDOM = 0;
    const SORT_ASC = 1;

    public $json_quiz_id;
    public $mode;
    public $range_type;
    public $page_ranges;
    public $question_number_ranges;
    public $midasi_number_ranges;
    public $section_numbers;
    public $midasi_numbers;
    public $sort;
    public $is_show_question_no;
    public $is_show_midasi_no;
    public $manual_is_individual;
    public $manual_syubetu_numbers;
    public $manual_change_display;
    public $manual_frequencies;
    public $manual_syomon_numbers;
    public $manual_individual_selected_json;

    function __construct($jsonQuizId, $data) {
        $this->json_quiz_id = !empty($jsonQuizId) ? $jsonQuizId : null;
        $this->mode = (isset($data['mode'])) ? (int)$data['mode'] : 0;
        $this->range_type = $data['rangeType'] ?? '';
        $this->page_ranges = $this->CreateRanges($data, 'page');
        $this->question_number_ranges = $this->CreateRanges($data, 'number');
        $this->midasi_number_ranges = $this->CreateRanges($data, 'midasi_number');
        $this->section_numbers = $data['sectionNos'] ?? '';
        $this->midasi_numbers = $data['midasiNos'] ?? '';
        $this->sort = $this->CreateSort($data);
        $this->is_show_question_no = $this->CreateIsShowQuestionNo($data);
        $this->is_show_midasi_no = $this->CreateIsShowMidasiNo($data);
        $this->manual_is_individual = $this->CreateIsIndividual($data);
        $this->manual_syubetu_numbers = $this->CreateManualSyubetuNumbers($data);
        $this->manual_change_display = (isset($data['changeDisplay'])) ? (int)$data['changeDisplay'] : 0;
        $this->manual_frequencies = (isset($data['frequency'])) ? $this->CreateManualFrequencies($data) : '';
        $this->manual_syomon_numbers = $data['syomonNos'] ?? '';
        $this->manual_individual_selected_json = $data['individualSelected'] ?? '';
    }

    /**
     * @param $data
     * @param $prefix
     * @return string
     */
    private function CreateRanges($data, $prefix): string
    {
        $ranges = [];

        for ($i=1; $i<=10; ++$i) {
            $from = (isset($data["{$prefix}_from_" . $i])) ? $data["{$prefix}_from_" . $i] : '';
            $to   = (isset($data["{$prefix}_to_" . $i]))   ? $data["{$prefix}_to_" . $i] : '';

            if ($from == '' && $to == '') continue;

            $ranges[] = "$from:$to";
        }

        return implode(',', $ranges);
    }

    /**
     * @param $data
     * @return int
     */
    private function CreateSort($data): int
    {
        return ($data['sort'] == 'random') ? self::SORT_RANDOM : self::SORT_ASC;
    }

    /**
     * @param $data
     * @return int
     */
    private function CreateIsShowQuestionNo($data): int
    {
        return (isset($data['showQuestionNo']) && $data['showQuestionNo']) ? 1 : 0;
    }

    /**
     * @param $data
     * @return int
     */
    private function CreateIsShowMidasiNo($data): int
    {
        return (isset($data['showMidasiNo']) && $data['showMidasiNo']) ? 1 : 0;
    }

    /**
     * @param $data
     * @return int
     */
    private function CreateIsIndividual($data): int
    {
        return (isset($data['selectIndividual']) && $data['selectIndividual']) ? 1 : 0;
    }

    /**
     * @param $data
     * @return string
     */
    private function CreateManualSyubetuNumbers($data)
    {
        $syubetuNos = [];

        foreach ($data as $key => $val)
        {
            if (empty($val)) continue;

            if (preg_match("/^syubetu_num_([0-9_]+)$/", $key,$matches))
            {
                $syubetuNos[] = "$matches[1]:$val";
            }
        }

        return implode(',', $syubetuNos);
    }

    /**
     * @param $data
     * @return string
     */
    private function CreateManualFrequencies($data)
    {
        return implode(',', $data['frequency']);
    }

    /**
     * @param $data
     * @return array
     */
    public static function ExplodeRanges($data)
    {
        if (empty($data)) return [];

        $tempRanges = explode(',', $data);

        if (empty($tempRanges)) return [];

        $questionNumberRanges = [];
        foreach ($tempRanges as $key => $range)
        {
            $questionNumberRanges[] = explode(':', $range);
        }

        return $questionNumberRanges;
    }

    /**
     * @param $numbers
     * @param $prefix
     * @return false|string
     */
    public static function CreateJsonForNumbers($numbers, $prefix)
    {
        if (empty($numbers)) return json_encode( [] );

        $numbersArray = explode(',', $numbers);
        $tempNumbers = [];

        if (!empty($numbersArray))
        {
            foreach ($numbersArray as $key => $number)
            {
                $tempNumbers["{$prefix}{$number}"] = 1;
            }
        }

        return json_encode( $tempNumbers );
    }

    /**
     * @param $numbers
     * @return false|string
     */
    public static function CreateJsonForSyubetuNumbers($numbers)
    {
        if (empty($numbers)) return json_encode( [] );

        $syubetuArray = explode(',', $numbers);
        $tempNumbers = [];

        if (!empty($syubetuArray))
        {
            foreach ($syubetuArray as $key => $syubetu)
            {
                $exploded = explode(':', $syubetu);

                $tempNumbers[$exploded[0]] = $exploded[1];
            }
        }

        return json_encode( $tempNumbers );
    }

    /**
     * @param $numbers
     * @return false|string
     */
    public static function CreateJsonForIndividualSyubetuNumbers($jsonData)
    {
        if (empty($jsonData)) return json_encode( [] );

        $syubetuNumbers = [];

        foreach ($jsonData as $syubetuNumber => $tmpSelected)
        {
            $syubetuNumbers[$syubetuNumber] = count($tmpSelected);
        }

        return json_encode ( $syubetuNumbers );
    }

    /**
     * @param $value
     * @return string
     */
    public static function CreateJsonForFrequencies($value): string
    {
        if (empty($value)) return json_encode( [] );

        return json_encode ( explode(',', $value) );
    }

    /**
     * @param $jsonData
     * @return string
     */
    public static function CreateSelectedShomonnos($jsonData): string
    {
        if (empty($jsonData)) return json_encode( [] );

        $shomonNos = [];

        foreach ($jsonData as $tmpSelected)
        {
            foreach ($tmpSelected as $myShomonno => $val)
            {
                $shomonNos[] = $myShomonno;
            }
        }

        return json_encode ( $shomonNos );
    }
}