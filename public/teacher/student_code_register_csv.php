<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\TeacherLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CsvHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\LmsCodeAmountModel;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Models\StudentCodeModel;
use IizunaLMS\Schools\LmsCode;
use IizunaLMS\Schools\LmsCodeAmount;
use IizunaLMS\Schools\LmsCodeGenerator;
use IizunaLMS\Schools\StudentCode;

if (!TeacherLoginController::IsLogin()) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}

$teacher = TeacherLoginController::GetTeacherData();

if (!$teacher->is_juku) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_COMMON_ILLEGAL_TRANSITION);
}


if (is_uploaded_file($_FILES["csvfile"]["tmp_name"])) {
    $uploadCsvResult = CsvHelper::UploadCsvFile($_FILES["csvfile"]["tmp_name"], $_FILES["csvfile"]["name"]);

    if (isset($uploadCsvResult['error'])) {
        DisplayJsonHelper::ShowErrorAndExit($uploadCsvResult['error']);
    }

    $filePath = $uploadCsvResult['filePath'];

    $fp = new \SplFileObject($filePath);
    $fp->setFlags(\SplFileObject::READ_CSV);

    $students = [];

    foreach ($fp as $line) {
        // 最終行判定したらループ終了
        if (CsvHelper::IsLastLineForCsvFile($line)) break;

        // 各データ取得
        $students[] = StringHelper::ConvertEncodingToUTF8($line[0]);
    }

    unlink($filePath);

    PDOHelper::GetPDO()->beginTransaction();

    $errorMessage = '';

    if (empty($students))
    {
        $errorMessage = '指定されたファイルにデータが含まれていません。';
    }
    else
    {
        foreach ($students as $studentName)
        {
            // LMSコード生成
            $lmsCode = (new LmsCodeGenerator())->Generate();

            $resultLmsCode = (new LmsCodeModel())->Add(new LmsCode([
                'lms_code' => $lmsCode
            ]));

            if (empty($resultLmsCode)) {
                $errorMessage = "{$studentName} の追加時にエラーが出たためキャンセルしました。";
                break;
            }

            $lmsCodeId = PDOHelper::GetLastInsertId(PDOHelper::GetPDO());

            $params = [
                'name' => $studentName,
                'lms_code_id' => $lmsCodeId,
                'teacher_id' => $teacher->id,
                'school_id' => $teacher->school_id
            ];

            $result = (new StudentCodeModel())->Add(new StudentCode($params));

            $resultLmsCodeAmount = (new LmsCodeAmountModel())->Add(new LmsCodeAmount($lmsCodeId));

            if (empty($result) || empty($resultLmsCodeAmount)) {
                $errorMessage = "{$studentName} の追加時にエラーが出たためキャンセルしました。";
                break;
            }
        }
    }

    if (empty($errorMessage))
    {
        PDOHelper::GetPDO()->commit();
        DisplayJsonHelper::ShowAndExit([
            'result' => 'OK'
        ]);
    }
    else
    {
        PDOHelper::GetPDO()->rollBack();
        DisplayJsonHelper::ShowErrorAndExit($errorMessage);
    }
}
else {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_STUDENT_CODE_PARAMETER_ERROR);
}
