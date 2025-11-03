<?php

namespace IizunaLMS\Onigiri;

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Models\OnigiriJsonQuizFolderModel;
use IizunaLMS\Models\OnigiriJsonQuizModel;

class OnigiriJsonQuizMover
{
    public function MoveQuiz($teacherId, $quizId, $parentFolderId)
    {
        if ($parentFolderId != 0) {
            $folder = (new OnigiriJsonQuizFolderModel())->GetById($parentFolderId);

            if (empty($folder)) {
                return ['error' => Error::ERROR_ONIGIRI_JSON_QUIZ_FOLDER_MOVE_QUIZ_FAILED];
            }
        }

        $JsonQuizModel = new OnigiriJsonQuizModel();
        $quiz = $JsonQuizModel->GetById($quizId);

        if ($quiz['teacher_id'] != $teacherId) {
            return ['error' =>  Error::ERROR_ONIGIRI_JSON_QUIZ_FOLDER_MOVE_NOT_PERMIT];
        }

        PDOHelper::GetPDO()->beginTransaction();

        $resultJsonQuiz = (new OnigiriJsonQuizModel())->MoveToFolder($quizId, $parentFolderId);

        if (!$resultJsonQuiz) {
            PDOHelper::GetPDO()->rollBack();
            return ['error' =>  Error::ERROR_ONIGIRI_JSON_QUIZ_FOLDER_MOVE_QUIZ_FAILED];
        }

        PDOHelper::GetPDO()->commit();

        return ['result' => 'OK'];
    }

    /**
     * @param $teacherId
     * @param $quizIds
     * @return bool
     */
    public function CheckQuizAuthor($teacherId, $quizIds): bool
    {
        $records = (new OnigiriJsonQuizModel())->GetsByIds($quizIds);

        foreach ($records as $record)
        {
            if ($record['teacher_id'] != $teacherId) return false;
        }

        return true;
    }

    /**
     * @param $teacherId
     * @param $quizIds
     * @param $parentFolderId
     * @return array|string[]
     */
    public function BulkMove($teacherId, $quizIds, $parentFolderId)
    {
        if ($parentFolderId != 0) {
            $folder = (new OnigiriJsonQuizFolderModel())->GetById($parentFolderId);

            if (empty($folder)) {
                return ['error' => Error::ERROR_ONIGIRI_JSON_QUIZ_FOLDER_BULK_MOVE_NOT_FOUND];
            }
        }

        if (!$this->CheckQuizAuthor($teacherId, $quizIds))
        {
            return ['error' => Error::ERROR_ONIGIRI_JSON_QUIZ_FOLDER_BULK_MOVE_NOT_PERMIT];
        }

        PDOHelper::GetPDO()->beginTransaction();

        $resultJsonQuiz = (new OnigiriJsonQuizModel())->BulkMoveToFolder($quizIds, $parentFolderId);

        if (!$resultJsonQuiz) {
            PDOHelper::GetPDO()->rollBack();
            return ['error' =>  Error::ERROR_ONIGIRI_JSON_QUIZ_FOLDER_BULK_MOVE_QUIZ_FAILED];
        }

        PDOHelper::GetPDO()->commit();

        return ['result' => 'OK'];
    }
}