<?php

namespace IizunaLMS\Onigiri;

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\SessionHelper;
use IizunaLMS\Models\OnigiriJsonQuizModel;
use IizunaLMS\Models\OnigiriJsonQuizResultModel;
use IizunaLMS\Onigiri\Data\OnigiriJsonQuizResult;

class OnigiriJsonQuizRegister
{
    public function RegisterResult($studentId, $param)
    {
        $result = [
            'result_id' => 0,
        ];

        $quizId = 0;

        if (empty($param['quiz_id'])) {
            return [ 'error' => Error::ERROR_ONIGIRI_JSON_QUIZ_RESULT_INVALID_PARAMETER ];
        }
        else {
            $quizId = $param['quiz_id'];
        }


        $onigiriJsonQuiz = (new OnigiriJsonQuizModel())->GetById($quizId);

        // 先生が確認できるテストかチェックする
        // 1. 回答済みの場合は false
        // 2. 未回答で且つ期間内にテストを開始している場合 true
        $is_first_result = false;
        $userResults = (new OnigiriJsonQuizResultModel())->GetsUsersQuizResult($onigiriJsonQuiz['id'], $studentId);

        if (empty($userResults))
        {
            // 期間内にテストを開始している場合
            $isExpired = $param['is_expired'] ?? 0;

            if (empty($isExpired)) $is_first_result = true;
        }

        // 回答を参照しやすいように整える
        $userAnswers = [];
        if (!empty($param['answers']))
        {
            foreach ($param['answers'] as $answer)
            {
                $userAnswers[] = $answer;
            }
        }

        // スコア換算
        $questions = json_decode($onigiriJsonQuiz['json'], true);
        $checkResult = $this->CheckCorrectAnswer($questions, $userAnswers);

        // データ登録
        $onigiriJsonQuizResult = new OnigiriJsonQuizResult([
            'onigiri_json_quiz_id' => $onigiriJsonQuiz['id'],
            'student_id' => $studentId,
            'answers_json' => json_encode($checkResult['answers'], JSON_UNESCAPED_UNICODE),
            'score' => $checkResult['score'],
            'is_first_result' => ($is_first_result) ? 1 : 0
        ]);

        PDOHelper::GetPDO()->beginTransaction();

        // calc_correct_answer_rate フラグを作る
        $updateOnigiriJsonQuizResult = (new OnigiriJsonQuizModel())->Update([
            'id' => $onigiriJsonQuiz['id'],
            'calc_correct_answer_rate' => 1,
            'update_date' => date("Y-m-d H:i:s")
        ]);

        if (empty($updateOnigiriJsonQuizResult)) {
            $result['error'] = Error::ERROR_ONIGIRI_JSON_QUIZ_RESULT_UPDATE_JSON_QUIZ;
            PDOHelper::GetPDO()->rollBack();
        }
        else {
            // 結果登録
            $addResult = (new OnigiriJsonQuizResultModel)->Add($onigiriJsonQuizResult);

            if (empty($addResult)) {
                $result['error'] = Error::ERROR_ONIGIRI_JSON_QUIZ_RESULT_ADD_FAILED;
                PDOHelper::GetPDO()->rollBack();
            }
            else {
                $result['result_id'] = PDOHelper::GetLastInsertId(PDOHelper::GetPDO());
                PDOHelper::GetPDO()->commit();
            }
        }

        return $result;
    }

    /**
     * @param $str
     * @return array|string|string[]|null
     */
    private function ReplaceUnneededTagsAndWhiteSpace($str)
    {
        $str = strip_tags($str);

        $str = preg_replace('/&nbsp;/u', '', $str);
        // 取り切れていない C2 A0 (NBSP) のみを除去
        $str = preg_replace("/\x{00A0}/u", '', $str);

        // FIXME マスターそのもののデータの末尾に余計な空白が大量に入っているため、暫定処置。理想はマスター側を修正し、プログラムで除去する処理はしないようにしたい。
        return preg_replace("/\A[\\x0-\x20\x7F\xC2\xA0\xE3\x80\x80]++|[\\x0-\x20\x7F\xC2\xA0\xE3\x80\x80]++\z/u", '', $str);
    }

    /**
     * @param $str
     * @return array|string|string[]|null
     */
    private function ReplaceSingleQuote($str)
    {
        return preg_replace("/’/u", "'", $str);
    }

    private function CheckCorrectAnswer($questions, $userAnswers)
    {
        $result = [
            'score' => 0,
            'answers' => []
        ];

        for ($i=0; $i<count($questions); ++$i)
        {
            $question = $questions[$i];

            $answer = $this->ReplaceUnneededTagsAndWhiteSpace($question['answer']);
            $answer = $this->ReplaceSingleQuote($answer);

            $userAnswer = $userAnswers[ $i ] ?? '';

            $userAnswer = $this->ReplaceUnneededTagsAndWhiteSpace($userAnswer);
            $userAnswer = $this->ReplaceSingleQuote($userAnswer);

            $isCorrect = ($answer == $userAnswer);

            $result['answers'][] = [
                'isCorrect' => ($isCorrect) ? 1 : 0,
                'answer' => $userAnswer
            ];

            if ($isCorrect) ++$result['score'];
        }

        return $result;
    }
}