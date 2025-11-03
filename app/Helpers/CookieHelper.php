<?php

namespace IizunaLMS\Helpers;

class CookieHelper
{
    const KEY_AUTO_LOGIN_TOKEN = 'auto_login_token';

    /**
     * @return bool
     */
    public static function IssetAutoLoginToken(): bool
    {
        return isset($_COOKIE[self::KEY_AUTO_LOGIN_TOKEN]);
    }

    /**
     * @param $data
     * @return void
     */
    public static function SetAutoLoginToken($data)
    {
        // cookieのオプション
        $options = [
            'expires' => time() + 60 * 60 * 24 * 365, // cookieの有効期限を1年間に設定
            'path' => '/', // 有効範囲を「ドメイン配下全て」に設定
            'httponly' => true // HTTPを通してのみcookieにアクセス可能（JavaScriptからのアクセスは不可となる）
        ];

        setcookie(self::KEY_AUTO_LOGIN_TOKEN, $data, $options);
    }

    /**
     * @return mixed|null
     */
    public static function GetAutoLoginToken()
    {
        if (!self::IssetAutoLoginToken()) return null;

        return $_COOKIE[self::KEY_AUTO_LOGIN_TOKEN];
    }

    /**
     * @return void
     */
    public static function DeleteAutoLoginToken()
    {
        if (!self::IssetAutoLoginToken()) return;

        setcookie(self::KEY_AUTO_LOGIN_TOKEN, '', time() - 6000, '/');
    }
}