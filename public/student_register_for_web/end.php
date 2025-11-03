<?php
require_once(__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Helpers\SmartyHelper;

$smarty = SmartyHelper::GetSmarty();
$smarty->display('_end.html');
