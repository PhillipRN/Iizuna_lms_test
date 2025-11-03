<?php

namespace IizunaLMS\EBook;

use IizunaLMS\EBook\Models\EbookExampleModel;
use IizunaLMS\EBook\Models\EbookQuizModel;
use IizunaLMS\EBook\Requests\RequestParamEbookDailyQuiz;
use IizunaLMS\EBook\Requests\RequestParamEbookInformation;
use IizunaLMS\EBook\Requests\RequestParamEbookQuiz;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;

class EbookQuiz
{
    const QUESTION_KIND_CHECK = 1;
    const QUESTION_KIND_TARGET = 2;
    const QUESTION_KIND_CHALLENGE = 3;

    const QUESTION_TYPE_CHOICE = 1; // 選択問題
    const QUESTION_TYPE_SORT = 2;  // 整序問題
    const QUESTION_TYPE_INPUT = 3; // 入力問題

    const QUESTION_LEVEL_EASY = 1;
    const QUESTION_LEVEL_NORMAL = 2;
    const QUESTION_LEVEL_HARD = 3;

    const QUESTION_GENRE_UNCHANGED = 1;
    const QUESTION_GENRE_CHANGED = 2;

    const DAILY_QUIZ_NUMBER = 5;

    const EASY_MAX_CHAPTER = 4;

    private string $instructionMessageFilePath = SYS_DIR . '/instruction_message.json';
    private string $unknownMessage = '';

    /**
     * @param RequestParamEbookQuiz $params
     * @return void
     */
    public function ShowQuiz(RequestParamEbookQuiz $params)
    {
        if (!$params->IsValid()) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_EBOOK_QUIZ_INVALID_PARAMETER);

        $result = $this->GenerateQuiz($params);

