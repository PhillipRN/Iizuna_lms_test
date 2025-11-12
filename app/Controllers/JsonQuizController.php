<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Datas\JsonQuiz;
use IizunaLMS\Datas\JsonQuizOption;
use IizunaLMS\Datas\JsonQuizResult;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PageHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\PeriodHelper;
use IizunaLMS\Helpers\SessionHelper;
use IizunaLMS\Models\JsonQuizDeliveryModel;
use IizunaLMS\Models\JsonQuizModel;
use IizunaLMS\Models\JsonQuizOptionModel;
use IizunaLMS\Models\JsonQuizResultModel;
use IizunaLMS\Models\JsonQuizResultSummaryModel;
use IizunaLMS\Models\StudentLmsCodeModel;

class JsonQuizController
{
    /**
     * @param $id
     * @return mixed
     */
    public function Get($id)
    {
        $record = $this->GetJsonQuizModel()->GetAndResultNumById($id);

        if (empty($record)) return [];

        $record['open_date'] = PeriodHelper::ConvertDisplayOpenDate($record['open_date']);
        $record['expire_date'] = PeriodHelper::ConvertDisplayExpireDate($record['expire_date']);

        return $record;
    }

    /**
     * @param $id
     * @return array
     */
    public function GetQuizById($id)
    {
        return $this->GetJsonQuizModel()->GetById($id);
    }

    /**
     * @param $id
     * @return array
     */
    public function GetResultById($id)
    {
        return $this->GetJsonQuizResultModel()->GetById($id);
    }

    /**
     * @param $id
     * @return array
     */
    public function GetResultSummaryByJsonQuizId($jsonQuizId)
    {
        return $this->GetJsonQuizResultSummaryModel()->GetByKeyValue('json_quiz_id', $jsonQuizId);
    }

    /**
     * @param $jsonQuizId
     * @return array
     */
    public function GetsResult($jsonQuizId, $order='ASC')
    {
        return $this->GetJsonQuizResultModel()->GetsByKeyValues(
            ['json_quiz_id', 'is_first_result'],
            [$jsonQuizId, 1],
            [],
            ['id' => $order]
        );
    }

    /**
     * @param $jsonQuizId
     * @param $studentId
     * @return array
     */
    public function GetsUsersResult($jsonQuizId, $studentId)
    {
        return $this->GetJsonQuizResultModel()->GetsUsersQuizResult($jsonQuizId, $studentId);
    }

    /**
     * @param $teacherId
     * @param $page
     * @return array
     */
    public function GetsByUserId($teacherId, $page)
    {
        $offset = ($page > 0) ? ($page - 1) * PageHelper::PAGE_LIMIT : 0;
        $limit = PageHelper::PAGE_LIMIT;
        return $this->GetJsonQuizModel()->GetsByKeyValue('teacher_id', $teacherId, 'DESC', $limit, $offset);
    }

    /**
     * @return int
     */
    public function GetMaxPageNum()
    {
        $count = $this->GetJsonQuizModel()->Count();

        if ($count <= 1) return 1;

        return (int)(floor(($count - 1) / PageHelper::PAGE_LIMIT)) + 1;
    }

    /**
     * @param $teacherId
     * @param $title
     * @param $params
     * @param $languageType
     * @param $json
     * @return array
     */
    public function Add($teacherId, $title, $params, $languageType, $json)
    {
        $JsonQuiz = $this->CreateJsonQuiz($teacherId, $title, $params, $languageType, $json);
        $result = $this->GetJsonQuizModel()->Add($JsonQuiz);

        if (!$result)
        {
            return [
                'error' => Error::ERROR_JSON_QUIZ_ADD_FAILED
            ];
        }

        $jsonQuizId = PDOHelper::GetLastInsertId(PDOHelper::GetPDO());

        $jsonQuizOptionResult = $this->AddOrUpdateJsonQuizOption($jsonQuizId, $params);

        if (!$jsonQuizOptionResult)
        {
            return [
                'error' => Error::ERROR_JSON_QUIZ_OPTION_ADD_FAILED
            ];
        }

        return [
            'error' => Error::ERROR_NONE,
        ];
    }

