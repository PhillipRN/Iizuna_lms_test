<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\JsonQuizController;
use IizunaLMS\Controllers\JsonQuizStatisticsController;
use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Helpers\DisplayErrorHelper;
use IizunaLMS\JsonQuizzes\JsonQuizStudentLoader;
use IizunaLMS\Models\JsonQuizDeliveryModel;
use IizunaLMS\Models\StudentLmsCodeModel;

if (!TeacherLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$teacher = TeacherLoginController::GetTeacherData();

$id = $_GET['id'] ?? 0;

$JsonQuizController = new JsonQuizController();
$jsonQuiz = $JsonQuizController->GetQuizById($id);

// 作成者が自分でない場合はエラー
if ($jsonQuiz['teacher_id'] != $teacher->id) DisplayErrorHelper::RedirectTeacherErrorPage(ERROR_JSON_QUIZ_PERMISSION);

// LMSコードに所属している生徒一覧を取得する
$students = (new JsonQuizStudentLoader())->GetStudentsById($jsonQuiz['id']);

$quizResults = $JsonQuizController->GetsResult($jsonQuiz['id'], 'DESC');

$JsonQuizStatisticsController = new JsonQuizStatisticsController();
$result = $JsonQuizStatisticsController->GetStatisticsStudents($jsonQuiz, $students, $quizResults);

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