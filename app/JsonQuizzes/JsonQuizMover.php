<?php

namespace IizunaLMS\JsonQuizzes;

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Models\JsonQuizFolderModel;
use IizunaLMS\Models\JsonQuizModel;

class JsonQuizMover
{
    public function MoveQuiz($teacherId, $quizId, $parentFolderId)
    {
        if ($parentFolderId != 0) {
            $folder = (new JsonQuizFolderModel())->GetById($parentFolderId);

            if (empty($folder)) {
                return ['error' => Error::ERROR_JSON_QUIZ_FOLDER_MOVE_QUIZ_FAILED];
            }
        }

        $JsonQuizModel = new JsonQuizModel();
        $quiz = $JsonQuizModel->GetById($quizId);

        if ($quiz['teacher_id'] != $teacherId) {
            return ['error' =>  Error::ERROR_JSON_QUIZ_FOLDER_MOVE_NOT_PERMIT];
        }

        PDOHelper::GetPDO()->beginTransaction();

        $resultJsonQuiz = (new JsonQuizModel())->MoveToFolder($quizId, $parentFolderId);

        if (!$resultJsonQuiz) {
            PDOHelper::GetPDO()->rollBack();
            return ['error' =>  Error::ERROR_JSON_QUIZ_FOLDER_MOVE_QUIZ_FAILED];
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
        $records = (new JsonQuizModel())->GetsByIds($quizIds);

        foreach ($records as $record)
        {
            if ($record['teacher_id'] != $teacherId) return false;
        }

        return true;
    }

    public function BulkMove($teacherId, $quizIds, $parentFolderId)
    {
        if ($parentFolderId != 0) {
            $folder = (new JsonQuizFolderModel())->GetById($parentFolderId);

            if (empty($folder)) {
                return ['error' => Error::ERROR_JSON_QUIZ_FOLDER_BULK_MOVE_NOT_FOUND];
            }
        }

        if (!$this->CheckQuizAuthor($teacherId, $quizIds))
        {
            return ['error' => Error::ERROR_JSON_QUIZ_FOLDER_BULK_MOVE_NOT_PERMIT];
        }

        PDOHelper::GetPDO()->beginTransaction();

        $resultJsonQuiz = (new JsonQuizModel())->BulkMoveToFolder($quizIds, $parentFolderId);

        if (!$resultJsonQuiz) {
            PDOHelper::GetPDO()->rollBack();
            return ['error' =>  Error::ERROR_JSON_QUIZ_FOLDER_BULK_MOVE_QUIZ_FAILED];
        }

        PDOHelper::GetPDO()->commit();

        return ['result' => 'OK'];
    }
}