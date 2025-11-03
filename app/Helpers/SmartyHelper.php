<?php

namespace IizunaLMS\Helpers;

class SmartyHelper
{
    public static function GetSmarty()
    {
        $smarty = new \Smarty();
        $smarty->compile_dir = dirname(__DIR__) . '/smarty_template_c';
        return $smarty;
    }
}