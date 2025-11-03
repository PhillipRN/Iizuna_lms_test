<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\TeacherSmartyHelper;
use IizunaLMS\JsonQuizzes\JsonQuizLoader;
use IizunaLMS\Models\OnigiriLearningRangeModel;
use IizunaLMS\Schools\SchoolGroupLoader;
use IizunaLMS\Teachers\TeacherResultPageJsonQuizzes;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();
$currentPage = $_GET['page'] ?? 1;

$JsonQuizLoader = new JsonQuizLoader();
$records = $JsonQuizLoader->GetsByTeacherId($teacher->id, $currentPage);
$maxPageNumber = $JsonQuizLoader->GetMaxPageNumber($teacher->id);

$schoolId = $teacher->school_id;
$schoolGroups = SchoolGroupLoader::GetSchoolAndGroups($schoolId);

$currentLmsCodeId = $_GET['lcid'] ?? null;

if (empty($currentLmsCodeId)) {
    foreach ($schoolGroups as $schoolGroup) {
        if (!empty($schoolGroup['is_school'])) {
            $currentLmsCodeId = $schoolGroup['lms_code_id'];
            break;

        }
    }
}

$currentSchoolGroup = null;
foreach ($schoolGroups as $schoolGroup) {
    if ($currentLmsCodeId == $schoolGroup['lms_code_id']) {
        $currentSchoolGroup = $schoolGroup;
        break;
    }
}

$TeacherResultPageJsonQuizzes = new TeacherResultPageJsonQuizzes();

$maxResultPageNumber = $TeacherResultPageJsonQuizzes->GetMaxPageNumber($currentLmsCodeId);

// Result 一覧取得
$currentResultPage = $_GET['resultPage'] ?? 1;
$resultData = $TeacherResultPageJsonQuizzes->GetResultPageData($currentLmsCodeId, $currentResultPage);

$smarty = TeacherSmartyHelper::GetSmarty($teacher);
$smarty->assign('records', $records);
$smarty->assign('schoolGroups', $schoolGroups);
$smarty->assign('currentPage', $currentPage);
$smarty->assign('currentResultPage', $currentResultPage);
$smarty->assign('maxPageNumber', $maxPageNumber);
$smarty->assign('maxResultPageNumber', $maxResultPageNumber);
$smarty->assign('currentLmsCodeId', $currentLmsCodeId);
$smarty->assign('currentSchoolGroup', $currentSchoolGroup);
$smarty->assign('resultData', $resultData);
$smarty->assign('maxStageNum', OnigiriLearningRangeModel::MAX_STAGE_NUM);
$smarty->display('_result.html');