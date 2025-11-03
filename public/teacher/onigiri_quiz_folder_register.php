<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\OnigiriJsonQuizFolderModel;
use IizunaLMS\Onigiri\Data\OnigiriJsonQuizFolder;
use IizunaLMS\Onigiri\OnigiriJsonQuizFolderController;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

if (!CSRFHelper::CheckPostKey())
{
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

$teacher = TeacherLoginController::GetTeacherData();

// 登録・更新
if (isset($_POST["name"])) {
    $params = RequestHelper::GetPostParams();
    $params['teacher_id'] = $teacher->id;

    $result = false;
    PDOHelper::GetPDO()->beginTransaction();

    $jsonQuizFolder = new OnigiriJsonQuizFolder($params);

    // 新規登録
    if (empty($params['id'])) {
        $result = (new OnigiriJsonQuizFolderModel())->Add($jsonQuizFolder);
    }
    // 更新
    else {
        if ($params['id'] == $params['parent_folder_id']) {
            PDOHelper::GetPDO()->rollBack();
            DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_JSON_QUIZ_FOLDER_CANNOT_PARENT_OWN);
        }

        // 系列が途切れてしまうため子孫のフォルダは親にできない
        $OnigiriJsonQuizFolderController = new OnigiriJsonQuizFolderController($teacher->school_id);
        if ($OnigiriJsonQuizFolderController->CheckDescendants($params['id'], $params['parent_folder_id'])) {
            PDOHelper::GetPDO()->rollBack();
            DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_JSON_QUIZ_FOLDER_CANNOT_PARENT_DESCENDANTS);
        }

        $jsonQuizFolder->id = $params['id'];
        $result = (new OnigiriJsonQuizFolderModel())->Update($jsonQuizFolder);
    }

    if ($result) {
        PDOHelper::GetPDO()->commit();

        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_JSON_QUIZ_FOLDER_REGISTER_FAILED);
    }
}
else {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_ONIGIRI_JSON_QUIZ_FOLDER_PARAMETER_ERROR);
}