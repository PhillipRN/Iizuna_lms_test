<?php

namespace IizunaLMS\Onigiri;

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Models\OnigiriJsonQuizDeliveryModel;
use IizunaLMS\Models\OnigiriJsonQuizModel;
use IizunaLMS\Models\OnigiriJsonQuizResultModel;

class OnigiriJsonQuizDeleter
{
    /**
     * @param $teacherId
     * @param $onigiriJsonQuizId
     * @return array|string[]
     */
    public function DeleteById($teacherId, $onigiriJsonQuizId)
    {
        $jsonQuiz = (new OnigiriJsonQuizModel())->GetById($onigiriJsonQuizId);

        if (empty($jsonQuiz)) {
            return ['error' =>  Error::ERROR_ONIGIRI_QUIZ_DELETE_NOT_FOUND];
        }

        if ($jsonQuiz['teacher_id'] != $teacherId) {
            return ['error' =>  Error::ERROR_ONIGIRI_QUIZ_DELETE_NOT_PERMIT];
        }

        PDOHelper::GetPDO()->beginTransaction();

        $resultJsonQuiz = (new OnigiriJsonQuizModel())->DeleteByKeyValue('id', $onigiriJsonQuizId);

        if (!$resultJsonQuiz) {
            PDOHelper::GetPDO()->rollBack();
            return ['error' =>  Error::ERROR_ONIGIRI_QUIZ_DELETE_FAILED];
        }

        $resultJsonQuizDelivery = (new OnigiriJsonQuizDeliveryModel())->DeleteByKeyValue('onigiri_json_quiz_id', $onigiriJsonQuizId);

        if (!$resultJsonQuizDelivery) {
            PDOHelper::GetPDO()->rollBack();
            return ['error' =>  Error::ERROR_ONIGIRI_QUIZ_DELETE_FAILED];
        }

        $resultJsonQuizResult = (new OnigiriJsonQuizResultModel)->DeleteByKeyValue('onigiri_json_quiz_id', $onigiriJsonQuizId);
        if (!$resultJsonQuizResult) {
            PDOHelper::GetPDO()->rollBack();
            return ['error' =>  Error::ERROR_JSON_QUIZ_DELETE_FAILED];
        }

        PDOHelper::GetPDO()->commit();

        return ['result' => 'OK'];
    }
}