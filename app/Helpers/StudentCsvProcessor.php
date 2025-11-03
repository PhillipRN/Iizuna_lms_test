<?php
namespace IizunaLMS\Helpers;

use IizunaLMS\Models\StudentModel;
use IizunaLMS\Students\StudentDataChecker;

/**
 * 生徒CSVファイル処理用ヘルパークラス
 */
class StudentCsvProcessor
{
    /**
     * CSVファイルを読み込み、UTF-8形式に変換して処理しやすい形にする
     *
     * @param string $filePath CSVファイルのパス
     * @return array [handle, tempFile, headerMap, header] の配列
     */
    public static function PrepareCSVFile($filePath)
    {
        // ファイル全体を読み込む
        $fullContent = file_get_contents($filePath);

        // BOMのチェック（UTF-8 BOM: EF BB BF）
        $hasBom = (substr($fullContent, 0, 3) === "\xEF\xBB\xBF");
        if ($hasBom) {
            error_log('CSV file has UTF-8 BOM, removing it');
            $fullContent = substr($fullContent, 3); // BOMを除去
        }

        // 文字コード検出と変換
        $encoding = mb_detect_encoding($fullContent, ['UTF-8', 'SJIS-win', 'eucJP-win', 'ASCII'], true);
        error_log('Detected encoding: ' . ($encoding ?: 'unknown'));

        // UTF-8以外の場合は変換
        if ($encoding !== 'UTF-8' && $encoding !== false) {
            error_log('Converting from ' . $encoding . ' to UTF-8');
            $fullContent = mb_convert_encoding($fullContent, 'UTF-8', $encoding);
        } else if ($encoding === false) {
            // 文字コード判定に失敗した場合はUTF-8と仮定
            error_log('Encoding detection failed, assuming UTF-8');
        }

        // 変換したデータを一時ファイルに書き込み
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($tempFile, $fullContent);

        $handle = fopen($tempFile, 'r');
        if ($handle === false) {
            throw new \Exception('Failed to open CSV file');
        }

        // ヘッダー行の読み込み
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new \Exception('Failed to read CSV header');
        }

        // ヘッダー行のトリム処理
        $header = array_map('trim', $header);

        error_log('CSV Headers: ' . implode(', ', $header));

        // ヘッダーマッピング
        $headerMap = self::MapHeaders($header);

