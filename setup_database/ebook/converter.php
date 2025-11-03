<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Helpers\FileHelper;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;


(new ConverterEbook())->Convert();

class ConverterEbook
{
    private array $reCreateDirectories = [];

    public function Convert()
    {
        // 日本語ファイルの名前を取得するためロケールを設定する
        setlocale(LC_ALL, 'ja_JP.UTF-8');

        // メモリ制限を緩和
        ini_set('memory_limit', '512M');

        // タイムアウトを無効化
        set_time_limit(0);

        $this->DebugLog("コンバート開始します。");

        $this->ConvertSheetData('ebook_example');
        $this->ConvertSheetData('ebook_quiz');

        $this->DebugLog("コンバート終了しました。");
    }

    private function ConvertSheetData($csvType)
    {
        $this->DebugLog("{$csvType} の処理を開始します。");
        $directories = glob(__DIR__ . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . $csvType . DIRECTORY_SEPARATOR . '*');

        foreach ($directories as $directoryPath) {
            if (!is_dir($directoryPath)) continue;

            $fileNames = glob($directoryPath . DIRECTORY_SEPARATOR . '*.xlsx');
            if (empty($fileNames)) continue;

            $columnArray = [];

            switch ($csvType) {
                case 'ebook_example':
                    $columnArray = ['G', 'A', 'B', 'C', 'D', 'E', 'F', 'H', 'I', 'J'];
                    break;

                case 'ebook_quiz':
                    $columnArray = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC'];
                    break;

                default:
                    return;
            }

            $reader = new XlsxReader();
            $dataMap = [];
            $keyArray = [];

            // 列データ取得
            foreach ($fileNames as $file) {
                $spreadsheet = $reader->load($file);
                $sheet = $spreadsheet->getSheet(0);
                $max_row = $sheet->getHighestRow();

                foreach ($columnArray as $column) {
                    if (empty($dataMap[$column])) {
                        $dataMap[$column] = $sheet->rangeToArray("{$column}2:{$column}{$max_row}");
                    } else {
                        $dataMap[$column] = array_merge($dataMap[$column], $sheet->rangeToArray("{$column}2:{$column}{$max_row}"));
                    }
                }

                $spreadsheet->disconnectWorksheets();
                $spreadsheet->garbageCollect();
                unset($spreadsheet);
            }

            // 項目名取得
            $spreadsheet = $reader->load($fileNames[0]);
            $sheet = $spreadsheet->getSheet(0);
            foreach ($columnArray as $column) {
                $keyArray[] = $sheet->getCell("{$column}1")->getValue();
            }

            $spreadsheet->disconnectWorksheets();
            $spreadsheet->garbageCollect();
            unset($spreadsheet);

            // データをまとめる
            $dataArray = [];
            $count = count($dataMap[$columnArray[0]]);

            for ($i = 0; $i < $count; ++$i) {
                $data = [];
                foreach ($columnArray as $column) {
                    $data[] = $dataMap[$column][$i][0];
                }

                if (empty($data[0])) continue;

                $dataArray[] = $data;
            }

            $directoryName = pathinfo($directoryPath)['basename'];

            // 書籍ID取得
            preg_match('/^([0-9]{5})/', $directoryName, $matches);

            if (empty($matches))
            {
                $this->DebugLog("{$directoryName} の BookID を取得できませんでした。");
                continue;
            }

            $bookId = $matches[1];

            $exportDirectoryPath = __DIR__ . DIRECTORY_SEPARATOR . 'export' . DIRECTORY_SEPARATOR . $bookId;

            if (in_array($bookId, $this->reCreateDirectories)) {
                // 既存の出力先フォルダを削除
                FileHelper::DeleteDirectory($exportDirectoryPath);

                // エクスポートフォルダ内に出力先フォルダを新たに作る
                FileHelper::CreateDirectory($exportDirectoryPath);

                $this->reCreateDirectories[] = $bookId;
            }

            $fp = fopen($exportDirectoryPath . DIRECTORY_SEPARATOR . $csvType . '.csv', 'w');

            fputcsv($fp, $keyArray);

            foreach ($dataArray as $data) {
                fputcsv($fp, $data);
            }

            fclose($fp);
            $this->DebugLog("{$bookId} の処理を完了しました。");
        }
        $this->DebugLog("{$csvType} の処理を完了しました。");
    }

    private function DebugLog($message)
    {
        echo(date('Y-m-d H:i:s') . ": {$message}\n");
    }
}

