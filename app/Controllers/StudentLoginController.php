<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Helpers\CookieHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\IStudentAutoLoginTokenModel;
use IizunaLMS\Models\IStudentLoginTokenModel;
use IizunaLMS\Models\StudentAutoLoginTokenModel;
use IizunaLMS\Models\StudentAutoLoginTokenModelDynamoDB;
use IizunaLMS\Models\StudentLoginTokenModel;
use IizunaLMS\Models\StudentLoginTokenModelDynamoDB;
use IizunaLMS\Models\StudentModel;
use IizunaLMS\Helpers\SessionHelper;
use IizunaLMS\Errors\Error;
use IizunaLMS\Students\Datas\StudentData;

class StudentLoginController
{
    const KEY_IS_APP = 'is_app';

    /**
     * @param $loginId
     * @param $password
     * @return array
     */
    public static function Login($loginId, $password)
    {
        if (empty($loginId)) return ['error' => Error::ERROR_LOGIN_FAILED];

        $ip = RequestHelper::GetIp();
        $TryLoginController = new TryLoginController();

        // FIXME 生徒先生の場合はログインIDに紐づけてブロックするようにする 同一IPから規定回数を越えたログイン失敗はブロックする
//        if (!empty($ip) && !$TryLoginController->CheckEnableLoginIp($ip))
//        {
//            return ['error' => Error::ERROR_LOGIN_LIMIT_IP];
//        }

        $student = (new StudentModel())->GetWithLoginIdAndPassword($loginId, StringHelper::GetHashedString($password));

        if (!empty($student))
        {
            SessionHelper::SetLoginStudentData($student);

            if (!empty($ip))
            {
                // 失敗のカウントをクリアする
                $TryLoginController->DeleteByIp($ip);
            }

            return ['student' => $student];
        }
        else
        {
            // 失敗をカウントアップする
//            if (!empty($ip))
//            {
//                $TryLoginController->CountUpByIp($ip);
//            }

            return ['error' => Error::ERROR_LOGIN_FAILED];
        }
    }

    /**
     * @param $loginId
     * @param $password
     * @return int
     */
    public static function LoginFromWeb($loginId, $password)
    {
        $loginResult = self::Login($loginId, $password);

        if (empty($loginResult['error']))
        {
            SessionHelper::SetLoginStudentData($loginResult['student']);
            return Error::ERROR_NONE;
        }
        else
        {
            return $loginResult['error'];
        }
    }

    /**
     * @param $loginToken
     * @return int
     */
    public static function LoginByToken($loginToken)
    {
        $hashedLoginToken = StringHelper::GetHashedString($loginToken);
        $loginTokenRecord = self::GetStudentLoginTokenModel()->GetByLoginToken($hashedLoginToken);

        if (empty($loginTokenRecord))
        {
            return Error::ERROR_STUDENT_LOGIN_TOKEN_NOT_FOUND;
        }

        $studentId = $loginTokenRecord['student_id'];

        $student = (new StudentModel())->GetById($studentId);
        if (!empty($student))
        {
            $student[self::KEY_IS_APP] = true;

            SessionHelper::SetLoginStudentData($student);

            return Error::ERROR_NONE;
        }

        return Error::ERROR_STUDENT_LOGIN_STUDENT_NOT_FOUND;
    }

    /**
     * @param $autoLoginToken
     * @return int
     */
    private static function LoginByAutoLoginToken($autoLoginToken)
    {
        $hashedAutoLoginToken = StringHelper::GetHashedString($autoLoginToken);
        $autoLoginTokenRecord = self::GetStudentAutoLoginTokenModel()->GetByAutoLoginToken($hashedAutoLoginToken);

        if (empty($autoLoginTokenRecord))
        {
            return Error::ERROR_STUDENT_AUTO_LOGIN_TOKEN_NOT_FOUND;
        }

        $studentId = $autoLoginTokenRecord['student_id'];

        $student = (new StudentModel())->GetById($studentId);
        if (!empty($student))
        {
            SessionHelper::SetLoginStudentData($student);

            return Error::ERROR_NONE;
        }

        return Error::ERROR_STUDENT_AUTO_LOGIN_STUDENT_NOT_FOUND;
    }

    /**
     * @return StudentData
     */
    public static function GetStudentData()
    {
        if (!SessionHelper::IssetLoginStudentData()) return null;

        $data = SessionHelper::GetLoginStudentData();

        if (!isset($data['id'])) return null;

        return new StudentData($data);
    }

    /**
     * @return bool
     */
    public static function IsApp()
    {
        if (!self::IsLogin()) return false;

        $data = SessionHelper::GetLoginStudentData();

        return !empty($data[self::KEY_IS_APP]);
    }

    /**
     * @return void
     */
    public static function Logout()
    {
        SessionHelper::UnsetLoginStudentData();
        CookieHelper::DeleteAutoLoginToken();
    }

    /**
     * @return bool
     */
    public static function IsLogin(): bool
    {
        return SessionHelper::IssetLoginStudentData();
    }

    /**
     * @return bool
     */
    public static function IsLoginOrTryAutoLogin(): bool
    {
        if (SessionHelper::IssetLoginStudentData())
        {
            if (StudentLoginController::GetStudentData() != null) return true;
        }

        if (CookieHelper::IssetAutoLoginToken())
        {
            $autoLoginToken = CookieHelper::GetAutoLoginToken();
            $result = self::LoginByAutoLoginToken($autoLoginToken);

            return $result == Error::ERROR_NONE;
        }

        return false;
    }

    /**
     * @return bool
     */
    public static function EnableLoginStudent()
    {
        if (!SessionHelper::IssetLoginStudentData()) return false;

        $data = SessionHelper::GetLoginStudentData();

        return !empty($data['login_id']);
    }

    public static function RedirectLoginErrorPage()
    {
        SessionHelper::SetStudentBeforeLoginUrl( $_SERVER['REQUEST_URI'] );
        header("Location: " . WWW_ROOT_URL . '/student/login_error.php');
        exit;
    }

    private static function GetStudentLoginTokenModel(): IStudentLoginTokenModel
    {
        return (USE_DYNAMO_DB) ? new StudentLoginTokenModelDynamoDB()
                               : new StudentLoginTokenModel();
    }

    private static function GetStudentAutoLoginTokenModel(): IStudentAutoLoginTokenModel
    {
        return (USE_DYNAMO_DB) ? new StudentAutoLoginTokenModelDynamoDB()
                               : new StudentAutoLoginTokenModel();
    }
}