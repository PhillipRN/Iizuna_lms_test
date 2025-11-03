<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\StudentViewController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Schools\SchoolGroupLoader;

// ログインチェック
if (!TeacherLoginController::IsLogin()) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_NOT_LOGINED);
}

$teacher = TeacherLoginController::GetTeacherData();
$currentLmsCodeId = $_GET['lcid'] ?? null;

$schoolId = $teacher->school_id;
$schoolGroups = SchoolGroupLoader::GetSchoolAndGroups($schoolId);

$currentSchoolGroup = null;
foreach ($schoolGroups as $schoolGroup) {
    if ($currentLmsCodeId == $schoolGroup['lms_code_id']) {
        $currentSchoolGroup = $schoolGroup;
        break;
    }
}

// 先生の学校にないコードなのでエラーにする
if (empty($currentSchoolGroup)) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);

// リクエストパラメータの取得（サニタイズ済み）
$requestParams = RequestHelper::getParameters([
    'page' => ['filter' => FILTER_VALIDATE_INT, 'default' => 1],
    'sn' => ['filter' => FILTER_VALIDATE_INT, 'default' => 1],
    'lcid' => ['filter' => FILTER_VALIDATE_INT, 'default' => null]
]);

// ビューコントローラーの初期化
$viewController = new StudentViewController(
    TeacherLoginController::GetTeacherData(),
    $requestParams
);

// データの取得
$viewData = $viewController->getViewData(9999999);
//var_dump($viewData['records']);


$file_name = "result_{$currentSchoolGroup['name']}.csv";

header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename={$file_name}");
header('Content-Transfer-Encoding: binary');

$fp = fopen('php://output', 'w');

// 1行目出力
$line = ['名前','学籍番号','ログインID','学校名','学年','クラス','登録済みコード'];
fputcsv($fp, $line, ',', '"');

// ボディー出力
foreach ($viewData['records'] as $record) {
    $body = [
        $record['student_name'],
        $record['student_number'],
        $record['login_id'],
        $record['school_name'],
        $record['school_grade'],
        $record['school_class'],
        $record['lms_code_names']
    ];

    fputcsv($fp, $body, ',', '"');
}
fclose($fp);
exit;