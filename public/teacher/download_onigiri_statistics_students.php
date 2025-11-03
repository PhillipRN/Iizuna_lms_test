<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\Models\OnigiriJsonQuizModel;
use IizunaLMS\Models\OnigiriJsonQuizResultModel;
use IizunaLMS\Onigiri\OnigiriJsonQuizStatisticsLoader;
use IizunaLMS\Onigiri\OnigiriJsonQuizStudentLoader;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();

$id = $_GET['id'] ?? 0;

$jsonQuiz = (new OnigiriJsonQuizModel())->GetById($id);

// 作成者が自分でない場合はエラー
if ($jsonQuiz['teacher_id'] != $teacher->id) DisplayErrorHelper::RedirectTeacherErrorPage(Error::ERROR_ONIGIRI_QUIZ_ACCESS_DENIED);

// LMSコードに所属している生徒一覧を取得する
$students = (new OnigiriJsonQuizStudentLoader())->GetStudentsByOnigiriJsonQuizId($jsonQuiz['id']);

$quizResults = (new OnigiriJsonQuizResultModel())->GetsByKeyValues(
    ['onigiri_json_quiz_id', 'is_first_result'],
    [$jsonQuiz['id'], 1],
    [],
    ['id' => 'DESC']
);


$result = (new OnigiriJsonQuizStatisticsLoader())->GetStatisticsStudents($jsonQuiz, $students, $quizResults);

$file_name = "{$jsonQuiz['title']} Quiz Student Analysis Report.csv";

header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename={$file_name}");
header('Content-Transfer-Encoding: binary');

$fp = fopen('php://output', 'w');

// UTF-8からSJIS-winへ変換するフィルター
stream_filter_append($fp, 'convert.iconv.UTF-8/CP932//TRANSLIT', STREAM_FILTER_WRITE);

// ヘッダー出力
fputcsv($fp, $result['headers'], ',', '"');

// ボディー出力
foreach ($result['records'] as $record) {
    fputcsv($fp, $record, ',', '"');
}
fclose($fp);
exit;