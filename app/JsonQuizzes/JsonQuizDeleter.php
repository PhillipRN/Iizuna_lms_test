<?php

namespace IizunaLMS\JsonQuizzes;

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Models\JsonQuizDeliveryModel;
use IizunaLMS\Models\JsonQuizModel;
use IizunaLMS\Models\JsonQuizOptionModel;
use IizunaLMS\Models\JsonQuizResultModel;

class JsonQuizDeleter
{
    public function DeleteById($teacherId, $jsonQuizId)
    {
        $jsonQuiz = (new JsonQuizModel())->GetById($jsonQuizId);

        if (empty($jsonQuiz)) {
            return ['error' =>  Error::ERROR_JSON_QUIZ_DELETE_NOT_FOUND];
        }

        if ($jsonQuiz['teacher_id'] != $teacherId) {
            return ['error' =>  Error::ERROR_JSON_QUIZ_DELETE_NOT_PERMIT];
        }

        PDOHelper::GetPDO()->beginTransaction();

        $resultJsonQuiz = (new JsonQuizModel())->DeleteByKeyValue('id', $jsonQuizId);

        if (!$resultJsonQuiz) {
            PDOHelper::GetPDO()->rollBack();
            return ['error' =>  Error::ERROR_JSON_QUIZ_DELETE_FAILED];
        }

        $resultJsonQuizDelivery = (new JsonQuizDeliveryModel())->DeleteByKeyValue('json_quiz_id', $jsonQuizId);

        if (!$resultJsonQuizDelivery) {
            PDOHelper::GetPDO()->rollBack();
            return ['error' =>  Error::ERROR_JSON_QUIZ_DELETE_FAILED];
        }

        $jsonQuizOption = (new JsonQuizOptionModel())->GetByKeyValue('json_quiz_id', $jsonQuizId);

        if (!empty($jsonQuizOption)) {
            $resultJsonQuizOption = (new JsonQuizOptionModel())->DeleteByKeyValue('json_quiz_id', $jsonQuizId);

            if (!$resultJsonQuizOption) {
                PDOHelper::GetPDO()->rollBack();
                return ['error' =>  Error::ERROR_JSON_QUIZ_DELETE_FAILED];
            }
        }

        $resultJsonQuizResult = (new JsonQuizResultModel)->DeleteByKeyValue('json_quiz_id', $jsonQuizId);
        if (!$resultJsonQuizResult) {
            PDOHelper::GetPDO()->rollBack();
            return ['error' =>  Error::ERROR_JSON_QUIZ_DELETE_FAILED];
        }

        PDOHelper::GetPDO()->commit();

        return ['result' => 'OK'];
    }
}