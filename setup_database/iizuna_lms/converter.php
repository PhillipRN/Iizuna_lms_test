<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Helpers\FileHelper;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

(new ConverterIizunaLMS())->Convert();

class ConverterIizunaLMS
{
    private $exportSettings = [
        [ 'sheetName' => '00_データ', 'exportFileName' => 'TC05' ],
        [ 'sheetName' => '02_問題形式', 'exportFileName' => 'TC02' ],
        [ 'sheetName' => '03_章・節', 'exportFileName' => 'TC03' ],
        [ 'sheetName' => '03_章･節', 'exportFileName' => 'TC03' ],
        [ 'sheetName' => '04_大問', 'exportFileName' => 'TC04' ],
        [ 'sheetName' => '06_レベル', 'exportFileName' => 'TC06' ],
        [ 'sheetName' => '07_頻度', 'exportFileName' => 'TC07' ],
        [ 'sheetName' => '08_見出し語', 'exportFileName' => 'TC08' ],
        [ 'sheetName' => '00_データ', 'exportFileName' => 'other_answer' ],
        [ 'sheetName' => '00_データ', 'exportFileName' => 'answer_index' ]
    ];

    private $exportDirectoryPath;
    private $currentExportDirectoryPath;
    private $currentFilePath;

    public function Convert()
    {
        // 日本語ファイルの名前を取得するためロケールを設定する
        setlocale(LC_ALL, 'ja_JP.UTF-8');

        // メモリ制限を緩和
        ini_set('memory_limit', '512M');

        // タイムアウトを無効化
        set_time_limit(0);

        $this->DebugLog("コンバート開始します。");

        $targetFolder = getenv('IMPORT_FOLDER');
        if (!empty($targetFolder)) {
            $specific = __DIR__ . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . $targetFolder;
            if (!is_dir($specific)) {
                $this->DebugLog("IMPORT_FOLDER={$targetFolder} が見つかりません。処理を終了します。");
                return;
            }
            $directories = [$specific];
        } else {
            $directories = FileHelper::GetDirectories(__DIR__ . DIRECTORY_SEPARATOR . 'import');
        }

        foreach ($directories as $directory)
        {
            $directoryName = pathinfo($directory)['basename'];
            $this->exportDirectoryPath = __DIR__ . DIRECTORY_SEPARATOR. 'export' . DIRECTORY_SEPARATOR . $directoryName;

            // 既存の出力先フォルダを削除
            FileHelper::DeleteDirectory($this->exportDirectoryPath);

            // エクスポートフォルダ内に出力先フォルダを新たに作る
            FileHelper::CreateDirectory($this->exportDirectoryPath);

            $filePaths = FileHelper::GetFiles($directory, 'xlsx');
            foreach ($filePaths as $filePath)
            {
                // basename だとフォルダ名に日本語が入っているとうまくファイル名で切れないため、自前でやる
                $fileName = str_replace($directory . DIRECTORY_SEPARATOR, '', $filePath);

                // 書籍ID取得
                preg_match('/^\w*TC([0-9]+)/', $fileName, $matches);

                if (empty($matches))
                {
                    $this->DebugLog("{$fileName} の BookID を取得できませんでした。");
                    continue;
                }

                $bookId = "TC{$matches[1]}";
                $this->DebugLog("{$bookId} の処理を開始します。");

                $this->currentExportDirectoryPath = $this->exportDirectoryPath . DIRECTORY_SEPARATOR . $bookId;
                FileHelper::CreateDirectory($this->currentExportDirectoryPath);

                $this->currentFilePath = $filePath;
                $this->ExportCsv();

                $this->DebugLog("{$bookId} の処理を完了しました。");
            }
        }
        $this->DebugLog("コンバート終了しました。");
    }

    private function DebugLog($message)
    {
        echo(date('Y-m-d H:i:s') . ": {$message}\n");
    }

