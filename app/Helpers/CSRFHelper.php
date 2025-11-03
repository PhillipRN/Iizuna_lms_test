<?php

namespace IizunaLMS\Helpers;

class CSRFHelper
{
    const KEY_CSRF = '_CSRF';

    /**
     * @return string
     * @throws \Exception
     */
    public static function GenerateKey()
    {
        $key = StringHelper::GetRandomString(32);

        // cookieのオプション
        $options = [
            'expires' => time() + 60 * 60 * 24 * 365, // cookieの有効期限を1年間に設定
            'path' => '/', // 有効範囲を「ドメイン配下全て」に設定
            'httponly' => true // HTTPを通してのみcookieにアクセス可能（JavaScriptからのアクセスは不可となる）
        ];

        setcookie(self::KEY_CSRF, $key, $options);

        return $key;
    }

    /**
     * @return bool
     */
    public static function CheckPostKey()
    {
        $key = $_POST['_csrf'] ?? '';

        if (!isset($_COOKIE[self::KEY_CSRF])) return false;

        return $_COOKIE[self::KEY_CSRF] == $key;
    }

    /**
     * @return void
     */
    public static function ReleaseKey()
    {
        if (isset($_COOKIE[self::KEY_CSRF]))
        {
            setcookie(self::KEY_CSRF ,'' , time() - 100 );
        }
    }
}