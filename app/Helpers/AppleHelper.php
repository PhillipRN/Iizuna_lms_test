<?php

namespace IizunaLMS\Helpers;

class AppleHelper
{
    /**
     * NOTE:
     * 申請しているバージョン（未来のバージョン）を指定する。
     * アプリのバージョンが指定バージョン以上の場合、申請モードフラグが立つ。
     * アプリ申請通過後はバージョンを上げ、次のバージョンに備える。
     */
    private static string $applicationVersion = '1.0.7';

    public static function IsApplicationForApple(): bool
    {
        if (!AppHelper::IsIOS()) return false;

        return AppHelper::IsOverVersion(self::$applicationVersion);
    }
}