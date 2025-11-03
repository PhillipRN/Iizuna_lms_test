<?php

namespace IizunaLMS\Helpers;

class FileHelper
{
    /**
     * @param $path
     * @return bool
     */
    public static function CreateDirectory($path)
    {
        if (!is_writable( dirname($path) ))
        {
            error_log("The parent directory does not allow writing.");
            return false;
        }

        if (!mkdir($path))
        {
            error_log("Failed to create the log directory.");
            return false;
        }

        chmod($path, 0771);

        return true;
    }

    /**
     * ディレクトリ一覧取得
     * @param $targetDirectoryPath
     * @return array
     */
    public static function GetDirectories($targetDirectoryPath): array
    {
        $directories = glob($targetDirectoryPath . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
        $result = [];

        foreach ($directories as $directoryPath) $result[] = $directoryPath;

        return $result;
    }

    /**
     * ファイル一覧取得
     * @param $directoryPath
     * @param string $extension
     * @return array
     */
    public static function GetFiles($directoryPath, string $extension='*'): array
    {
        $fileNames = glob($directoryPath . DIRECTORY_SEPARATOR . "*.{$extension}");
        $result = [];

        foreach ($fileNames as $fileName) $result[] = $fileName;

        return $result;
    }

    /**
     * 指定したフォルダを削除する
     * @param $del_path
     * @return void
     */
    public static function DeleteDirectory($del_path){
        if (file_exists($del_path)) {
            self::DeleteFiles($del_path);
            rmdir($del_path);
        }
    }

    /**
     * 指定したフォルダ未満の全てのファイルを削除します
     * @param $del_path
     * @return void
     */
    public static function DeleteFiles($del_path){
        if(file_exists($del_path)){
            $files = glob($del_path . DIRECTORY_SEPARATOR . '*');
            if(!empty($files)){
                foreach($files as $file){
                    if(is_dir($file)){
                        self::DeleteFiles($file);
                        rmdir($file);
                    }
                    else{
                        unlink($file);
                    }
                }
            }
        }
    }
}