    /**
     * @param $teacherId
     * @param $title
     * @param $params
     * @param $languageType
     * @param $json
     * @param $jsonQuiz
     * @return array
     */
    public function Update($teacherId, $title, $params, $languageType, $json, $jsonQuiz)
    {
        $JsonQuiz = $this->CreateJsonQuiz($teacherId, $title, $params, $languageType, $json, $jsonQuiz);
        $result = $this->GetJsonQuizModel()->Update($JsonQuiz);

        if (!$result)
        {
            return [
                'error' => Error::ERROR_JSON_QUIZ_ADD_FAILED
            ];
        }

        $jsonQuizOptionResult = $this->AddOrUpdateJsonQuizOption($jsonQuiz['id'], $params);

        if (!$jsonQuizOptionResult)
        {
            return [
                'error' => Error::ERROR_JSON_QUIZ_OPTION_ADD_FAILED
            ];
        }

        return [
            'error' => Error::ERROR_NONE,
        ];
    }

    /**
     * @param $teacherId
     * @param $title
     * @param $params
     * @param $languageType
     * @param $json
     * @param $jsonQuiz
     * @return JsonQuiz
     */
    private function CreateJsonQuiz($teacherId, $title, $params, $languageType, $json, $jsonQuiz=null)
    {
        $newJsonQuiz = new JsonQuiz([
            'id' => ($jsonQuiz != null) ? $jsonQuiz['id'] : null,
            'parent_folder_id' => $params['parent_folder_id'],
            'teacher_id' => $teacherId,
            'title_no' => $params['titleNo'],
            'language_type' => $languageType,
            'title' => $title,
            'json' => $json,
            'max_score' => $params['total'],
            'open_date' => $params['open_date'],
            'expire_date' => $params['expire_date'],
            'time_limit' => $params['time_limit'],
            'create_date' => ($jsonQuiz != null) ? $jsonQuiz['create_date'] : null
        ]);

        $newJsonQuiz->irregularDateFix();

        return $newJsonQuiz;
    }

    /**
     * @param $jsonQuizId
     * @param $params
     * @return bool
     */
    private function AddOrUpdateJsonQuizOption($jsonQuizId, $params) : bool
    {
        $JsonQuizOption = new JsonQuizOption($jsonQuizId, $params);
        return $this->GetJsonQuizOptionModel()->AddOrUpdate($JsonQuizOption);
    }

    /**
     * @param $studentId
     * @param $param
     * @return array
     */
    public function RegisterResult($studentId, $param)
    {
        $result = [
            'error' => Error::ERROR_NONE,
            'result_id' => 0,
        ];

        $quizId = 0;

        if (empty($param['quiz_id']) || empty($param['answers']) || !is_array($param['answers'])) {
            $result['error'] = Error::ERROR_JSON_QUIZ_RESULT_INVALID_PARAMETER;
        }
        else {
            $quizId = $param['quiz_id']; 
        }

        if ($result['error'] != Error::ERROR_NONE) return $result;

        $jsonQuiz = $this->Get($quizId);

        // 先生が確認できるテストかチェックする
        // 1. 回答済みの場合は false
        // 2. 未回答で且つ期間内にテストを開始している場合 true
        $is_first_result = false;
        $userResults = $this->GetJsonQuizResultModel()->GetsUsersQuizResult($jsonQuiz['id'], $studentId);

        if (empty($userResults))
        {
            // 期間内にテストを開始している場合
            $isExpired = $param['is_expired'] ?? 0;

            if (empty($isExpired)) $is_first_result = true;
        }

        // 回答を参照しやすいように整える
        $userAnswers = [];
        foreach ($param['answers'] as $answer)
        {
            $questionId = $answer['question_id'];
            $userAnswers[$questionId] = [
                'answer' => $answer['user_answer'] ?? '',
                'answer_id' => $answer['answer_id'] ?? '',
            ];
        }

        // スコア換算
        $data = json_decode($jsonQuiz['json'], true);
        $checkResult = $this->CheckCorrectAnswer($data['questions'], $userAnswers);

        // データ登録
        $jsonQuizResult = new JsonQuizResult([
            'json_quiz_id' => $jsonQuiz['id'],
            'student_id' => $studentId,
            'answers_json' => json_encode($checkResult['answers'], JSON_UNESCAPED_UNICODE),
            'score' => $checkResult['score'],
            'is_first_result' => ($is_first_result) ? 1 : 0
        ]);

        PDOHelper::GetPDO()->beginTransaction();
        $resultId = 0;

        try {
            // calc_correct_answer_rate フラグを作る
            $updateJsonQuizResult = $this->GetJsonQuizModel()->Update([
                'id' => $jsonQuiz['id'],
                'calc_correct_answer_rate' => 1,
                'update_date' => date("Y-m-d H:i:s")
            ]);

            if (empty($updateJsonQuizResult)) {
                $result['error'] = Error::ERROR_JSON_QUIZ_RESULT_UPDATE_JSON_QUIZ;
                PDOHelper::GetPDO()->rollBack();
            }
            else {
                // 結果登録
                $addResult = $this->GetJsonQuizResultModel()->Add($jsonQuizResult);

                if (empty($addResult)) {
                    $result['error'] = Error::ERROR_JSON_QUIZ_RESULT_ADD_FAILED;
                    PDOHelper::GetPDO()->rollBack();
                }
                else {
                    $resultId = PDOHelper::GetLastInsertId(PDOHelper::GetPDO());
                    PDOHelper::GetPDO()->commit();
                    $result['result_id'] = $resultId;
                }
            }
        } catch (\Exception $e) {
            PDOHelper::GetPDO()->rollBack();
            error_log('Exception in RegisterResult: ' . $e->getMessage());
            $result['error'] = Error::ERROR_JSON_QUIZ_RESULT_ADD_FAILED;
        }

        return $result;
    }

