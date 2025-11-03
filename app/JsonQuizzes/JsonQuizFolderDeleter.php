<?php

namespace IizunaLMS\JsonQuizzes;

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Models\JsonQuizFolderModel;
use IizunaLMS\Models\JsonQuizModel;

class JsonQuizFolderDeleter
{
    public function DeleteById($teacherId, $folderId)
    {
        $Model = new JsonQuizFolderModel();
        $folder = $Model->GetById($folderId);

        if (empty($folder)) {
            return ['error' =>  Error::ERROR_JSON_QUIZ_FOLDER_DELETE_NOT_FOUND];
        }

        if ($folder['teacher_id'] != $teacherId) {
            return ['error' =>  Error::ERROR_JSON_QUIZ_FOLDER_DELETE_NOT_PERMIT];
        }

        $childrenRecords = $Model->GetsByKeyValue('parent_folder_id', $folderId);

        if (!empty($childrenRecords)) {
            return ['error' =>  Error::ERROR_JSON_QUIZ_FOLDER_DELETE_REMAIN_CHILD];
        }

        PDOHelper::GetPDO()->beginTransaction();

        $resultJsonQuizFolder = $Model->DeleteByKeyValue('id', $folderId);

        if (!$resultJsonQuizFolder) {
            PDOHelper::GetPDO()->rollBack();
            return ['error' =>  Error::ERROR_JSON_QUIZ_FOLDER_DELETE_FAILED];
        }

        $resultJsonQuiz = (new JsonQuizModel())->MoveToRootFolder($folderId);

        if (!$resultJsonQuiz) {
            PDOHelper::GetPDO()->rollBack();
            return ['error' =>  Error::ERROR_JSON_QUIZ_FOLDER_MOVE_QUIZ_FAILED];
        }

        PDOHelper::GetPDO()->commit();

        return ['result' => 'OK'];
    }
}