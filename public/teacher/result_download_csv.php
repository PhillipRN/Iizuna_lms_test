<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Schools\SchoolGroupLoader;
use IizunaLMS\Teachers\TeacherResultPageJsonQuizzes;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();
$currentLmsCodeId = $_GET['lcid'] ?? null;

// TODO LMSコードが先生の学校のコードかどうかチェックする

$schoolId = $teacher->school_id;
$schoolGroups = SchoolGroupLoader::GetSchoolAndGroups($schoolId);

$currentSchoolGroup = null;
foreach ($schoolGroups as $schoolGroup) {
    if ($currentLmsCodeId == $schoolGroup['lms_code_id']) {
        $currentSchoolGroup = $schoolGroup;
        break;
    }
}

$TeacherResultPageJsonQuizzes = new TeacherResultPageJsonQuizzes();

// Result 一覧取得
$resultData = $TeacherResultPageJsonQuizzes->GetResultData($currentLmsCodeId);
$file_name = "result_{$currentSchoolGroup['name']}.csv";

header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename={$file_name}");
header('Content-Transfer-Encoding: binary');

$fp = fopen('php://output', 'w');

// UTF-8からSJIS-winへ変換するフィルター
stream_filter_append($fp, 'convert.iconv.UTF-8/CP932//TRANSLIT', STREAM_FILTER_WRITE);

// 1行目出力
$line = ['', '', 'タイトル'];
foreach ($resultData['quizzes'] as $quiz) {
    $line[] = $quiz['title'];
}
fputcsv($fp, $line, ',', '"');

// 2行目出力
$line = ['', '', '期間開始日'];
foreach ($resultData['quizzes'] as $quiz) {
    $openDate = $quiz['open_date'];

    if ($openDate == '1000-01-01 00:00:00') {
        $openDate = '';
    } else {
        $openDate = date('n月j日', strtotime($openDate));
    }

    $line[] = $openDate;
}
fputcsv($fp, $line, ',', '"');

// 3行目出力
$line = ['', '', '問題数'];
foreach ($resultData['quizzes'] as $quiz) {
    $line[] = $quiz['max_score'];
}
fputcsv($fp, $line, ',', '"');

// 4行目出力
$header2 = ['生徒名', '学籍番号', 'ログインID'];
foreach ($resultData['quizzes'] as $quiz) {
    $header2[] = '';
}

fputcsv($fp, $header2, ',', '"');

// ボディー出力
foreach ($resultData['studentResults'] as $studentResult) {
    $record = [
        $studentResult['name'],
        $studentResult['student_number'],
        $studentResult['login_id']
    ];

    foreach ($studentResult['results'] as $result) {
        $record[] = ($result === null) ? '未' : $result;
    }

    fputcsv($fp, $record, ',', '"');
}
fclose($fp);
exit;