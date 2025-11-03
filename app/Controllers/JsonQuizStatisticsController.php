<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Models\JsonQuizResultStatisticsModel;

class JsonQuizStatisticsController
{
    const SORT_KEY_NONE = 0;
    const SORT_KEY_CORRECT = 'correct';
    const SORT_KEY_INCORRECT = 'incorrect';

    public function IsStatisticsData($jsonQuizId)
    {
        $JsonQuizResultStatisticsModel = $this->GetJsonQuizResultStatisticsModel();
        $record = $JsonQuizResultStatisticsModel->GetByKeyValue('json_quiz_id', $jsonQuizId);

        return (!empty($record));
    }

    public function GetStatisticsData($jsonQuizId)
    {
        $JsonQuizResultStatisticsModel = $this->GetJsonQuizResultStatisticsModel();
        $record = $JsonQuizResultStatisticsModel->GetByKeyValue('json_quiz_id', $jsonQuizId);

        if (empty($record)) return null;

        $answerRates = json_decode($record['answer_rates_json'], true);
        $total = $answerRates['total'];

        foreach ($answerRates['statisticsData'] as $questionId => $questionAnswerRates)
        {
            for ($i=0; $i<count($questionAnswerRates); ++$i)
            {
                $answerCount = $questionAnswerRates[$i]['answerCount'];
                $rate = ($answerCount == 0) ? 0 : $answerCount / $total * 100;

                $answerRates['statisticsData'][$questionId][$i]['rate'] = floor($rate * 10) / 10;
            }
        }

        // 解答なしを最後に持っていく
        $replaceStatisticsData = [];

        foreach ($answerRates['statisticsData'] as $questionId => $questionAnswerRates)
        {
            $replaceStatisticsData[$questionId] = [];
            $noAnswer = null;

            for ($i=0; $i<count($questionAnswerRates); ++$i)
            {
                if ($questionAnswerRates[$i]['answer'] == "")
                {
                    $noAnswer = $questionAnswerRates[$i];
                }
                else
                {
                    $replaceStatisticsData[$questionId][] = $questionAnswerRates[$i];
                }
            }

            if (!empty($noAnswer)) $replaceStatisticsData[$questionId][] = $noAnswer;
        }

        $answerRates['statisticsData'] = $replaceStatisticsData;

        return $answerRates;
    }

    /**
     * 受講生分析用のデータを取得する
     * @param $jsonQuiz
     * @param $students
     * @param $jsonQuizResults
     * @return array
     */
    public function GetStatisticsStudents($jsonQuiz, $students, $jsonQuizResults)
    {
        $records = [];
        $jsonQuizData = json_decode($jsonQuiz['json'], true);
        $questions = $jsonQuizData['questions'];

        // ヘッダーを集める
        $header = ['name', 'student_number', 'id', 'submitted'];

        foreach ($questions as $question)
        {
            if ($question['question_type'] == 'page_break_item') continue;

            $header[] = "（{$question['question_id']}）{$question['question_text']}";
            $header[] = 1;
        }

        $header[] = 'n correct';
        $header[] = 'n incorrect';
        $header[] = 'score';

        // レコードを集める
        foreach ($jsonQuizResults as $jsonQuizResult)
        {
            $id = $jsonQuizResult['student_id'];
            $name = (empty($students[ $id ])) ? "" : $students[ $id ]['name'];
            $studentNumber = (empty($students[ $id ])) ? "" : $students[ $id ]['student_number'];

            $recordData = [
                'name' => $name,
                'student_number' => $studentNumber,
                'id' => $id,
                'submitted' => $jsonQuizResult['create_date'],
                'answers' => [],
                'n correct' => 0,
                'n incorrect' => 0,
                'score' => 0
            ];

            $answers = json_decode($jsonQuizResult['answers_json'], true);

            foreach ($questions as $question)
            {
                if ($question['question_type'] == 'page_break_item') continue;

                $questionId = $question['question_id'];
                $statisticsData[ $questionId ] = [];
                $correctNums[ $questionId ] = 0;

                $answer = $answers[ $questionId ];
                $recordData['answers'][] = $answer;

                if ($answer['isCorrect'])
                {
                    ++$recordData['n correct'];
                    ++$recordData['score'];
                }
                else
                {
                    ++$recordData['n incorrect'];
                }
            }

            $record = [
                $recordData['name'],
                $recordData['student_number'],
                $recordData['id'],
                $recordData['submitted']
            ];

            foreach ($recordData['answers'] as $answer)
            {
                $record[] = $answer['answer'];
                $record[] = $answer['isCorrect'] ? 1 : 0;
            }

            $record[] = $recordData['n correct'];
            $record[] = $recordData['n incorrect'];
            $record[] = $recordData['score'];

            $records[] = $record;
        }

        return [
            'headers' => $header,
            'records' => $records
        ];
    }

