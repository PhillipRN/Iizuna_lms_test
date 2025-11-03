<?php

namespace IizunaLMS\JsonQuizzes;

class JsonQuizStringHelper
{
    public static function ReplaceUnneededTagsAndWhiteSpace($str)
    {
        $str = preg_replace('/<rt>([^\/]+)<\/rt>/u', '', $str);
        $str = strip_tags($str);

        // FIXME マスターそのもののデータの末尾に余計な空白が大量に入っているため、暫定処置。理想はマスター側を修正し、プログラムで除去する処理はしないようにしたい。
        $str = preg_replace('/&nbsp;/u', '', $str);
        return preg_replace("/\A[\\x0-\x20\x7F\xC2\xA0\xE3\x80\x80]++|[\\x0-\x20\x7F\xC2\xA0\xE3\x80\x80]++\z/u", '', $str);
    }
}