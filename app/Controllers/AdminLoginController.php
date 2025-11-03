<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Helpers\RequestHelper;

/**
 * Class AdminLoginController
 */
class AdminLoginController
{
    /**
     * @param $loginId
     * @param $password
     * @return bool
     */
    public function Login($loginId, $password)
    {
        $ip = RequestHelper::GetIp();

        // 同一IPから規定回数を越えたログイン失敗はブロックする
        if (!empty($ip) && !$this->GetTryLoginController()->CheckEnableLoginIp($ip, true))
        {
            return ERROR_LOGIN_BLOCK_IP;
        }

        if ($loginId == ADMIN_LOGIN_ID && $password == ADMIN_LOGIN_PW)
        {
            $_SESSION[SESS_ADMIN_LOGIN_TIME] = time();

            if (!empty($ip))
            {
                // 失敗のカウントをクリアする
                $this->GetTryLoginController()->DeleteByIp($ip, true);
            }

            return ERROR_NONE;
        }

        // 失敗をカウントアップする
        if (!empty($ip))
        {
            $this->GetTryLoginController()->CountUpByIp($ip, true);
        }

        return ERROR_LOGIN_FAILED;
    }

    public function Logout()
    {
        unset($_SESSION[SESS_ADMIN_LOGIN_TIME]);
    }

    /**
     * @return bool
     */
    public static function IsLogin()
    {
        return (isset($_SESSION[SESS_ADMIN_LOGIN_TIME]) && !empty($_SESSION[SESS_ADMIN_LOGIN_TIME]));
    }

    /**
     * @return mixed
     */
    public static function GetUserId()
    {
        return $_SESSION[SESS_USER_ID];
    }

    /**
     * @param $loginId
     * @param $password
     * @return array
     */
    public function ValidateLoginParameters($loginId, $password)
    {
        $errors = [];

        if (empty($loginId)) {
            $errors[] = "IDを入力してください";
        }

        if (empty($password)) {
            $errors[] = "PWを入力してください";
        }

        return $errors;
    }

    private ?TryLoginController $_TryLoginController = null;

    private function GetTryLoginController(): TryLoginController
    {
        if ($this->_TryLoginController != null) return $this->_TryLoginController;

        $this->_TryLoginController = new TryLoginController();

        return $this->_TryLoginController;
    }
    
    /**
     * @param TryLoginController $TryLoginController
     * @return void
     */
    public function AttachTryLoginController(TryLoginController $TryLoginController)
    {
        $this->_TryLoginController = $TryLoginController;
    }
}