<?php

namespace IizunaLMS\Commands;

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Models\OnigiriJsonQuizModel;
use IizunaLMS\Models\OnigiriJsonQuizResultModel;
use IizunaLMS\Models\OnigiriJsonQuizResultStatisticsModel;
use IizunaLMS\Onigiri\Data\OnigiriJsonQuizResultStatistics;

class OnigiriJsonQuizCorrectAnswerRate
{
    /**
     * @return void
     */
    public function SummaryAndRegist()
    {
        $OnigiriJsonQuizModel = new OnigiriJsonQuizModel();
        $records = $OnigiriJsonQuizModel->GetsByKeyValue('calc_correct_answer_rate', 1);

        foreach ($records as $record) {
            $statisticsData = $this->CompileQuestionStatisticsData($record);

            // トランザクション開始
            PDOHelper::GetPDO()->beginTransaction();

            if (!$this->AddOrUpdateJsonQuizResultStatistics($statisticsData))
            {
                PDOHelper::GetPDO()->rollBack();
                continue;
            }

            // 登録できたら json_quiz テーブルの calc_correct_answer_rate フラグを落とす
            $updateOnigiriJsonQuizResult = $OnigiriJsonQuizModel->Update([
                'id' => $record['id'],
                'calc_correct_answer_rate' => 0,
                'update_date' => date("Y-m-d H:i:s")
            ]);

            // トランザクションコミット
            if ($updateOnigiriJsonQuizResult) {
                PDOHelper::GetPDO()->commit();
            }
            else {
                PDOHelper::GetPDO()->rollBack();
            }
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
        $OnigiriJsonQuizResultStatisticsModel = new OnigiriJsonQuizResultStatisticsModel();
        $jsonQuizResultStatisticsRecords = $OnigiriJsonQuizResultStatisticsModel->GetByKeyValue('onigiri_json_quiz_id', $statisticsData['onigiri_json_quiz_id']);

        // onigiri_json_quiz_result_statistics に既に onigiri_json_quiz_id が登録されている場合はUPDATE、ない場合はINSERT
        if (empty($jsonQuizResultStatisticsRecords)) {
            $data = new OnigiriJsonQuizResultStatistics($statisticsData);
            return $OnigiriJsonQuizResultStatisticsModel->Add($data);
        }
        else {
            return $OnigiriJsonQuizResultStatisticsModel->Update([
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
    private function CompileQuestionStatisticsData($record)
    {
        $questions = json_decode($record['json'], true);

        // 分析用の入れ物を準備
        $statisticsData = [];
        $correctNums = [];

        // $questions を for で回す
        for ($i=0; $i<count($questions); ++$i)
        {
            $question = $questions[$i];
            $questionId = $i;

            $statisticsData[ $questionId ] = [];
            $correctNums[ $questionId ] = 0;

            // 選択肢がない場合
            if (empty($question['choices']))
            {
                $answer = $this->ReplaceUnneededTagsAndWhiteSpace($question['answer']);
                $statisticsData[ $questionId ][] = new StatisticsRecordData( $answer, true );
            }

            // 選択肢がある場合
            else
            {
                foreach ($question['choices'] as $choiceData)
                {
                    $choice = $this->ReplaceUnneededTagsAndWhiteSpace($choiceData['text']);
                    $statisticsData[ $questionId ][] = new StatisticsRecordData($choice, !empty($choiceData['isCorrect']));
                }
            }

            // 解答なし
            $statisticsData[ $questionId ][] = new StatisticsRecordData( '', false );
        }

        $results = (new OnigiriJsonQuizResultModel())->GetsByKeyValue('onigiri_json_quiz_id', $record['id']);

        $count = 0;

        foreach ($results as $result)
        {
            // 最初の回答以外はスキップ
            if (empty($result['is_first_result'])) continue;

            ++$count;

            // 各ユーザーごとの json_quiz_id のテストの結果
            $userAnswers = json_decode($result['answers_json'], true);

            // 問題ごとの正答率を集計する
            for ($i=0; $i<count($questions); ++$i)
            {
                $questionId = $i;
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
            'onigiri_json_quiz_id' => $record['id'],
            'answer_rates_json' => json_encode([
                'total' => $count,
                'statisticsData' => $statisticsData,
                'correctNums' => $correctNums
            ], JSON_UNESCAPED_UNICODE),
        ];
    }



    /**
     * @param $str
     * @return array|string|string[]|null
     */
    private function ReplaceUnneededTagsAndWhiteSpace($str)
    {
        $str = strip_tags($str);

        // FIXME マスターそのもののデータの末尾に余計な空白が大量に入っているため、暫定処置。理想はマスター側を修正し、プログラムで除去する処理はしないようにしたい。
        $str = preg_replace('/&nbsp;/u', '', $str);
        return preg_replace("/\A[\\x0-\x20\x7F\xC2\xA0\xE3\x80\x80]++|[\\x0-\x20\x7F\xC2\xA0\xE3\x80\x80]++\z/u", '', $str);
    }
}