        DisplayJsonHelper::ShowAndExit($result);
    }

    /**
     * @param RequestParamEbookQuiz $params
     * @return array[]
     */
    private function GenerateQuiz(RequestParamEbookQuiz $params)
    {
        $records = [];
        if (!empty($params->page))
        {
            $records = (new EbookQuizModel())->GetPageRecords($params->titleNo, $params->page, $params->genres, $params->isInput);
        }
        else
        {
            $questionKinds = [];
            switch ($params->questionKind)
            {
                case self::QUESTION_KIND_CHECK:
                    $questionKinds = [ self::QUESTION_KIND_CHECK ];
                    break;

                case self::QUESTION_KIND_TARGET:
                    $questionKinds = [ self::QUESTION_KIND_TARGET ];
                    break;

                case self::QUESTION_KIND_CHALLENGE:
                    $questionKinds = [ self::QUESTION_KIND_CHALLENGE ];
                    break;

                default:
                    $questionKinds = [ self::QUESTION_KIND_CHECK, self::QUESTION_KIND_TARGET ];
                    break;
            }

            $records = (new EbookQuizModel())->GetsByQuestionKindsAndChapter(
                $params->titleNo,
                $questionKinds,
                $params->chapters,
                $params->isInput
            );
        }

        return $this->ConvertQuizResultData($records, $params->number, $params->isRandom);
    }

    /**
     * @param RequestParamEbookDailyQuiz $params
     * @return void
     */
    public function ShowDailyQuiz(RequestParamEbookDailyQuiz $params)
    {
        if (!$params->IsValid()) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_EBOOK_QUIZ_INVALID_PARAMETER);

        $result = $this->GenerateDailyQuiz($params);

        DisplayJsonHelper::ShowAndExit($result);
    }

    /**
     * @param RequestParamEbookInformation $params
     * @return void
     */
    public function ShowEbookInformation(RequestParamEbookInformation $params)
    {
        if (!$params->IsValid()) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_EBOOK_INFORMATION_INVALID_PARAMETER);

        $quizRecords = (new EbookQuizModel())->GetQuizPages($params->titleNo);
        $voiceRecords = (new EbookExampleModel())->GetVoicePages($params->titleNo);

        $result = $this->ConvertInformationResultData($quizRecords, $voiceRecords);

        DisplayJsonHelper::ShowAndExit($result);
    }

    /**
     * @param RequestParamEbookDailyQuiz $params
     * @return array[]
     */
    private function GenerateDailyQuiz(RequestParamEbookDailyQuiz $params)
    {
        $questionKinds = [];
        switch ($params->level)
        {
            case self::QUESTION_LEVEL_HARD:
                $questionKinds = [ self::QUESTION_KIND_CHECK, self::QUESTION_KIND_CHALLENGE ];
                break;

            default:
                $questionKinds = [ self::QUESTION_KIND_CHECK ];
                break;
        }

        $maxChapter = ($params->level == self::QUESTION_LEVEL_EASY) ? self::EASY_MAX_CHAPTER : null;

        $idRecords = (new EbookQuizModel())->GetIdsByTitleNosAndQuestionKindsAndMaxChapter($params->titleNoArray, $questionKinds, $maxChapter);

        $records = [];

        if (!empty($idRecords))
        {
            $ids = [];
            foreach ($idRecords as $idRecord) $ids[] = $idRecord['id'];

            $records = (new EbookQuizModel())->GetByIDs($ids);
        }

        return $this->ConvertQuizResultData($records, self::DAILY_QUIZ_NUMBER);
    }

    /**
     * @param $records
     * @param $number
     * @param bool $isRandom
     * @return array[]
     */
    private function ConvertQuizResultData($records, $number, $isRandom=true): array
    {
        if (empty($records))
        {
            return Error::GetErrorResultData(Error::ERROR_EBOOK_QUIZ_NO_DATA);
        }

        if ($isRandom) shuffle($records);

        $ids = [];
        $exampleIds = [];
        $filteredData = [];

        foreach ($records as $record)
        {
            // 「例文番号」が同じものはスキップ
            if (in_array($record['example_id'], $exampleIds)) continue;

            $ids[] = $record['id'];
            $exampleIds[] = $record['example_id'];
            $filteredData[] = $record;

            // 指定されている数で終了
            if (count($filteredData) == $number) break;
        }

        // 数が不足している場合は、「例文番号」を加味せずに詰める
        if (count($filteredData) != $number)
        {
            foreach ($records as $record)
            {
                // 既に含まれているものはスキップ
                if (in_array($record['id'], $ids)) continue;

                $ids[] = $record['id'];
                $exampleIds[] = $record['example_id'];
                $filteredData[] = $record;

                // 指定されている数で終了
                if (count($filteredData) == $number) break;
            }
        }

        $tmpRecords = [];
        $recordCount = count($filteredData);
        $isEnough = ($number == 0);

        // instruction をまとめる
        $instructions = [];
        $instructionId = 0; // isRandom = false の場合に使用する

        if ($isRandom == false)
        {
            $tmpRecords[$instructionId] = [
                'header' => $filteredData[0]['instruction'],
                'questions' => []
            ];
        }

        // instruction_id ごとにデータを纏めていく
        for ($i=0; $i<$recordCount; ++$i)
        {
            $record = $filteredData[$i];

            if ($isRandom)
            {
                if (in_array($record['instruction'], $instructions, true)) {
                    // $instructions のキーを instruction_id として使用する
                    $instructionId = array_search($record['instruction'], $instructions, true);
                } else {
                    $instructions[] = $record['instruction'];
                    $instructionId = count($instructions) - 1;
                }

                if (!isset($tmpRecords[$instructionId])) {
                    $tmpRecords[$instructionId] = [
                        'header' => $record['instruction'],
                        'questions' => []
                    ];
                }
            }
            else
            {
                if ($i > 0 && $record['instruction'] != $filteredData[$i - 1]['instruction'])
                {
                    ++$instructionId;

                    $tmpRecords[$instructionId] = [
                        'header' => $record['instruction'],
                        'questions' => []
                    ];
                }
            }

            $isInnerInputField = preg_match('/<zz>/', $record['body_1']) || preg_match('/<zz>/', $record['body_2']);
            $isIncludeHtml = false;
            {
                $tmpBody = preg_replace('/<zz>/', '', $record['body_1'] . $record['body_2']);
                $isIncludeHtml = ($tmpBody != strip_tags($tmpBody));
            }

            $choices = [];
            if (!empty($record['choices'])) {
                $choices = explode('/', $record['choices']);

                if (is_array($choices) && $record['question_type'] == self::QUESTION_TYPE_CHOICE) shuffle($choices);
            }

            $tmpRecords[$instructionId]['questions'][] = [
                'id' => $record['id'],
                'type' => $record['question_type'],
                'body' => $record['body_1'],
                'subBody' => $record['body_2'],
                'choices' => $choices,
                'answers' => explode('/', $record['answers']),
                'is_upper_case_judgement' => $record['is_upper_case_judgement'],
                'voice_file' => $record['voice'],
                'entrance_exam_college_name' => $record['entrance_exam_college_name'],
                'entrance_exam_faculty' => $record['entrance_exam_faculty'],
                'entrance_exam_year' => $record['entrance_exam_year'],
                'subBody_flag' => empty($record['body_2']) ? 0 : 1,
                'outer_input_field_flag' => $isInnerInputField ? 0 : 1,
                'include_html_flag' => $isIncludeHtml ? 1 : 0,
            ];

            // 指定されている数で終了
            // NOTE: EbookExample と違い、階層構造になっているので同じ判定にはできない
            if ($number != 0 && $i+1 == $number)
            {
                $isEnough = true;
                break;
            }
        }

        // 結果表示用にデータを纏め直す
        $result = [];
        foreach ($tmpRecords as $tmpRecord) $result[] = $tmpRecord;

        if ($isEnough) {
            return [
                'result' => $result
            ];
        }
        else {
            $error = Error::GetErrorResultData(Error::ERROR_EBOOK_FLASH_CARD_NOT_ENOUGH);
            return array_merge(['result' => $result], $error);
        }
    }

    /**
     * @param $quizRecords
     * @param $voiceRecords
     * @return array[]
     */
    private function ConvertInformationResultData($quizRecords, $voiceRecords): array
    {
        if (empty($quizRecords) && empty($voiceRecords))
        {
            return Error::GetErrorResultData(Error::ERROR_EBOOK_INFORMATION_NO_DATA);
        }

        $quizPageArray = [];
        foreach ($quizRecords as $record) $quizPageArray[] = $record['page'];

        $voicePageArray = [];
        foreach ($voiceRecords as $record) $voicePageArray[] = $record['page'];

        return [
            'result' => [
                'quizzes' => $quizPageArray,
                'voices' => $voicePageArray,
            ]
        ];
    }

    private $instructionMessageData;
    /**
     * @param $instructionId
     * @return mixed|string
     */
    private function GetInstructionMessage($instructionId)
    {
        if (empty($this->instructionMessageData))
        {
            $jsonString = file_get_contents($this->instructionMessageFilePath);
            $this->instructionMessageData = json_decode($jsonString, true);
        }

        return (isset($this->instructionMessageData[$instructionId]))
            ? $this->instructionMessageData[$instructionId]
            : $this->unknownMessage;
    }
}