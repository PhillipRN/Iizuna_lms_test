<?php

namespace IizunaLMS\Helpers;

class OSDetectHelper
{
    public static function IsIOS()
    {
        $pattern = '/ipod|ipad|iphone|macintosh/i';
        return preg_match($pattern, $_SERVER['HTTP_USER_AGENT']);
    }
}