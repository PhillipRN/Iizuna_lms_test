<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\CsvHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\StudentCsvProcessor;

// CSRFチェック
if (!CSRFHelper::CheckPostKey()) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

// ログインチェック
if (!TeacherLoginController::IsLogin()) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_NOT_LOGINED);
}

// 教師データ取得
$teacher = TeacherLoginController::GetTeacherData();

// リクエストバリデーション
if (!isset($_POST['lms_code_id']) || !isset($_FILES['csv_file'])) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_REGISTER_CSV_PARAMETER_ERROR);
}

$lmsCodeId = $_POST['lms_code_id'];

// CSVファイルアップロード
$uploadResult = CsvHelper::UploadCsvFile($_FILES['csv_file']['tmp_name'], $_FILES['csv_file']['name']);
if (isset($uploadResult['error'])) {
    DisplayJsonHelper::ShowErrorAndExit($uploadResult['error']);
}

$filePath = $uploadResult['filePath'];

try {
    // CSVファイルの読み込みと変換
    $csvData = StudentCsvProcessor::PrepareCSVFile($filePath);
    $handle = $csvData['handle'];
    $tempFile = $csvData['tempFile'];
    $headerMap = $csvData['headerMap'];

    // 必須ヘッダーの検証
    $headerValidation = StudentCsvProcessor::ValidateRequiredHeaders($headerMap);
    if (!$headerValidation['isValid']) {
        fclose($handle);
        DisplayJsonHelper::ShowAndExit([
            'error' => [
                'code' => Error::ERROR_STUDENT_REGISTER_CSV_INVALID_FORMAT,
                'message' => '必須項目（' . implode('、', $headerValidation['missingHeaders']) . '）がCSVファイルに含まれていません。テンプレートをダウンロードして正しい形式で作成してください。'
            ]
        ]);
    }

    // 生徒データの処理
    $processResult = StudentCsvProcessor::ProcessStudentData($handle, $headerMap, $lmsCodeId);
    $students = $processResult['students'];
    $duplicateCount = $processResult['duplicateCount'];
    $loginIdDuplicates = $processResult['loginIdDuplicates'];
    $invalidPasswords = $processResult['invalidPasswords'];

    fclose($handle);

    // 一時ファイルの保存（登録処理用）
    $_SESSION['student_csv_temp_file'] = $tempFile;
    $_SESSION['student_csv_lms_code'] = $lmsCodeId;

    // ログインID重複チェック
    $hasLoginIdDuplicates = !empty($loginIdDuplicates);

    // パスワードエラーチェック
    $hasPasswordErrors = !empty($invalidPasswords);

    // 登録不可のエラーがあるかチェック
    $hasErrors = $hasLoginIdDuplicates || $hasPasswordErrors;

    // 結果を返す
    DisplayJsonHelper::ShowAndExit([
        'result' => 'OK',
        'students' => $students,
        'duplicateCount' => $duplicateCount,
        'hasLoginIdDuplicates' => $hasLoginIdDuplicates,
        'loginIdDuplicates' => $loginIdDuplicates,
        'hasPasswordErrors' => $hasPasswordErrors,
        'invalidPasswords' => $invalidPasswords,
        'hasErrors' => $hasErrors,
        'lmsCodeId' => $lmsCodeId
    ]);

} catch (\Exception $e) {
    if (isset($handle)) {
        fclose($handle);
    }
    error_log('CSV Preview Error: ' . $e->getMessage());
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_UPLOAD_FILE_FAILED);
}