        return [
            'handle' => $handle,
            'tempFile' => $tempFile,
            'headerMap' => $headerMap,
            'header' => $header
        ];
    }

    /**
     * ヘッダー行から必須フィールドとオプションフィールドのマッピングを作成
     *
     * @param array $header ヘッダー行の配列
     * @return array ヘッダーマッピング配列
     */
    public static function MapHeaders($header)
    {
        $requiredHeaders = ['名前', '学籍番号', 'ログインID', 'パスワード'];
        $optionalHeaders = ['学校名', '学年', 'クラス'];
        $headerMap = [];

        // 必須ヘッダーのマッピング
        foreach ($requiredHeaders as $required) {
            $found = false;
            foreach ($header as $index => $field) {
                if (mb_strtolower(trim($field), 'UTF-8') === mb_strtolower(trim($required), 'UTF-8')) {
                    $headerMap[$required] = $index;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                error_log('Missing required header: ' . $required);
            }
        }

        // オプションヘッダーのマッピング
        foreach ($optionalHeaders as $optional) {
            foreach ($header as $index => $field) {
                if (mb_strtolower(trim($field), 'UTF-8') === mb_strtolower(trim($optional), 'UTF-8')) {
                    $headerMap[$optional] = $index;
                    break;
                }
            }
        }

        return $headerMap;
    }

    /**
     * CSVのデータ行から生徒データを抽出する
     *
     * @param resource $handle ファイルハンドル
     * @param array $headerMap ヘッダーマッピング
     * @param int $lmsCodeId LMSコードID
     * @return array [students, duplicateCount, loginIdDuplicates, invalidPasswords] の配列
     */
    public static function ProcessStudentData($handle, $headerMap, $lmsCodeId)
    {
        $students = [];
        $studentModel = new StudentModel();
        $duplicateCount = 0;
        $loginIdDuplicates = [];
        $invalidPasswords = [];
        $rowNumber = 1; // 既にヘッダー行を読み込んでいるため1から開始
        $studentDataChecker = new StudentDataChecker();

        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;
            error_log("Processing row $rowNumber");

            // 空行スキップ
            if (self::IsEmptyRow($data)) {
                error_log("Skipping empty row $rowNumber");
                continue;
            }

            // 各フィールドのトリム処理
            $data = array_map('trim', $data);

            // 必須フィールドが埋まっているかチェック
            if (!self::HasRequiredFields($data, $headerMap)) {
                error_log("Skipping row $rowNumber due to missing required fields");
                continue;
            }

            $name = $data[$headerMap['名前']];
            $studentNumber = isset($headerMap['学籍番号']) && isset($data[$headerMap['学籍番号']]) ? $data[$headerMap['学籍番号']] : '';
            $loginId = $data[$headerMap['ログインID']];
            $password = $data[$headerMap['パスワード']];

            // ヘッダー行のようなデータをスキップ（念のため）
            if ($name === '名前' || $loginId === 'ログインID') {
                error_log("Skipping header-like row $rowNumber");
                continue;
            }

            // パスワード検証
            $isPasswordValid = $studentDataChecker->CheckPassword($password);
            $passwordStatus = '';
            if (!$isPasswordValid) {
                $invalidPasswords[] = [
                    'name' => $name,
                    'password' => $password,
                    'row' => $rowNumber
                ];
                $passwordStatus = '登録不可（パスワード不適切）';
            }

            // 追加フィールドの取得（存在する場合）
            $schoolName = isset($headerMap['学校名']) && isset($data[$headerMap['学校名']]) ? $data[$headerMap['学校名']] : '';
            $schoolGrade = isset($headerMap['学年']) && isset($data[$headerMap['学年']]) ? $data[$headerMap['学年']] : '';
            $schoolClass = isset($headerMap['クラス']) && isset($data[$headerMap['クラス']]) ? $data[$headerMap['クラス']] : '';

            // 同姓同名チェック
            $duplicates = $studentModel->CheckDuplicateNames($name, $lmsCodeId);
            $hasDuplicates = !empty($duplicates);

            if ($hasDuplicates) {
                $duplicateCount += count($duplicates);
            }

            // ログインID重複チェック
            $existingStudent = $studentModel->GetStudentByLoginId($loginId);
            $hasLoginIdDuplicate = !empty($existingStudent);

            if ($hasLoginIdDuplicate) {
                $loginIdDuplicates[] = $loginId;
            }

            // ステータス判定
            $status = '新規登録';
            $hasError = false;

            if (!$isPasswordValid) {
                $status = '登録不可（パスワード不適切）';
                $hasError = true;
            } else if ($hasLoginIdDuplicate) {
                $status = '登録不可（ログインID重複）';
                $hasError = true;
            }

            $students[] = [
                'name' => $name,
                'student_number' => $studentNumber,
                'login_id' => $loginId,
                'password' => $password,
                'school_name' => $schoolName,
                'school_grade' => $schoolGrade,
                'school_class' => $schoolClass,
                'hasDuplicates' => $hasDuplicates,
                'hasLoginIdDuplicate' => $hasLoginIdDuplicate,
                'hasPasswordError' => !$isPasswordValid,
                'hasError' => $hasError,
                'status' => $status
            ];
        }

        return [
            'students' => $students,
            'duplicateCount' => $duplicateCount,
            'loginIdDuplicates' => array_unique($loginIdDuplicates),
            'invalidPasswords' => $invalidPasswords
        ];
    }

    /**
     * 行が空かどうかをチェック
     *
     * @param array $data 行データ
     * @return bool 空の場合はtrue
     */
    public static function IsEmptyRow($data)
    {
        if (empty($data)) {
            return true;
        }

        // 全ての要素が空かチェック
        foreach ($data as $value) {
            if (!empty(trim($value))) {
                return false;
            }
        }

        return true;
    }

    /**
     * 必須フィールドが全て存在するかチェック
     *
     * @param array $data 行データ
     * @param array $headerMap ヘッダーマッピング
     * @return bool 必須フィールドが全て存在する場合はtrue
     */
    public static function HasRequiredFields($data, $headerMap)
    {
        $requiredFields = ['名前', 'ログインID', 'パスワード'];

        foreach ($requiredFields as $field) {
            if (!isset($headerMap[$field]) ||
                !isset($data[$headerMap[$field]]) ||
                empty(trim($data[$headerMap[$field]]))) {
                return false;
            }
        }

        return true;
    }

    /**
     * 必須ヘッダーが全て存在するかチェック
     *
     * @param array $headerMap ヘッダーマッピング
     * @return array [isValid, missingHeaders] の配列。isValidがfalseの場合、missingHeadersに不足しているヘッダーの一覧が含まれる
     */
    public static function ValidateRequiredHeaders($headerMap)
    {
        $requiredHeaders = ['名前', '学籍番号', 'ログインID', 'パスワード'];
        $missingHeaders = [];

        foreach ($requiredHeaders as $required) {
            if (!isset($headerMap[$required])) {
                $missingHeaders[] = $required;
            }
        }

        return [
            'isValid' => empty($missingHeaders),
            'missingHeaders' => $missingHeaders
        ];
    }
}