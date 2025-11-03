<?php

namespace IizunaLMS\Onigiri;

class OnigiriJsonQuizType
{
    public static function GetTypeName($type)
    {
        switch ($type)
        {
            case 'four_choice_ja_en':
                return '日本語→英語　4択';

            case 'four_choice_en_ja':
                return '英語→日本語　4択';

            case 'input_ja_en':
                return '日本語→英語　入力';

            case 'input_fill_in_en_example':
                return '英語例文穴埋め　入力';

            case 'input_listening_en_example':
                return '英語例文聞き取り　入力';

            case 'input_listening_en_word':
                return '英単語聞き取り　入力';

            case 'four_choice_listening':
                return '英単語聞き取り　4択';

            default:
                return '';
        }
    }
}