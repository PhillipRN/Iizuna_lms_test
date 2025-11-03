<?php

namespace IizunaLMS\Helpers;

use IizunaLMS\Requests\RequestParamStudentRegisterForWeb;
use IizunaLMS\Students\Datas\StudentPageParameters;

class SessionHelper
{
    const SESS_LOGIN_TEACHER_ID = 'SESS_LOGIN_TEACHER_ID';
    const SESS_LOGIN_STUDENT_DATA = 'SESS_LOGIN_STUDENT_DATA';
    const SESS_STUDENT_REGISTER_FOR_WEB_DATA = 'SESS_STUDENT_REGISTER_FOR_WEB_DATA';
    const SESS_QUIZ_IS_EXPIRED = 'SESS_QUIZ_IS_EXPIRED';
    const SESS_STUDENT_PAGE_PARAMETERS = 'SESS_STUDENT_PAGE_PARAMETERS';
    const SESS_STUDENT_REGISTER_FOR_WEB_IS_APP = 'SESS_STUDENT_REGISTER_FOR_WEB_IS_APP';
    const SESS_STUDENT_BEFORE_LOGIN_URL = 'SESS_STUDENT_BEFORE_LOGIN_URL';
    const SESS_APP_VERSION = 'SESS_APP_VERSION';
    const SESS_OS = 'SESS_OS';

    public static function IssetLoginTeacherId()
    {
        return isset($_SESSION[self::SESS_LOGIN_TEACHER_ID]);
    }

    public static function SetLoginTeacherId($data)
    {
        $_SESSION[self::SESS_LOGIN_TEACHER_ID] = $data;
    }

    public static function GetLoginTeacherId()
    {
        return $_SESSION[self::SESS_LOGIN_TEACHER_ID];
    }

    public static function UnsetLoginTeacherId()
    {
        unset($_SESSION[self::SESS_LOGIN_TEACHER_ID]);
    }
    
    public static function IssetLoginStudentData()
    {
        return isset($_SESSION[self::SESS_LOGIN_STUDENT_DATA]);
    }

    public static function SetLoginStudentData($data)
    {
        $_SESSION[self::SESS_LOGIN_STUDENT_DATA] = $data;
    }

    public static function GetLoginStudentData()
    {
        return $_SESSION[self::SESS_LOGIN_STUDENT_DATA];
    }

    public static function UnsetLoginStudentData()
    {
        unset($_SESSION[self::SESS_LOGIN_STUDENT_DATA]);
    }

    public static function IssetStudentRegisterForWebData()
    {
        return isset($_SESSION[self::SESS_STUDENT_REGISTER_FOR_WEB_DATA]);
    }

    public static function SetAppVersion($data)
    {
        $_SESSION[self::SESS_APP_VERSION] = $data;
    }

    public static function GetAppVersion()
    {
        return $_SESSION[self::SESS_APP_VERSION];
    }

    public static function UnsetAppVersion()
    {
        unset($_SESSION[self::SESS_APP_VERSION]);
    }

    public static function IssetAppVersion()
    {
        return isset($_SESSION[self::SESS_APP_VERSION]);
    }

    public static function SetOS($data)
    {
        $_SESSION[self::SESS_OS] = $data;
    }

    public static function GetOS()
    {
        return $_SESSION[self::SESS_OS];
    }

    public static function UnsetOS()
    {
        unset($_SESSION[self::SESS_OS]);
    }

    public static function IssetOS()
    {
        return isset($_SESSION[self::SESS_OS]);
    }

    /**
     * @param RequestParamStudentRegisterForWeb $data
     * @return void
     */
    public static function SetStudentRegisterForWebData(RequestParamStudentRegisterForWeb $data)
    {
        $_SESSION[self::SESS_STUDENT_REGISTER_FOR_WEB_DATA] = serialize($data);
    }

    /**
     * @return RequestParamStudentRegisterForWeb
     */
    public static function GetStudentRegisterForWebData() : RequestParamStudentRegisterForWeb
    {
        return unserialize($_SESSION[self::SESS_STUDENT_REGISTER_FOR_WEB_DATA]);
    }

    public static function UnsetStudentRegisterForWebData()
    {
        unset($_SESSION[self::SESS_STUDENT_REGISTER_FOR_WEB_DATA]);
    }

    /**
     * @param StudentPageParameters $data
     * @return void
     */
    public static function SetStudentPageParameters(StudentPageParameters $data)
    {
        $_SESSION[self::SESS_STUDENT_PAGE_PARAMETERS] = serialize($data);
    }

    /**
     * @return StudentPageParameters
     */
    public static function GetStudentPageParameters() : StudentPageParameters
    {
        return unserialize($_SESSION[self::SESS_STUDENT_PAGE_PARAMETERS]);
    }

    public static function UnsetStudentPageParameters()
    {
        unset($_SESSION[self::SESS_STUDENT_PAGE_PARAMETERS]);
    }

    public static function IssetStudentPageParameters()
    {
        return isset($_SESSION[self::SESS_STUDENT_PAGE_PARAMETERS]);
    }

    /**
     * @param bool $value
     * @return void
     */
    public static function SetStudentRegisterForWebIsApp(bool $value)
    {
        $_SESSION[self::SESS_STUDENT_REGISTER_FOR_WEB_IS_APP] = $value;
    }

    /**
     * @return bool
     */
    public static function GetStudentRegisterForWebIsApp() : bool
    {
        if (empty($_SESSION[self::SESS_STUDENT_REGISTER_FOR_WEB_IS_APP])) return false;

        return $_SESSION[self::SESS_STUDENT_REGISTER_FOR_WEB_IS_APP];
    }

    /**
     * @return void
     */
    public static function UnsetStudentRegisterForWebIsApp()
    {
        unset($_SESSION[self::SESS_STUDENT_REGISTER_FOR_WEB_IS_APP]);
    }

    public static function IssetStudentBeforeLoginUrl()
    {
        return isset($_SESSION[self::SESS_STUDENT_BEFORE_LOGIN_URL]);
    }

    public static function SetStudentBeforeLoginUrl($data)
    {
        $_SESSION[self::SESS_STUDENT_BEFORE_LOGIN_URL] = $data;
    }

    public static function GetStudentBeforeLoginUrl()
    {
        return $_SESSION[self::SESS_STUDENT_BEFORE_LOGIN_URL];
    }

    public static function UnsetStudentBeforeLoginUrl()
    {
        unset($_SESSION[self::SESS_STUDENT_BEFORE_LOGIN_URL]);
    }
}