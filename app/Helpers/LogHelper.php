<?php

namespace IizunaLMS\Helpers;

class LogHelper
{
    /**
     * @param $message
     */
    public static function OutputErrorLog($message)
    {
        $file = LOG_DIR . '/' . ERROR_LOG_FILE;

        $from = "";
        $backtrace = debug_backtrace();
        if (!empty($backtrace))
        {
            $from = "({$backtrace[0]['file']}:{$backtrace[0]['line']})";
        }

        $message = "[" . date("Y/m/d H:i:s") . "] {$message}{$from}\n";

        if (!is_dir(LOG_DIR))
        {
            if (!self::CreateDirectory(LOG_DIR)) return;
        }

        file_put_contents($file, $message, FILE_APPEND);
    }

    /**
     * @param $path
     * @return bool
     */
    public static function CreateDirectory($path)
    {
        if (!is_writable( dirname($path) ))
        {
            error_log("The parent directory of the log directory does not allow writing.");
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
}