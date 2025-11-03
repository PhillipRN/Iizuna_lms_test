<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CSRFHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Helpers\StudentCsvProcessor;
use IizunaLMS\Models\StudentLmsCodeModel;
use IizunaLMS\Models\StudentModel;
use IizunaLMS\Students\Datas\StudentData;
use IizunaLMS\Students\Datas\StudentLmsCodeData;
use IizunaLMS\Students\ContactUserIdGenerator;

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

// リクエストバリデーション - 実行フラグがない場合はプレビュー処理を行う
if (!isset($_POST['execute']) || $_POST['execute'] != '1') {
    // student_csv_preview.php と同様のプレビュー処理
    require_once(__DIR__ . '/student_csv_preview.php');
    exit;
}

// セッションからCSVファイルパスを取得
if (!isset($_SESSION['student_csv_temp_file']) || !file_exists($_SESSION['student_csv_temp_file'])) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_REGISTER_CSV_FILE_NOT_FOUND);
}

$filePath = $_SESSION['student_csv_temp_file'];
$lmsCodeId = $_POST['lms_code_id'];

try {
    // CSVファイルの読み込みと変換
    $csvData = StudentCsvProcessor::PrepareCSVFile($filePath);
    $handle = $csvData['handle'];
    $headerMap = $csvData['headerMap'];

    // 必須ヘッダーの検証
    $headerValidation = StudentCsvProcessor::ValidateRequiredHeaders($headerMap);
    if (!$headerValidation['isValid']) {
        fclose($handle);
        DisplayJsonHelper::ShowAndExit([
            'error' => [
                'code' => Error::ERROR_STUDENT_REGISTER_CSV_INVALID_FORMAT,
                'message' => '必須項目（' . implode('、', $headerValidation['missingHeaders']) . '）がCSVファイルに含まれていません。'
            ]
        ]);
    }

    // 生徒データの処理（ProcessStudentDataを使用）
    $processResult = StudentCsvProcessor::ProcessStudentData($handle, $headerMap, $lmsCodeId);
    $students = $processResult['students'];
    $loginIdDuplicates = $processResult['loginIdDuplicates'];
    $invalidPasswords = $processResult['invalidPasswords'];

    // ファイルハンドルを閉じる
    fclose($handle);

    // モデル初期化
    $studentModel = new StudentModel();
    $studentLmsCodeModel = new StudentLmsCodeModel();
    $contactUserIdGenerator = new ContactUserIdGenerator();

    // トランザクション開始
    PDOHelper::GetPDO()->beginTransaction();

    $registeredCount = 0;
    $errorCount = 0;

    // 生徒データを登録
    foreach ($students as $student) {
        // ログインIDが重複している、またはパスワードエラーがある生徒はスキップ
        if ($student['hasLoginIdDuplicate'] || $student['hasPasswordError']) {
            $errorCount++;
            continue;
        }

        // 学生データの作成
        $studentData = new StudentData([
            'name' => $student['name'],
            'student_number' => $student['student_number'],
            'login_id' => $student['login_id'],
            'password' => StringHelper::GetHashedString($student['password']),
            'contact_user_id' => $contactUserIdGenerator->Generate(),
            'school_name' => $student['school_name'],
            'school_grade' => $student['school_grade'],
            'school_class' => $student['school_class']
        ]);

        // 学生の追加
        $addResult = $studentModel->Add($studentData);
        if (!$addResult) {
            $errorCount++;
            continue;
        }

        $studentId = PDOHelper::GetLastInsertId(PDOHelper::GetPDO());

        // LMSコードの紐付け
        $studentLmsCodeData = new StudentLmsCodeData([
            'student_id' => $studentId,
            'lms_code_id' => $lmsCodeId
        ]);

        $lmsCodeResult = $studentLmsCodeModel->Add($studentLmsCodeData);
        if (!$lmsCodeResult) {
            $errorCount++;
            continue;
        }

        $registeredCount++;
    }

    // コミット
    PDOHelper::GetPDO()->commit();

    // 一時ファイルの削除
    @unlink($filePath);
    unset($_SESSION['student_csv_temp_file']);
    unset($_SESSION['student_csv_lms_code']);

    // 結果を返す
    DisplayJsonHelper::ShowAndExit([
        'result' => 'OK',
        'registeredCount' => $registeredCount,
        'errorCount' => $errorCount
    ]);

} catch (Exception $e) {
    // エラー時はロールバック
    if (PDOHelper::GetPDO()->inTransaction()) {
        PDOHelper::GetPDO()->rollBack();
    }

    if (isset($handle)) {
        fclose($handle);
    }

    error_log('CSV Register Error: ' . $e->getMessage());
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_REGISTER_CSV_FAILED);
}