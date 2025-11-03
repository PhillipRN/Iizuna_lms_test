<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Datas\TeacherLoginData;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\SchoolModel;
use IizunaLMS\Models\TeacherModel;
use IizunaLMS\Helpers\SessionHelper;
use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Errors\Error;
use IizunaLMS\Models\TeacherSchoolModel;

class TeacherLoginController
{
    /**
     * @param $loginId
     * @param $password
     * @return int
     */
    public static function Login($loginId, $password)
    {
        $ip = RequestHelper::GetIp();
        $TryLoginController = new TryLoginController();

        // FIXME 生徒先生の場合はログインIDに紐づけてブロックするようにする 同一IPから規定回数を越えたログイン失敗はブロックする
//        if (!empty($ip) && !$TryLoginController->CheckEnableLoginIp($ip))
//        {
//            return Error::ERROR_LOGIN_LIMIT_IP;
//        }

        $teacher = (new TeacherSchoolModel())->GetWithLoginIdAndPassword($loginId, StringHelper::GetHashedString($password));
        if (!empty($teacher))
        {
            SessionHelper::SetLoginTeacherId($teacher['id']);

            if (!empty($ip))
            {
                // 失敗のカウントをクリアする
                $TryLoginController->DeleteByIp($ip);
            }

            return Error::ERROR_NONE;
        }

        // 失敗をカウントアップする
//        if (!empty($ip))
//        {
//            $TryLoginController->CountUpByIp($ip);
//        }

        return Error::ERROR_LOGIN_FAILED;
    }

    /**
     * @return TeacherLoginData|null
     */
    public static function GetTeacherData(): ?TeacherLoginData
    {
        if (!SessionHelper::IssetLoginTeacherId()) return null;

        $teacherId = SessionHelper::GetLoginTeacherId();
        $teacher = (new TeacherSchoolModel())->GetById($teacherId);

        return new TeacherLoginData($teacher);
    }

    /**
     * @return void
     */
    public static function Logout()
    {
        SessionHelper::UnsetLoginTeacherId();
    }

    /**
     * @return bool
     */
    public static function IsLogin(): bool
    {
        return SessionHelper::IssetLoginTeacherId();
    }
}