    /**
     * アイテム分析用のデータを取得する
     * @param $jsonQuiz
     * @param $students
     * @param $jsonQuizResults
     * @return array
     */
    public function GetStatisticsItems($jsonQuiz, $jsonQuizResults)
    {
        $records = [];
        $jsonQuizData = json_decode($jsonQuiz['json'], true);
        $questions = $jsonQuizData['questions'];

        // ヘッダーを集める
        $header = [
            'Question Title',
            'Answered Student Count',
            'Quiz Question Count',
            'Correct Student Count',
            'Wrong Student Count',
            'Correct Student Ratio',
            'Wrong Student Ratio'
        ];

        $questionCount = 0;
        foreach ($questions as $question)
        {
            if ($question['question_type'] == 'page_break_item') continue;

            ++$questionCount;
        }

        $summary = [];
        foreach ($questions as $question)
        {
            if ($question['question_type'] == 'page_break_item') continue;

            $summary[ $question['question_id'] ] = [
                'Question Title' => "（{$question['question_id']}）{$question['question_text']}",
                'Answered Student Count' => count($jsonQuizResults),
                'Quiz Question Count' => $questionCount,
                'Correct Student Count' => 0,
                'Wrong Student Count' => 0,
                'Correct Student Ratio' => 0,
                'Wrong Student Ratio' => 0
            ];
        }

        $totalRecords = count($jsonQuizResults);

        // 集計する
        foreach ($jsonQuizResults as $jsonQuizResult)
        {
            $answers = json_decode($jsonQuizResult['answers_json'], true);

            foreach ($questions as $question)
            {
                if ($question['question_type'] == 'page_break_item') continue;

                $questionId = $question['question_id'];
                $answer = $answers[ $questionId ];

                if ($answer['isCorrect']) $summary[ $questionId ]['Correct Student Count']++;
                else                      $summary[ $questionId ]['Wrong Student Count']++;
            }
        }

        //Ratio を計算する
        foreach ($summary as $key => $record)
        {
            $correctNum = $record['Correct Student Count'];
            $wrongNum = $record['Wrong Student Count'];

            $summary[ $key ]['Correct Student Ratio'] = (empty($correctNum)) ? 0 : $correctNum / $totalRecords;
            $summary[ $key ]['Wrong Student Ratio'] = (empty($wrongNum)) ? 0 : $wrongNum / $totalRecords;
        }

        return [
            'headers' => $header,
            'records' => $summary
        ];
    }

    /**
     * ページ分析用のデータを取得する
     * @param array $jsonQuiz
     * @param array $jsonQuizResults
     * @param $sortKey
     * @return array
     */
    public function GetStatisticsPageData(array $jsonQuiz, array $jsonQuizResults, $sortKey=self::SORT_KEY_NONE)
    {
        $baseQuestions = $jsonQuiz['questions'];

        // 正答率を集計する
        $correctSummary = [];
        foreach ($jsonQuizResults as $jsonQuizResult)
        {
            $answers = json_decode($jsonQuizResult['answers_json'], true);

            foreach ($baseQuestions as $question)
            {
                if ($question['question_type'] == 'page_break_item') continue;

                $questionId = $question['question_id'];
                $answer = $answers[ $questionId ];

                if (!isset($correctSummary[ $questionId ]))
                {
                    $correctSummary[ $questionId ] = [
                        'correct_count' => 0,
                        'incorrect_count' => 0
                    ];
                }

                if ($answer['isCorrect']) $correctSummary[ $questionId ]['correct_count']++;
                else                      $correctSummary[ $questionId ]['incorrect_count']++;
            }
        }

        // ソート可能にデータを整形する
        $pageData = [];
        $partId = 0;
        $partText = '';
        foreach ($baseQuestions as $question)
        {
            if ($question['question_type'] == 'page_break_item') {
                ++$partId;
                $partText = $question['question_text'];
                continue;
            }

            $pageData[] = [
                'question_id' => $question['question_id'],
                'question_type' => $question['question_type'],
                'part_id' => $partId,
                'part_text' => $partText,
                'question_text' => $question['question_text'],
                'correct_count' => $correctSummary[ $question['question_id'] ]['correct_count'],
                'incorrect_count' => $correctSummary[ $question['question_id'] ]['incorrect_count']
            ];
        }

        $sortColumn = '';
        switch ($sortKey)
        {
            case self::SORT_KEY_CORRECT:
                $sortColumn = 'correct_count';
                break;
            case self::SORT_KEY_INCORRECT:
                $sortColumn = 'incorrect_count';
                break;
        }

        // ソートする
        if (!empty($sortColumn))
        {
            // 並び替えの基準を取得
            $sortValues = array_column($pageData, $sortColumn);
            $sortSubValues = array_column($pageData, 'question_id');

            // 正答率 or 誤答率の降順且つ問題番号の昇順に並び替える.
            array_multisort(
                $sortValues, SORT_DESC,
                $sortSubValues, SORT_ASC, SORT_NUMERIC,
                $pageData);
        }

        return $pageData;
    }

    private ?JsonQuizResultStatisticsModel $_JsonQuizResultStatisticsModel = null;

    private function GetJsonQuizResultStatisticsModel(): JsonQuizResultStatisticsModel
    {
        if ($this->_JsonQuizResultStatisticsModel != null) return $this->_JsonQuizResultStatisticsModel;

        $this->_JsonQuizResultStatisticsModel = new JsonQuizResultStatisticsModel();

        return $this->_JsonQuizResultStatisticsModel;
    }
}