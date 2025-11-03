<?php

namespace IizunaLMS\Helpers;

use IizunaLMS\Datas\TeacherLoginData;
use IizunaLMS\LmsTickets\LmsTicketLoader;

class TeacherSmartyHelper
{
    public static function GetSmarty(TeacherLoginData $teacher=null)
    {
        $smarty = new \Smarty();
        $smarty->compile_dir = dirname(__DIR__) . '/smarty_template_c';

        if (!empty($teacher))
        {
            $smarty->assign('isJuku', $teacher->is_juku);
            $smarty->assign('isPaid', $teacher->is_paid);
            $smarty->assign('haveOnigiriTicket', (new LmsTicketLoader())->HaveOnigiriTicket($teacher->id));
            $smarty->registerPlugin('modifier', 'format_lms_codes', 'IizunaLMS\\Helpers\\TeacherSmartyHelper::formatLmsCodes');
        }

        return $smarty;
    }

    /**
     * LMSコード名の区切り文字を<br>タグに変換する Smarty 修飾子
     *
     * @param string $string LMSコード名（||区切り）
     * @return string 変換後の文字列
     */
    public static function formatLmsCodes($string)
    {
        if (empty($string)) {
            return '未設定';
        }

        // まずHTMLエスケープしてから区切り文字を<br>に置換
        $escaped = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        return str_replace('||', '<br>', $escaped);
    }
}