    /**
     * @param $str
     * @return string
     */
    private function ReplaceUnneededTagsAndWhiteSpace($str)
    {
        $str = preg_replace('/<rt>([^\/]+)<\/rt>/u', '', $str);
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

    /**
     * @param $questions
     * @param $userAnswers
     * @return array
     */
    public function CheckCorrectAnswer($questions, $userAnswers)
    {
        $result = [
            'score' => 0,
            'answers' => []
        ];

        foreach ($questions as $question)
        {
            if ($question['question_type'] == 'page_break_item') continue;

            $questionId = $question['question_id'];

            $userAnswer = $userAnswers[ $questionId ] ?? ['answer' => '', 'answer_id' => ''];
            if (!is_array($userAnswer)) {
                $userAnswer = [
                    'answer' => $userAnswer,
                    'answer_id' => '',
                ];
            }

            $isCorrect = $this->IsCorrectAnswer($question['answers'], $userAnswer);

            if (!$isCorrect && !empty($question['other_answers'])) {
                $isCorrect = $this->IsCorrectOtherAnswer($question['other_answers'], $userAnswer['answer']);
            }

            $result['answers'][ $questionId ] = [
                'isCorrect' => ($isCorrect) ? 1 : 0,
                'answer' => $userAnswer['answer'],
                'answer_id' => $userAnswer['answer_id']
            ];

            if ($isCorrect) ++$result['score'];
        }

        return $result;
    }

    /**
     * @param $id
     * @param $params
     * @param $teacherId
     * @return array
     */
    public function Copy($id, $params, $teacherId): array
    {
        $jsonQuizRecord = $this->GetJsonQuizModel()->GetById($id);

        $jsonQuizRecord['teacher_id'] = $teacherId;
        $jsonQuizRecord['title'] = $params['title'];
        $jsonQuizRecord['open_date'] = (!empty($params['open_date'])) ? JsonQuiz::irregularOpenDateFix($params['open_date']) : PeriodHelper::PERIOD_OPEN_DATE;
        $jsonQuizRecord['expire_date'] = (!empty($params['expire_date'])) ? JsonQuiz::irregularExpireDateFix($params['expire_date']) : PeriodHelper::PERIOD_EXPIRE_DATE;
        $jsonQuizRecord['create_date'] = date("Y-m-d H:i:s");

        if (isset ($jsonQuizRecord['id'])) unset($jsonQuizRecord['id']);
        if (isset ($jsonQuizRecord['update_date'])) unset($jsonQuizRecord['update_date']);

        $result = $this->GetJsonQuizModel()->Add($jsonQuizRecord);

        if (!$result)
        {
            return [
                'error' => Error::ERROR_JSON_QUIZ_ADD_FAILED
            ];
        }

        $jsonQuizId = PDOHelper::GetLastInsertId(PDOHelper::GetPDO());

        $jsonQuizOptionRecord = $this->GetJsonQuizOptionModel()->GetByKeyValue('json_quiz_id', $id);

        $jsonQuizOptionRecord['json_quiz_id'] = $jsonQuizId;
        if (isset ($jsonQuizOptionRecord['id'])) unset($jsonQuizOptionRecord['id']);
        if (isset ($jsonQuizOptionRecord['create_date'])) unset($jsonQuizOptionRecord['create_date']);
        if (isset ($jsonQuizOptionRecord['update_date'])) unset($jsonQuizOptionRecord['update_date']);

        $jsonQuizOptionResult = $this->GetJsonQuizOptionModel()->Add($jsonQuizOptionRecord);

        if (!$jsonQuizOptionResult)
        {
            return [
                'error' => Error::ERROR_JSON_QUIZ_OPTION_ADD_FAILED
            ];
        }

        return [
            'error' => Error::ERROR_NONE,
        ];

    }

    /**
     * 正解かどうかチェックする
     * @param $answers
     * @param $userAnswer
     * @return bool
     */
    private function IsCorrectAnswer($answers, $userAnswer)
    {
        $userAnswerId = '';
        $userAnswerText = '';

        if (is_array($userAnswer)) {
            $userAnswerId = $userAnswer['answer_id'] ?? '';
            $userAnswerText = $userAnswer['answer'] ?? '';
        } else {
            $userAnswerText = $userAnswer;
        }

        if ($userAnswerId !== '') {
            foreach ($answers as $answer) {
                if (!empty($answer['answer_id']) && $answer['answer_id'] === $userAnswerId) {
                    return $answer['weight'] == 100;
                }
            }
        }

        $userAnswerText = $this->ReplaceUnneededTagsAndWhiteSpace($userAnswerText);
        $userAnswerText = $this->ReplaceSingleQuote($userAnswerText);

        foreach ($answers as $answer)
        {
            $answerText = $this->ReplaceUnneededTagsAndWhiteSpace($answer['answer_text']);

            if ($answerText != $userAnswerText) continue;

            return $answer['weight'] == 100;
        }

        return false;
    }

    /**
     * 別解で正解かどうかチェックする
     * @param $otherAnswers
     * @param $userAnswer
     * @return bool
     */
    private function IsCorrectOtherAnswer($otherAnswers, $userAnswer)
    {
        $userAnswer = $this->ReplaceUnneededTagsAndWhiteSpace($userAnswer);
        $userAnswer = $this->ReplaceSingleQuote($userAnswer);

        foreach ($otherAnswers as $answer)
        {
            $answerText = $this->ReplaceUnneededTagsAndWhiteSpace($answer);
            if ($answerText == $userAnswer) return true;
        }

        return false;
    }

    private ?JsonQuizModel $_JsonQuizModel = null;

    private function GetJsonQuizModel(): JsonQuizModel
    {
        if ($this->_JsonQuizModel != null) return $this->_JsonQuizModel;

        $this->_JsonQuizModel = new JsonQuizModel();

        return $this->_JsonQuizModel;
    }

    private ?JsonQuizOptionModel $_JsonQuizOptionModel = null;

    private function GetJsonQuizOptionModel(): JsonQuizOptionModel
    {
        if ($this->_JsonQuizOptionModel != null) return $this->_JsonQuizOptionModel;

        $this->_JsonQuizOptionModel = new JsonQuizOptionModel();

        return $this->_JsonQuizOptionModel;
    }

    private ?JsonQuizResultModel $_JsonQuizResultModel = null;

    private function GetJsonQuizResultModel(): JsonQuizResultModel
    {
        if ($this->_JsonQuizResultModel != null) return $this->_JsonQuizResultModel;

        $this->_JsonQuizResultModel = new JsonQuizResultModel();

        return $this->_JsonQuizResultModel;
    }

    private ?JsonQuizResultSummaryModel $_JsonQuizResultSummaryModel = null;

    private function GetJsonQuizResultSummaryModel(): JsonQuizResultSummaryModel
    {
        if ($this->_JsonQuizResultSummaryModel != null) return $this->_JsonQuizResultSummaryModel;

        $this->_JsonQuizResultSummaryModel = new JsonQuizResultSummaryModel();

        return $this->_JsonQuizResultSummaryModel;
    }
}
