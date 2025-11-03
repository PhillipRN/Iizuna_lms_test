<?php

namespace IizunaLMS\Helpers;

class CsvHelper
{
    /**
     * @param $fileTmpName
     * @param $fileName
     * @return array|string[]
     */
    public static function UploadCsvFile($fileTmpName, $fileName)
    {
        if (!is_dir(TEMP_DIR))
        {
            if (!FileHelper::CreateDirectory(TEMP_DIR))
            {
                return [
                    'error' => ERROR_ADMIN_UPLOAD_FILE_FAILED
                ];
            }
        }

        $filePath = TEMP_DIR . "/" . $fileName;

        //拡張子を判定
        if (pathinfo($fileName, PATHINFO_EXTENSION) != 'csv')
        {
            return [
                'error' => ERROR_ADMIN_UPLOAD_FILE_NOT_CSV
            ];
        }

        // ファイルを移動
        if (move_uploaded_file($fileTmpName, $filePath))
        {
            chmod($filePath, 0644);

            return [
                'filePath' => $filePath
            ];
        }
        else
        {
            return [
                'error' => ERROR_ADMIN_UPLOAD_FILE_FAILED
            ];
        }
    }

    /**
     * @param $data
     * @return bool
     */
    public static function IsLastLineForCsvFile($data)
    {
        for ($i=0; $i<count($data); ++$i)
        {
            if (!empty($data[$i] != "")) return false;
        }

        return true;
    }
}