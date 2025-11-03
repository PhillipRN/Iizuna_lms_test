<?php

namespace IizunaLMS\Commands;

use IizunaLMS\Datas\JsonQuizResultStatistics;
use IizunaLMS\Datas\JsonQuizResultSummary;
use IizunaLMS\Models\JsonQuizModel;
use IizunaLMS\Models\JsonQuizResultModel;
use IizunaLMS\Models\JsonQuizResultStatisticsModel;
use IizunaLMS\Models\JsonQuizResultSummaryModel;
use IizunaLMS\Helpers\PDOHelper;

class CorrectAnswerRate
{
    /**
     * @return void
     */
    public function SummaryAndRegist()
    {
        $JsonQuizModel = new JsonQuizModel();
        $records = $JsonQuizModel->GetsByKeyValue('calc_correct_answer_rate', 1);

        foreach ($records as $record) {
            $resultData = $this->CompileQuestionResultData($record);
            $statisticsData = $this->CompileQuestionStatisticsData($record);

            // トランザクション開始
            PDOHelper::GetPDO()->beginTransaction();

            if (!$this->AddOrUpdateJsonQuizResultSummary($resultData))
            {
                PDOHelper::GetPDO()->rollBack();
                continue;
            }

            if (!$this->AddOrUpdateJsonQuizResultStatistics($statisticsData))
            {
                PDOHelper::GetPDO()->rollBack();
                continue;
            }

            // 登録できたら json_quiz テーブルの calc_correct_answer_rate フラグを落とす
            $updateJsonQuizResult = $JsonQuizModel->Update([
                'id' => $resultData['json_quiz_id'],
                'calc_correct_answer_rate' => 0,
                'update_date' => date("Y-m-d H:i:s")
            ]);

            // トランザクションコミット
            if ($updateJsonQuizResult) {
                PDOHelper::GetPDO()->commit();
            }
            else {
                PDOHelper::GetPDO()->rollBack();
            }
        }
    }

    /**
     * 集計した結果を登録更新する
     * @param $resultData
     * @return bool|void
     */
    private function AddOrUpdateJsonQuizResultSummary($resultData)
    {
        // json_quiz_result_summary のレコードチェック
        $JsonQuizResultSummaryModel = new JsonQuizResultSummaryModel();
        $jsonQuizResultSummaryRecords = $JsonQuizResultSummaryModel->GetByKeyValue('json_quiz_id', $resultData['json_quiz_id']);

        // json_quiz_result_summary に既に json_quiz_id が登録されている場合はUPDATE、ない場合はINSERT
        if (empty($jsonQuizResultSummaryRecords)) {
            $jsonQuizResultSummary = new JsonQuizResultSummary($resultData);
            return $JsonQuizResultSummaryModel->Add($jsonQuizResultSummary);
        }
        else {
            return $JsonQuizResultSummaryModel->Update([
                'id' => $jsonQuizResultSummaryRecords['id'],
                'average' => $resultData['average'],
                'highest_score' => $resultData['highest_score'],
                'lowest_score' => $resultData['lowest_score'],
                'correct_answer_rates_json' => $resultData['correct_answer_rates_json'],
                'update_date' => date("Y-m-d H:i:s")
            ]);
        }
    }

    /**
     * 統計画面用のデータを登録更新する
     * @param $statisticsData
     * @return bool|void
     */
    private function AddOrUpdateJsonQuizResultStatistics($statisticsData)
    {
        // json_quiz_result_summary のレコードチェック
        $JsonQuizResultStatisticsModel = new JsonQuizResultStatisticsModel();
        $jsonQuizResultStatisticsRecords = $JsonQuizResultStatisticsModel->GetByKeyValue('json_quiz_id', $statisticsData['json_quiz_id']);

        // json_quiz_result_statistics に既に json_quiz_id が登録されている場合はUPDATE、ない場合はINSERT
        if (empty($jsonQuizResultStatisticsRecords)) {
            $jsonQuizResultStatistics = new JsonQuizResultStatistics($statisticsData);
            return $JsonQuizResultStatisticsModel->Add($jsonQuizResultStatistics);
        }
        else {
            return $JsonQuizResultStatisticsModel->Update([
                'id' => $jsonQuizResultStatisticsRecords['id'],
                'answer_rates_json' => $statisticsData['answer_rates_json'],
                'update_date' => date("Y-m-d H:i:s")
            ]);
        }
    }

