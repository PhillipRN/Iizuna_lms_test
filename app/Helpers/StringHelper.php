<?php

namespace IizunaLMS\Helpers;

class StringHelper
{
    const SECURITY_SALT = 'vmh923t87qycmx0vt9r32cynf3t28c7v';
    private static $chars = 'abcdefghjkmnpqrstuvwxy3456789';
    private static $charsIncludeUpperLetterAndUnderScore = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_';
    private static $charsIncludeUpperLetterAndSymbol = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!#$%&()*+,-.:;<=>?@[]^_`{|}~';

    /**
     * @param $str
     * @return string
     */
    public static function GetHashedString($str)
    {
        return hash('sha256', $str . self::SECURITY_SALT);
    }

    /**
     * @param $length
     * @return string
     * @throws \Exception
     */
    public static function GetRandomString($length) {
        $str = '';
        $chars_length = strlen(self::$chars) - 1;
        for ($i = 0; $i < $length; ++$i)
        {
            $str .= self::$chars[random_int(0, $chars_length)];
        }
        return $str;
    }

    /**
     * @param $length
     * @return string
     * @throws \Exception
     */
    public static function GetRandomStringIncludeUpperLetterAndUnderScore($length) {
        $str = '';
        $chars_length = strlen(self::$charsIncludeUpperLetterAndUnderScore) - 1;
        for ($i = 0; $i < $length; ++$i)
        {
            $str .= self::$charsIncludeUpperLetterAndUnderScore[random_int(0, $chars_length)];
        }
        return $str;
    }

    /**
     * @param $length
     * @return string
     * @throws \Exception
     */
    public static function GetRandomStringIncludeUpperLetterAndSymbol($length) {
        $str = '';
        $chars_length = strlen(self::$charsIncludeUpperLetterAndSymbol) - 1;
        for ($i = 0; $i < $length; ++$i)
        {
            $str .= self::$charsIncludeUpperLetterAndSymbol[random_int(0, $chars_length)];
        }
        return $str;
    }

    /**
     * @param $str
     * @return string
     */
    public static function ConvertEncodingToUTF8($str)
    {
        $fromEncoding = mb_detect_encoding($str, ['ASCII', 'ISO-2022-JP', 'UTF-8', 'EUC-JP', 'SJIS'], true);

        if ($fromEncoding === false)
        {
            $fromEncoding = 'SJIS';
        }

        return mb_convert_encoding($str, 'UTF-8', $fromEncoding);
    }
}