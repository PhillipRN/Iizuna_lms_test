<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\CsvHelper;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\SchoolModel;
use IizunaLMS\Schools\SchoolRegister;

if (!AdminLoginController::IsLogin()) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_NOT_LOGIN);
}

$registeredSchools = [];
$errorSchools = [];

if (is_uploaded_file($_FILES["csvfile"]["tmp_name"])) {
    $uploadCsvResult = CsvHelper::UploadCsvFile($_FILES["csvfile"]["tmp_name"], $_FILES["csvfile"]["name"]);

    if (isset($uploadCsvResult['error'])) {
        return [
            "csvErrors" => [$uploadCsvResult['error']]
        ];
    }

    $filePath = $uploadCsvResult['filePath'];

    $fp = new \SplFileObject($filePath);
    $fp->setFlags(\SplFileObject::READ_CSV);

    $schools = [];

    foreach ($fp as $line) {
        // keyは取得しない
        if ($fp->key() == 0) {
            continue;
        } // 最終行判定したらループ終了
        else if (CsvHelper::IsLastLineForCsvFile($line)) {
            break;
        } // 各データ取得
        else {
            $tmpData = array();

            for ($i = 0; $i < count($line); ++$i) {
                switch ($i) {
                    case 2:
                        $prefNo = StringHelper::ConvertEncodingToUTF8($line[$i]);
                        preg_match('/\((.+)\)/', $prefNo, $matches);

                        $tmpData['school_pref'] = $matches[1] ?? '';
                        break;

                    case 5:
                        $tmpData['school_name'] = StringHelper::ConvertEncodingToUTF8($line[$i]);
                        break;

                    case 6:
                        $tmpData['school_address'] = StringHelper::ConvertEncodingToUTF8($line[$i]);
                        break;

                    case 7:
                        $tmpData['school_zip'] = StringHelper::ConvertEncodingToUTF8($line[$i]);
                        break;
                }
            }

            $schools[] = $tmpData;
        }
    }

    unlink($filePath);

    $result = false;
    PDOHelper::GetPDO()->beginTransaction();

    foreach ($schools as $school)
    {
        // すでに登録済みのものはスキップ
        if (!empty((new SchoolModel())->GetsByKeyValues(
                ['name', 'pref'],
                [$school['school_name'], $school['school_pref']]
            )))
        {
            $registeredSchools[] = "{$school['school_name']} ({$school['school_pref']})";
            continue;
        }

        $schoolResult = (new SchoolRegister())->Add($school);

        if ($schoolResult) $result = true;
        else $errorSchools[] = $school['school_name'];
    }

    if ($result) {
        PDOHelper::GetPDO()->commit();
    }
}

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('registeredSchools', $registeredSchools);
$smarty->assign('errorSchools', $errorSchools);
$smarty->display('_school_register_csv.html');