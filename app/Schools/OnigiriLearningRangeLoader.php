<?php

namespace IizunaLMS\Schools;

use IizunaLMS\Models\OnigiriLearningRangeModel;
use IizunaLMS\Onigiri\LearningRange;

class OnigiriLearningRangeLoader
{
    private $lmsCodeId;

    private const KEY_GENRE = 'genre';
    private const KEY_LEVEL = 'learning_range_level';
    private const KEY_STAGE = 'stage';
    private const KEY_MAX_STAGE = 'max_stage';
    private const KEY_SEQUENTIAL_NUMBER = 'sequential_number';

    function __construct($lmsCodeId)
    {
        $this->lmsCodeId = $lmsCodeId;
    }

    /**
     * @param $stages
     * @return array
     */
    public static function GenerateGenreNames($stages)
    {
        $genreNames = [];

        foreach ($stages as $genre => $levels)
        {
            switch ($genre)
            {
                case 'toeic':
                case 'english_cert':
                    $genreNames[$genre] = [];
                    break;
            }

            switch ($genre)
            {
                case 'toeic':
                    foreach ($levels as $level => $stages)
                    {
                        $genreNames[$genre][$level] = OnigiriLearningRangeLoader::GetToeicLevelString($level);
                    }
                    break;

                case 'english_cert':
                    foreach ($levels as $level => $stages)
                    {
                        $genreNames[$genre][$level] = OnigiriLearningRangeLoader::GetEnglishCertLevelString($level);
                    }
                    break;
            }
        }

        return $genreNames;
    }

    /**
     * @return array
     */
    public function LoadForUpdatePage()
    {
        $maxStages = (new LearningRange())->GetMaxStages();

        $dataArray = (new OnigiriLearningRangeModel())->GetsByLmsCodeId($this->lmsCodeId);
        $selectedData = [];
        $result = [];

        foreach ($dataArray as $data)
        {
            $genre = $data[self::KEY_GENRE];
            $level = $data[self::KEY_LEVEL];
            $stage = $data[self::KEY_STAGE];

            $selectedData["{$genre}_{$level}_{$stage}"] = 1;
        }

        foreach ($maxStages as $maxStage)
        {
            $genre = $maxStage[self::KEY_GENRE];
            $level = $maxStage[self::KEY_LEVEL];
            $maxStage = $maxStage[self::KEY_MAX_STAGE];

            if (!isset($result[$genre]))
            {
                $result[$genre] = [];
            }

            $result[$genre][$level] = [];

            for ($stage=1; $stage<=$maxStage; ++$stage)
            {
                $result[$genre][$level][$stage] = (!empty($selectedData["{$genre}_{$level}_{$stage}"])) ? 1 : 0;
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function LoadForOnigiriQuizChoicePage()
    {
        $maxStages = (new LearningRange())->GetMaxStages();

        $dataArray = (new OnigiriLearningRangeModel())->GetsByLmsCodeId($this->lmsCodeId);
        $selectedData = [];
        $result = [];

        foreach ($dataArray as $data)
        {
            $genre = $data[self::KEY_GENRE];
            $level = $data[self::KEY_LEVEL];
            $stage = $data[self::KEY_STAGE];

            $selectedData["{$genre}_{$level}_{$stage}"] = 1;
        }

        foreach ($maxStages as $maxStage)
        {
            $genre = $maxStage[self::KEY_GENRE];
            $level = $maxStage[self::KEY_LEVEL];
            $maxStage = $maxStage[self::KEY_MAX_STAGE];

            if (!isset($result[$genre]))
            {
                $result[$genre] = [];
            }

            $result[$genre][$level] = [];

            for ($stage=1; $stage<=$maxStage; ++$stage)
            {
                if (empty($selectedData["{$genre}_{$level}_{$stage}"])) continue;

                $result[$genre][$level][$stage] = 1;
            }

            if (empty($result[$genre][$level])) unset($result[$genre][$level]);
            if (empty($result[$genre])) unset($result[$genre]);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function LoadForIndexPage()
    {
        $dataArray = (new OnigiriLearningRangeModel())->GetsByLmsCodeId($this->lmsCodeId);
        $result = [];

        foreach ($dataArray as $data)
        {
            $genre = $data[self::KEY_GENRE];
            $level = $data[self::KEY_LEVEL];
            $stage = $data[self::KEY_STAGE];

            switch ($genre)
            {
                case OnigiriLearningRangeModel::GENRE_TOEIC:
                case OnigiriLearningRangeModel::GENRE_ENGLISH_CERT:
                    if (!isset($result["{$genre}_{$level}"]))
                    {
                        $result["{$genre}_{$level}"] = [];
                    }
                    $result["{$genre}_{$level}"][$stage] = 1;
                    break;

                default:
                    if (!isset($result[$genre]))
                    {
                        $result[$genre] = [];
                    }
                    $result[$genre][$stage] = 1;
                    break;
            }
        }

        return $result;
    }

    public function LoadForOnigiriQuizCreatePage()
    {
        $dataArray = (new OnigiriLearningRangeModel())->GetsByLmsCodeId($this->lmsCodeId);

        $result = [];

        foreach ($dataArray as $data)
        {
            $genre = $data[self::KEY_GENRE];
            $level = $data[self::KEY_LEVEL];
            $stage = $data[self::KEY_STAGE];

            $result[] = [
                'sequential_number' => $data[self::KEY_SEQUENTIAL_NUMBER],
                'title' => self::GetTitle($genre, $level, $stage),
                'value' => "{$genre}_{$level}_{$stage}",
            ];
        }

        return $result;
    }

    /**
     * @param $genre
     * @param $level
     * @param $stage
     * @return string
     */
    private static function GetTitle($genre, $level, $stage)
    {
        switch ($genre)
        {
            case 'toeic':
                return 'TOEIC ' . OnigiriLearningRangeLoader::GetToeicLevelString($level) . " Stage {$stage}";

            case 'english_cert':
                return '英検 ' . OnigiriLearningRangeLoader::GetEnglishCertLevelString($level) . " Stage {$stage}";

            case 'junior_high_school':
                return "中学1～3年 Stage {$stage}";

            case 'high_school':
                return "高校1～2年 Stage {$stage}";

            case 'college_common_test':
                return "大学受験／共通テスト Stage {$stage}";

            case 'college_standard':
                return "大学受験／標準私大 Stage {$stage}";

            case 'college_elite':
                return "大学受験／難関私大・国公立 Stage {$stage}";
        }

        return '';
    }

    private static function GetToeicLevelString($level)
    {
        return "{$level}点以上";
    }

    private static function GetEnglishCertLevelString($level)
    {
        switch ($level)
        {
            case 1:
                return '1級';
            case 2:
                return '準1級';
            case 3:
                return '2級';
            case 4:
                return '準2級';
            case 5:
                return '3級';
            case 6:
                return '4級';
        }

        return '';
    }

    /**
     * @param $rangeString
     * @return string
     */
    public static function GetTitleByRangeString($rangeString)
    {
        preg_match('/^(.+)_([\d]+)_([\d]+)$/', $rangeString, $matches);

        if (count($matches) < 3) return '';

        $genre = $matches[1];
        $level = $matches[2];
        $stage = $matches[3];

        return self::GetTitle($genre, $level, $stage);
    }
}