    /**
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    private function ExportCsv()
    {
        $reader = new XlsxReader();
        $spreadsheet = $reader->load($this->currentFilePath);

        for ($i=0; $i<count($this->exportSettings); ++$i) {
            $setting = $this->exportSettings[$i];

            $this->DebugLog("{$setting['exportFileName']} PROGRESS");

            switch ($setting['exportFileName']) {
                case 'TC02':
                    $this->ExportTC02($spreadsheet, $setting);
                    break;

                case 'TC03':
                    $this->ExportTC03($spreadsheet, $setting);
                    break;

                case 'TC04':
                    $this->ExportTC04($spreadsheet, $setting);
                    break;

                case 'TC05':
                    $this->ExportTC05($spreadsheet, $setting);
                    break;

                case 'TC06':
                    $this->ExportTC06_08($spreadsheet, $setting, ['LEVELNO','NAME']);
                    break;

                case 'TC07':
                    $this->ExportTC06_08($spreadsheet, $setting, ['FREQUENCYNO','NAME']);
                    break;

                case 'TC08':
                    $this->ExportTC06_08($spreadsheet, $setting, ['MIDASINO','NAME']);
                    break;

                case 'other_answer':
                    $this->ExportOtherAnswer($spreadsheet, $setting);
                    break;

                case 'answer_index':
                    $this->ExportAnswerIndex($spreadsheet, $setting);
                    break;
            }
//            $this->DebugLog('[メモリ使用量]：'. memory_get_usage() / (1024 * 1024) . 'MB');
//            $this->DebugLog('[メモリ最大使用量]：'. memory_get_peak_usage() / (1024 * 1024) . 'MB');
        }

        unset($spreadsheet);
        Settings::getCache()->clear();
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @param array $setting
     * @return void
     */
    private function ExportTC02(Spreadsheet $spreadsheet, array $setting)
    {
        $sheet = $spreadsheet->getSheetByName($setting['sheetName']);

        if (empty($sheet)) {
            $this->DebugLog('SKIP');
            return;
        }

        // データ開始行と終了行を取得
        $startRow = $this->GetStartRow($sheet);
        $endRow = $this->GetEndRow($sheet);

        // データ取得
        $values = $sheet->rangeToArray("A{$startRow}:C{$endRow}");

        $header = [
            ['SYUBETUNO','NAME','RATE']
        ];
        $result = array_merge($header, $values);

        // ファイル出力
        $fp = fopen("{$this->currentExportDirectoryPath}/{$setting['exportFileName']}.csv", 'w');
        foreach ($result as $row) fputcsv($fp, $row);
        fclose($fp);

        $this->DebugLog('DONE');
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @param array $setting
     * @return void
     */
    private function ExportTC03(Spreadsheet $spreadsheet, array $setting)
    {
        $sheet = $spreadsheet->getSheetByName($setting['sheetName']);

        if (empty($sheet)) {
            $this->DebugLog('SKIP');
            return;
        }

        // データ開始行と終了行を取得
        $startRow = $this->GetStartRow($sheet);
        $endRow = $this->GetEndRow($sheet);

        // データ取得
        $values = $sheet->rangeToArray("A{$startRow}:D{$endRow}");

        // ファイル出力
        $fp = fopen("{$this->currentExportDirectoryPath}/{$setting['exportFileName']}.csv", 'w');

        fputcsv($fp, ['CHAPNO','SECNO','CHAPNAME','SECNAME']);

        foreach ($values as $row)
        {
            fputcsv($fp, [
                $row[0],
                $row[2],
                $row[1],
                $row[3]
            ]);
        }
        fclose($fp);

        $this->DebugLog('DONE');
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @param array $setting
     * @return void
     */
    private function ExportTC04(Spreadsheet $spreadsheet, array $setting)
    {
        $sheet = $spreadsheet->getSheetByName($setting['sheetName']);

        if (empty($sheet)) {
            $this->DebugLog('SKIP');
            return;
        }

        // データ開始行と終了行を取得
        $startRow = $this->GetStartRow($sheet);
        $endRow = $this->GetEndRow($sheet);

        // データ取得
        $values = $sheet->rangeToArray("A{$startRow}:C{$endRow}");

        // ファイル出力
        $fp = fopen("{$this->currentExportDirectoryPath}/{$setting['exportFileName']}.csv", 'w');

        fputcsv($fp, ['DAIMONNO','SORTNO','BUN']);

        foreach ($values as $row)
        {
            fputcsv($fp, [
                $row[0],
                $row[2],
                $row[1]
            ]);
        }
        fclose($fp);

        $this->DebugLog('DONE');
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @param array $setting
     * @return void
     */
    private function ExportTC05(Spreadsheet $spreadsheet, array $setting)
    {
        $sheet = $spreadsheet->getSheetByName($setting['sheetName']);

        if (empty($sheet)) {
            $this->DebugLog('SKIP');
            return;
        }

        // データ開始行と終了行を取得
        $startRow = $this->GetStartRow($sheet);
        $endRow = $this->GetEndRow($sheet);

        // データ取得
        $this->DebugLog('データ取得 開始');

        $shomonNos = $sheet->rangeToArray("A{$startRow}:A{$endRow}", null, true, false);
        echo '1/23 shomonNos > ';
        $secnos = $sheet->rangeToArray("F{$startRow}:F{$endRow}", null, true, false);
        echo '2/23 secnos > ';
        $syubetunos = $sheet->rangeToArray("H{$startRow}:H{$endRow}", null, true, false);
        echo '3/23 syubetunos > ';
        $revnos = $sheet->rangeToArray("J{$startRow}:J{$endRow}", null, true, false);
        echo '4/23 revnos > ';
        $midasinos = $sheet->rangeToArray("K{$startRow}:K{$endRow}", null, true, false);
        echo '5/23 midasinos > ';
        $midasinames = $sheet->rangeToArray("L{$startRow}:L{$endRow}");
        echo '6/23 midasinames > ';
        $daimonnos = $sheet->rangeToArray("M{$startRow}:M{$endRow}", null, true, false);
        echo '7/23 daimonnos > ';
        $levelnos = $sheet->rangeToArray("O{$startRow}:O{$endRow}", null, true, false);
        echo '8/23 levelnos > ';
        $freqencynos = $sheet->rangeToArray("Q{$startRow}:Q{$endRow}", null, true, false);
        echo '9/23 freqencynos > ';
        $buns = $sheet->rangeToArray("S{$startRow}:S{$endRow}");
        echo '10/23 buns > ';
        $choices = $sheet->rangeToArray("T{$startRow}:T{$endRow}");
        echo '11/23 choices > ';
        $ansbuns = $sheet->rangeToArray("V{$startRow}:V{$endRow}");
        echo '12/23 ansbuns > ';
        $anslengths = $sheet->rangeToArray("W{$startRow}:W{$endRow}");
        echo '13/23 anslengths > ';
        $ansnums = $sheet->rangeToArray("X{$startRow}:X{$endRow}");
        echo '14/23 ansnums > ';
        $pages = $sheet->rangeToArray("Y{$startRow}:Y{$endRow}");
        echo '15/23 pages > ';
        $revpnos = $sheet->rangeToArray("Z{$startRow}:Z{$endRow}", null, true, false);
        echo '16/23 revpnos > ';
        $choicesnums = $sheet->rangeToArray("AA{$startRow}:AA{$endRow}", null, true, false);
        echo '17/23 choicesnums > ';
        $answerfroms = $sheet->rangeToArray("AB{$startRow}:AB{$endRow}");
        echo '18/23 answerfroms > ';
        $filenames = $sheet->rangeToArray("AC{$startRow}:AC{$endRow}");
        echo '19/23 filenames > ';
        $ansbunfulls = $sheet->rangeToArray("AD{$startRow}:AD{$endRow}");
        echo '20/23 ansbunfulls > ';
        $comments = $sheet->rangeToArray("AE{$startRow}:AE{$endRow}");
        echo '21/23 comments > ';
        $searchlabels = $sheet->rangeToArray("AF{$startRow}:AF{$endRow}");
        echo "22/23 searchlabels\n";
        $answerIndexes = $sheet->rangeToArray("AG{$startRow}:AG{$endRow}");
        echo "23/23 answerIndexes\n";
        $this->DebugLog('データ取得 終了');

        $values = [];

        for ($i=0; $i<count($shomonNos); ++$i)
        {
            $values[] = [
                $shomonNos[$i][0],
                $secnos[$i][0],
                $syubetunos[$i][0],
                $revnos[$i][0],
                $midasinos[$i][0],
                $midasinames[$i][0],
                $daimonnos[$i][0],
                $levelnos[$i][0],
                $freqencynos[$i][0],
                $buns[$i][0],
                $choices[$i][0],
                $ansbuns[$i][0],
                $anslengths[$i][0],
                $ansnums[$i][0],
                $pages[$i][0],
                $revpnos[$i][0],
                $choicesnums[$i][0],
                $answerfroms[$i][0],
                $filenames[$i][0],
                $ansbunfulls[$i][0],
                $comments[$i][0],
                $searchlabels[$i][0],
                $answerIndexes[$i][0],
            ];
        }

        // ファイル出力
        $fp = fopen("{$this->currentExportDirectoryPath}/{$setting['exportFileName']}.csv", 'w');

        fputcsv($fp, ['SYOMONNO','DAIMONNO','SECNO','SYUBETUNO','SEQNO','MIDASINO','MIDASINAME','REVNO','REVPNO','LEVELNO','FREQENCYNO','BUN','PAGE','ANSLENGTH','ANSNUM','ANSBUN','CHOICES','CHOICESNUM','ANSWERFROM','FILENAME','ANSBUNFULL','COMMENT','SEARCHLABEL']);

        $seqno = 1;
        foreach ($values as $row)
        {
            $shomonNo   = $row[0];
            $secno      = $row[1];
            $syubetuno  = $row[2];
            $revno      = $row[3];
            $midasino   = $row[4];
            $midasiname = $row[5];
            $daimonno   = $row[6];
            $levelno    = $row[7];
            $freqencyno = $row[8];
            $bun        = $row[9];
            $choices    = $row[10];
            $ansbun     = $row[11];
            $anslength  = $row[12];
            $ansnum     = $row[13];
            $page       = $row[14];
            $revpno     = $row[15];
            $choicesnum = $row[16];
            $answerfrom = $row[17];
            $filename   = $row[18];
            $ansbunfull = $row[19];
            $comment    = $row[20];
            $searchlabel= $row[21];
            $answerIndex= $row[22]; // 正答位置設定確認用

            $bun = str_replace( array("\r\n", "\r", "\n"), '' , $bun);

            // T列、V列を全角数字に表示するため先頭にアポストロフィ（’）が入っているので除去する
            if (!empty($answerIndex)) {
                $choices = preg_replace('/^’/', '', $choices);
                $ansbun = preg_replace('/^’/', '', $ansbun);
            }

            $midasino   = $this->emptyToZero( $midasino );
            $revno      = $this->emptyToZero( $revno  );
            $revpno     = $this->emptyToZero( $revpno );
            $levelno    = $this->emptyToZero( $levelno );
            $freqencyno = $this->emptyToZero( $freqencyno );
            $choicesnum = $this->emptyToZero( $choicesnum );

            $midasino   = $this->invalidDataToZero($midasino, $shomonNo, 'MIDASINO');
            $revno      = $this->invalidDataToZero($revno, $shomonNo, 'REVNO');
            $revpno     = $this->invalidDataToZero($revpno, $shomonNo, 'REVPNO');
            $levelno    = $this->invalidDataToZero($levelno, $shomonNo, 'LEVELNO');
            $freqencyno = $this->invalidDataToZero($freqencyno, $shomonNo, 'FREQENCYNO');
            $choicesnum = $this->invalidDataToZero($choicesnum, $shomonNo, 'CHOICESNUM');

            fputcsv($fp, [
                $shomonNo, // SYOMONNO
                $daimonno, // DAIMONNO
                $secno, // SECNO
                $syubetuno, // SYUBETUNO
                $seqno, // SEQNO
                $midasino, // MIDASINO
                $midasiname, // MIDASINAME
                $revno, // REVNO
                $revpno, // REVPNO
                $levelno, // LEVELNO
                $freqencyno, // FREQENCYNO
                $bun, // BUN
                $page, // PAGE
                $anslength, // ANSLENGTH
                $ansnum, // ANSNUM
                $ansbun, // ANSBUN
                $choices, // CHOICES
                $choicesnum, // CHOICESNUM
                $answerfrom, // ANSWERFROM
                $filename, // FILENAME
                $ansbunfull, // ANSBUNFULL
                $comment, // COMMENT
                $searchlabel  // SEARCHLABEL
            ]);

            ++$seqno;
        }
        fclose($fp);

        $this->DebugLog('DONE');
    }

    /**
     * @param $value
     * @return int|mixed
     */
    private function emptyToZero($value)
    {
        if ($value === null || $value === '') return 0;
        return $value;
    }

    /**
     * @param $value
     * @param $shomonNo
     * @param $keyName
     * @return float|int|mixed|string
     */
    private function invalidDataToZero($value, $shomonNo, $keyName)
    {
        // 数字のみじゃない場合
        if (!preg_match('/^([0-9,\-\.]+)$/', $value))
        {
            $this->DebugLog("{$shomonNo} の {$keyName} (値: {$value}) が数字のみではないので 0 に変換しました。");
            return 0;
        }

        return $value;
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @param array $setting
     * @param array $header
     * @return void
     */
    private function ExportTC06_08(Spreadsheet $spreadsheet, array $setting, array $header)
    {
        $sheet = $spreadsheet->getSheetByName($setting['sheetName']);
        $isSkip = empty($sheet);

        if (!$isSkip)
        {
            // 値がない場合はスルー
            $firstValue = $sheet->getCell('A2')->getValue();
            if ($firstValue === '' || $firstValue === null) $isSkip = true;
        }

        if ($isSkip) {
            $this->DebugLog('SKIP');
            return;
        }

        // データ開始行と終了行を取得
        $startRow = $this->GetStartRow($sheet);
        $endRow = $this->GetEndRow($sheet);

        // データ取得
        $values = $sheet->rangeToArray("A{$startRow}:B{$endRow}");

        // ファイル出力
        $fp = fopen("{$this->currentExportDirectoryPath}/{$setting['exportFileName']}.csv", 'w');

        fputcsv($fp, $header);

        foreach ($values as $row) fputcsv($fp, $row);
        fclose($fp);

        $this->DebugLog('DONE');
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @param array $setting
     * @return void
     */
    private function ExportOtherAnswer(Spreadsheet $spreadsheet, array $setting)
    {
        $sheet = $spreadsheet->getSheetByName($setting['sheetName']);

        if (empty($sheet)) {
            $this->DebugLog('SKIP');
            return;
        }

        // データ開始行と終了行を取得
        $startRow = $this->GetStartRow($sheet);
        $endRow = $this->GetEndRow($sheet);

        // データ取得
        $otherAnswerNumbers = $sheet->rangeToArray("AH{$startRow}:AH{$endRow}");

        // AH列は34なのでその右隣の35からが別解のデータ
        $baseCol = 35;
        $values = [];

        for ($i=0; $i<count($otherAnswerNumbers); ++$i) {
            if (!is_numeric($otherAnswerNumbers[$i][0])) continue;

            $currentRow = $startRow + $i;
            $num = $otherAnswerNumbers[$i][0];
            $shomonNo = $sheet->getCell([1, $currentRow])->getValue();

            // 答えごとに別のレコードにする
            for ($colIterator=0; $colIterator<$num; ++$colIterator) {
                $currentValue = $sheet->getCell([$baseCol + $colIterator, $currentRow])->getValue();

                // 前後の空白を除去する
                if (is_string($currentValue)) $currentValue = trim($currentValue);

                $values[] = [$shomonNo, $currentValue];
            }
        }

        // ファイル出力
        $fp = fopen("{$this->currentExportDirectoryPath}/{$setting['exportFileName']}.csv", 'w');

        fputcsv($fp, ['syomon_no','answer']);

        foreach ($values as $row) fputcsv($fp, $row);
        fclose($fp);

        $this->DebugLog('DONE');
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @param array $setting
     * @return void
     */
    private function ExportAnswerIndex(Spreadsheet $spreadsheet, array $setting)
    {
        $sheet = $spreadsheet->getSheetByName($setting['sheetName']);

        if (empty($sheet)) {
            $this->DebugLog('SKIP');
            return;
        }

        // データ開始行と終了行を取得
        $startRow = $this->GetStartRow($sheet);
        $endRow = $this->GetEndRow($sheet);

        // データ取得
        $otherAnswerNumbers = $sheet->rangeToArray("AG{$startRow}:AG{$endRow}");
        $values = [];

        for ($i=0; $i<count($otherAnswerNumbers); ++$i) {
            if (!is_numeric($otherAnswerNumbers[$i][0])) continue;

            $currentRow = $startRow + $i;
            $shomonNo = $sheet->getCell([1, $currentRow])->getValue();

            $currentValue = $sheet->getCell("AG{$currentRow}")->getValue();

            // 前後の空白を除去する
            if (is_string($currentValue)) $currentValue = trim($currentValue);

            $values[] = [$shomonNo, $currentValue];
        }

        // ファイル出力
        $fp = fopen("{$this->currentExportDirectoryPath}/{$setting['exportFileName']}.csv", 'w');

        fputcsv($fp, ['syomon_no','answer_index']);

        foreach ($values as $row) fputcsv($fp, $row);
        fclose($fp);

        $this->DebugLog('DONE');
    }

    /**
     * @param Worksheet $sheet
     * @return int
     */
    private function GetStartRow(Worksheet $sheet): int
    {
        for ($i=2; $i<10; ++$i)
        {
            $value = $sheet->getCell("A{$i}")->getCalculatedValue();
            if (!is_numeric($value)) continue;

            return $i;
        }

        return 2;
    }

    /**
     * @param Worksheet $sheet
     * @return int
     */
    private function GetEndRow(Worksheet $sheet): int
    {
        $row = $sheet->getHighestRow();
        for ($i=$row; $i>0; --$i) {
            $value = $sheet->getCell("A{$i}")->getCalculatedValue();

            if (!is_numeric($value)) continue;

            return $i;
        }

        return 0;
    }
}
