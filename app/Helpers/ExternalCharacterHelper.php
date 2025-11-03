<?php

namespace IizunaLMS\Helpers;

class ExternalCharacterHelper
{
    public static function ReplaceTags($text)
    {
        $result = $text;

        preg_match_all('/<e>([^\/]+)<\/e>/', $result, $matches);

        foreach ($matches[1] as $code)
        {
            $result = self::Replace($result, $code);
        }

        return $result;
    }

    private static function Replace($text, $code)
    {
        if (empty(self::$externalCharacters[$code])) return $text;

        return preg_replace('/<e>' . $code . '<\/e>/', self::$externalCharacters[$code], $text);
    }

    private static $externalCharacters = [
        1 => '<span class="external_character kaeriten">き</span>',
        5 => '<span class="external_character kaeriten">く</span>',
        42 => '杓',
        43 => '謎',
        47 => '塡',
        48 => '鵠',
        50 => '豹',
        52 => '溺',
        54 => '堵',
        56 => '倦',
        57 => '遡',
        58 => '嘲',
        60 => '嚙',
        61 => '溢',
        63 => '厖',
        64 => '剝',
        66 => '辻',
        67 => '逢',
        68 => '<span class="external_character">え</span>',
        70 => '遜',
        73 => '蔑',
        74 => '晦',
        75 => '凜',
        76 => '搔',
        78 => '<span class="external_character">あ</span>',
        79 => '迄',
        80 => '頰',
        83 => '簞',
        85 => '禱',
        87 => '𠮷',
        88 => '箸',
        90 => '屛',
        91 => '<span class="external_character">い</span>',
        95 => '揃',
        97 => '瘦',
        98 => '凋',
        102 => '俱',
        105 => '屢',
        110 => '鷗',
        111 => '撰',
        112 => '祇',
        113 => '歎',
        114 => '菟',
        117 => '<span class="external_character">う</span>',
        118 => '葛',
        119 => '卿',
        123 => '噂',
        127 => '櫛',
        129 => '簾',
        133 => '蟬',
        135 => '<span class="external_character">お</span>', // 蓮の二点しんにょう
        136 => '巷',
        137 => '詮',
        143 => '摑',
        144 => '瀆',
        145 => '洦',
        152 => '潑',
        157 => '顚',
        166 => '芡',
        167 => '叱',
        171 => '虵',
        176 => '芧',
        193 => '牙',
        194 => '簶',
        198 => '萊',
        203 => '啞',
        204 => '梲',
        205 => '焰',
        206 => '跎',
        207 => '鵼',
        214 => '繫',
        215 => '軀',
        230 => '噓',
        231 => '簶',
        232 => '壒',
        233 => '囊',
        234 => '饒',
        245 => '啐',
        246 => '晡',
        255 => '噯',
        256 => '鏁',
        269 => '<span class="external_character">か</span>',
        270 => '尨',
    ];
}