<?php

namespace IizunaLMS\Services\BookUpload;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BookUploadValidator
{
    private array $requiredSheets = [
        '00_データ' => ['00_データ'],
        '02_問題形式' => ['02_問題形式'],
    ];

    private array $optionalSheets = [
        '03_章・節' => ['03_章・節', '03_章･節'],
        '04_大問' => ['04_大問'],
        '06_レベル' => ['06_レベル'],
        '07_頻度' => ['07_頻度'],
        '08_見出し語' => ['08_見出し語'],
    ];

    /**
     * @param string $filePath
     * @return array [errors, warnings]
     */
    public function validate(string $filePath): array
    {
        $errors = [];
        $warnings = [];

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $spreadsheet = $reader->load($filePath);
        } catch (\Throwable $e) {
            return [[sprintf('Excelファイルを読み込めません: %s', $e->getMessage())], $warnings];
        }

        foreach ($this->requiredSheets as $label => $candidates) {
            $sheet = $this->resolveSheet($spreadsheet, $candidates);
            if (!$sheet) {
                $errors[] = sprintf('%s シートが存在しません。テンプレート通りのシート名に戻してください。', $label);
                continue;
            }

            [$startRow, $endRow] = $this->detectDataRange($sheet);
            if ($endRow < $startRow) {
                $errors[] = sprintf('%s シートに有効なデータがありません。余計な行やフィルタを解除してください。', $label);
                continue;
            }

            if ($label === '02_問題形式') {
                $rateWarning = $this->validateRateColumn($sheet, $startRow, $endRow);
                if ($rateWarning) {
                    $errors[] = $rateWarning;
                }
            }

            if ($label === '00_データ') {
                $choiceErrors = $this->validateMultipleChoiceConsistency($sheet, $startRow, $endRow);
                $errors = array_merge($errors, $choiceErrors);
            }
        }

        foreach ($this->optionalSheets as $label => $candidates) {
            $sheet = $this->resolveSheet($spreadsheet, $candidates);
            if (!$sheet) {
                if ($label === '08_見出し語') {
                    $warnings[] = '注意：08_見出し語 シートが見つかりません。このシートは不要な場合このまま続けることができます。これはエラーではありません。';
                } else {
                    $warnings[] = sprintf('%s シートが見つかりません。必要なデータが欠けていないか確認してください。', $label);
                }
                continue;
            }

            [$startRow, $endRow] = $this->detectDataRange($sheet);
            if ($endRow < $startRow) {
                $warnings[] = sprintf('%s シートにデータが存在しません。', $label);
            }
        }

        return [$errors, $warnings];
    }

    private function resolveSheet($spreadsheet, array $candidates): ?Worksheet
    {
        foreach ($candidates as $candidate) {
            $sheet = $spreadsheet->getSheetByName($candidate);
            if ($sheet) {
                return $sheet;
            }
        }

        return null;
    }

    private function detectDataRange(Worksheet $sheet): array
    {
        $start = null;
        $end = null;
        $highestRow = (int)$sheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $value = $sheet->getCell("A{$row}")->getCalculatedValue();
            if (is_numeric($value)) {
                $start = $row;
                break;
            }
        }

        for ($row = $highestRow; $row >= 2; $row--) {
            $value = $sheet->getCell("A{$row}")->getCalculatedValue();
            if (is_numeric($value)) {
                $end = $row;
                break;
            }
        }

        if ($start === null || $end === null) {
            $start = 2;
            $end = 1;
        }

        return [$start, $end];
    }

    private function validateRateColumn(Worksheet $sheet, int $startRow, int $endRow): ?string
    {
        $range = sprintf('C%d:C%d', $startRow, $endRow);
        $values = $sheet->rangeToArray($range, null, true, true, true);

        $total = 0;
        foreach ($values as $row) {
            $cell = reset($row);
            if ($cell === null || $cell === '') {
                continue;
            }
            if (!is_numeric($cell)) {
                return '02_問題形式 シートの配分比率に数字以外の値が含まれています。';
            }
            $total += (float)$cell;
        }

        if ($total <= 0) {
            return '02_問題形式 シートの配分比率合計が0です。';
        }

        if (abs($total - 100) > 1) {
            return sprintf('02_問題形式 シートの配分比率合計が100になっていません（現在: %.2f）。', $total);
        }

        return null;
    }

    private function validateMultipleChoiceConsistency(Worksheet $sheet, int $startRow, int $endRow): array
    {
        $errors = [];

        for ($row = $startRow; $row <= $endRow; $row++) {
            $typeRaw = trim((string)$sheet->getCell("H{$row}")->getCalculatedValue());
            if (!$this->isFourChoiceType($typeRaw)) {
                continue;
            }

            $tRaw = trim((string)$sheet->getCell("T{$row}")->getCalculatedValue());
            $uRaw = trim((string)$sheet->getCell("V{$row}")->getCalculatedValue());

            $normalizedForCount = str_replace('／', '/', $tRaw);
            if (substr_count($normalizedForCount, '/') > 2) {
                $errors[] = sprintf('00_データ 行%d: T列の「/」は最大2個までです。現在3個以上あります。', $row);
                continue;
            }

            $tChoices = $this->splitChoices($tRaw);
            if (count($tChoices) > 3) {
                $errors[] = sprintf('00_データ 行%d: T列の選択肢は最大3個までです。（現在%d個）', $row, count($tChoices));
                continue;
            }

            if ($dup = $this->findDuplicate($tChoices)) {
                $errors[] = sprintf('00_データ 行%d: T列の選択肢に重複があります。「%s」を削除してください。', $row, $dup);
                continue;
            }

            if ($uRaw === '') {
                $errors[] = sprintf('00_データ 行%d: 4択問題はV列（正答例）を必ず入力してください。', $row);
                continue;
            }

            $allChoices = $tChoices;
            $allChoices[] = $uRaw;

            if ($dupAll = $this->findDuplicate($allChoices)) {
                $errors[] = sprintf('00_データ 行%d: T列とU列の選択肢が重複しています。「%s」を修正してください。', $row, $dupAll);
                continue;
            }

            if (count($allChoices) !== 4) {
                $errors[] = sprintf('00_データ 行%d: 4択問題は選択肢が4つ必要です。（現在%d個）', $row, count($allChoices));
            }
        }

        return $errors;
    }

    private function splitChoices(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $normalized = str_replace('／', '/', $value);
        $parts = array_map('trim', explode('/', $normalized));
        $parts = array_filter($parts, static fn($part) => $part !== '');
        return array_values($parts);
    }

    private function findDuplicate(array $choices): ?string
    {
        $seen = [];
        foreach ($choices as $choice) {
            $normalized = mb_strtolower($choice);
            if (isset($seen[$normalized])) {
                return $choice;
            }
            $seen[$normalized] = true;
        }
        return null;
    }

    private function isFourChoiceType(string $typeValue): bool
    {
        if ($typeValue === '') {
            return false;
        }

        if (is_numeric($typeValue) && (int)$typeValue === 1) {
            return true;
        }

        $normalized = preg_replace('/\s+/u', '', $typeValue);
        $patterns = ['4択', '４択', '四択'];
        foreach ($patterns as $pattern) {
            if (mb_strpos($normalized, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
