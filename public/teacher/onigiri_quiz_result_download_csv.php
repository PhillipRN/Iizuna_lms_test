<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\LmsTickets\LmsTicketLoader;
use IizunaLMS\Onigiri\OnigiriJsonQuizResultLoader;
use IizunaLMS\Schools\SchoolGroupLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();
$currentLmsCodeId = $_GET['lcid'] ?? null;

// TODO LMSコードが先生の学校のコードかどうかチェックする

$schoolId = $teacher->school_id;
$schoolGroups = SchoolGroupLoader::GetSchoolAndGroups($schoolId);

// 教師のおにぎりチケット情報を取得
$ticketData = (new LmsTicketLoader())->GetTeachersOnigiriTicket($teacher->id);
foreach ($ticketData as $ticket)
{
    $schoolGroups[] = [
        'lms_code_id' => $ticket['lms_code_id'],
        'name' => $ticket['name']
    ];
}

// TODO まとめる
$currentGroupName = null;
foreach ($schoolGroups as $schoolGroup) {
    if ($currentLmsCodeId == $schoolGroup['lms_code_id']) {
        $currentGroupName = $schoolGroup['name'];
        break;
    }
}

$OnigiriJsonQuizResultLoader = new OnigiriJsonQuizResultLoader();

// Result 一覧取得
$resultData = $OnigiriJsonQuizResultLoader->GetResultData($currentLmsCodeId);
$file_name = "onigiri_quiz_result_{$currentGroupName}.csv";

header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename={$file_name}");
header('Content-Transfer-Encoding: binary');

$fp = fopen('php://output', 'w');

// UTF-8からSJIS-winへ変換するフィルター
stream_filter_append($fp, 'convert.iconv.UTF-8/CP932//TRANSLIT', STREAM_FILTER_WRITE);

// 1行目出力
$line = ['', 'タイトル'];
foreach ($resultData['quizzes'] as $quiz) {
    $line[] = $quiz['title'];
}
fputcsv($fp, $line, ',', '"');

// 2行目出力
$line = ['', '期間開始日'];
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
$line = ['', '問題数'];
foreach ($resultData['quizzes'] as $quiz) {
    $line[] = $quiz['total'];
}
fputcsv($fp, $line, ',', '"');

// 4行目出力
$header2 = ['生徒名', '学籍番号'];
foreach ($resultData['quizzes'] as $quiz) {
    $header2[] = '';
}

fputcsv($fp, $header2, ',', '"');

// ボディー出力
foreach ($resultData['studentResults'] as $studentResult) {
    $record = [
        $studentResult['name'],
        $studentResult['student_number']
    ];

    foreach ($studentResult['results'] as $result) {
        $record[] = ($result === null) ? '未' : $result;
    }

    fputcsv($fp, $record, ',', '"');
}
fclose($fp);
exit;