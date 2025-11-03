<?php

namespace IizunaLMS\Helpers;

use IizunaLMS\Errors\ErrorMessage;

class DisplayErrorHelper
{
    const SESS_ERROR_MESSAGE = 'sess_error_message';
    /**
     * @param $errorCode
     * @return string
     */
    public static function RedirectStudentErrorPage($errorCode)
    {
        $_SESSION[self::SESS_ERROR_MESSAGE] = ErrorMessage::GetMessage($errorCode);
        header('Location: ' . WWW_ROOT_URL . '/student/error.php');
        exit;
    }
    /**
     * @param $errorCode
     * @return string
     */
    public static function RedirectTeacherErrorPage($errorCode)
    {
        $_SESSION[self::SESS_ERROR_MESSAGE] = ErrorMessage::GetMessage($errorCode);
        header('Location: ' . WWW_ROOT_URL . '/teacher/error.php');
        exit;
    }
}