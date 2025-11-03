<?php

namespace IizunaLMS\Helpers;

class AppHelper
{
    const OS_IOS = 1;
    const OS_ANDROID = 2;

    public static function IsIOS()
    {
        if (!SessionHelper::IssetOS()) return false;

        return SessionHelper::GetOS() == self::OS_IOS;
    }

    public static function GetAppVersion()
    {
        return (SessionHelper::IssetAppVersion()) ? SessionHelper::GetAppVersion() : 0;
    }

    public static function SetAppOSAndVersion($os, $version)
    {
        SessionHelper::SetOS($os);
        SessionHelper::SetAppVersion($version);
    }

    public static function IsOverVersion($targetVersion): bool
    {
        $currentVersions = explode('.', self::GetAppVersion());
        $targetVersions = explode('.', $targetVersion);

        for ($i = 0; $i < count($targetVersions); $i++) {
            if (count($currentVersions) <= $i) break;

            if (intval($currentVersions[$i]) < intval($targetVersions[$i])) {
                return false;
            }
        }

        return true;
    }
}
