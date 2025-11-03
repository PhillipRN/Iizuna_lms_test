<?php
require_once (__DIR__ . '/app/bootstrap.php');

use IizunaLMS\Onigiri\OnigiriFirebaseNotification;

$result = (new OnigiriFirebaseNotification())->SendNotice('test', '1111');
var_dump($result);