    /**
     * @param $record
     * @return array
     */
    private function CompileQuestionResultData($record)
    {
        // みんなの結果
        // 平均点
        // 最高点
        // 最低点
        $data = json_decode($record['json'], true);
        $questions = $data['questions'];

        // 問題ごとの正答率集計用
        $correctNums = [];

        foreach ($questions as $question)
        {
            if ($question['question_type'] == 'page_break_item') continue;

            $correctNums[ $question['question_id'] ] = 0;
        }

        $JsonQuizResultModel = new JsonQuizResultModel();
        $jsonQuizResults = $JsonQuizResultModel->GetsByKeyValue('json_quiz_id', $record['id']);

        $count = 0;

        // 回答データを集計する
        $average = 0;
        $sumScore = 0;
        $highestScore = 0;
        $lowestScore = (empty($jsonQuizResults)) ? 0 : $record['max_score'];

        foreach ($jsonQuizResults as $jsonQuizResult)
        {
            // 最初の回答以外はスキップ
            if (empty($jsonQuizResult['is_first_result'])) continue;

            ++$count;
            $score = $jsonQuizResult['score'];

            // 平均点計算用に加算
            $sumScore += $score;

            // 最高点更新
            if ($highestScore < $score) $highestScore = $score;

            // 最低点更新
            if ($lowestScore > $score) $lowestScore = $score;

            $answers = json_decode($jsonQuizResult['answers_json'], true);

            // 問題ごとの正答率を集計する
            foreach ($questions as $question)
            {
                // page_break_item はスキップ
                if ($question['question_type'] == 'page_break_item') continue;

                $question_id = $question['question_id'];

                if (empty($answers[$question_id])) continue;

                $answer = $answers[$question_id];

                if (empty($answer['isCorrect'])) continue;

                ++$correctNums[$question_id];
            }
        }

        // 平均点を計算し百分率にする
        if (!empty($sumScore)) {
            $average = round($sumScore / $count * 100);
        }

        // 正答率を計算する
        $correctAnswerRatesJson = [];
        foreach ($correctNums as $questionId => $correctNum) {
            $correctAnswerRatesJson[$questionId] = (empty($correctNum)) ? 0 : round($correctNum / $count * 100);
        }

        return [
            'json_quiz_id' => $record['id'],
            'average' => $average,
            'highest_score' => $highestScore,
            'lowest_score' => $lowestScore,
            'correct_answer_rates_json' => json_encode($correctAnswerRatesJson, JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * @param $record
     * @return array
     */
    private function CompileQuestionStatisticsData($record)
    {
        $data = json_decode($record['json'], true);
        $questions = $data['questions'];

        // 分析用の入れ物を準備
        $statisticsData = [];
        $correctNums = [];

        foreach ($questions as $question)
        {
            if ($question['question_type'] == 'page_break_item') continue;

            $questionId = $question['question_id'];
            $statisticsData[ $questionId ] = [];
            $correctNums[ $questionId ] = 0;

            // 通常解答
            foreach ($question['answers'] as $answer)
            {
                $statisticsData[ $questionId ][] = new StatisticsRecordData( $answer['answer_text'], $answer['weight'] == 100 );
            }

            // 別解
            if (!empty($question['other_answers']))
            {
                foreach ($question['other_answers'] as $otherAnswer)
                {
                    $statisticsData[ $questionId ][] = new StatisticsRecordData( $otherAnswer, true );
                }
            }

            // 解答なし
            $statisticsData[ $questionId ][] = new StatisticsRecordData( '', false );
        }

        $JsonQuizResultModel = new JsonQuizResultModel();
        $jsonQuizResults = $JsonQuizResultModel->GetsByKeyValue('json_quiz_id', $record['id']);

        $count = 0;

        foreach ($jsonQuizResults as $jsonQuizResult)
        {
            // 最初の回答以外はスキップ
            if (empty($jsonQuizResult['is_first_result'])) continue;

            ++$count;

            // 各ユーザーごとの json_quiz_id のテストの結果
            $userAnswers = json_decode($jsonQuizResult['answers_json'], true);

            // 問題ごとの正答率を集計する
            foreach ($questions as $question)
            {
                if ($question['question_type'] == 'page_break_item') continue;

                $questionId = $question['question_id'];
                $userAnswer = $userAnswers[$questionId];

                // ユーザーの回答を集計する
                $isCount = false;
                foreach ($statisticsData[ $questionId ] as $key => $statisticsRecord)
                {
                    if ($statisticsRecord->answer != $userAnswer['answer']) continue;

                    $statisticsData[ $questionId ][ $key ]->answerCount += 1;
                    $isCount = true;
                }

                // 登録のない回答の場合は回答を追加する
                if (!$isCount)
                {
                    $newStatisticsRecord = new StatisticsRecordData( $userAnswer['answer'], false );
                    $newStatisticsRecord->answerCount += 1;

                    $statisticsData[ $questionId ][] = $newStatisticsRecord;
                }

                if (!empty($userAnswer['isCorrect'])) ++$correctNums[ $questionId ];
            }
        }

        return [
            'json_quiz_id' => $record['id'],
            'answer_rates_json' => json_encode([
                'total' => $count,
                'statisticsData' => $statisticsData,
                'correctNums' => $correctNums
            ], JSON_UNESCAPED_UNICODE),
        ];
    }
}
