<?php
require_once (__DIR__ . '/app/bootstrap.php');

use IizunaLMS\Firebase\CloudMessaging;

$fcm = new CloudMessaging();

$result = $fcm->SendByToken(
    'FCM Message',
    'This is an FCM notification message!',
    'd1NgGuScQD2HCtBmCBdya-:APA91bF1NpNwTrnUC2tpqYLGgpV16W1zvWQev7rUTDpeYUWxHg7HKOiiCTj_xKf1hUXCea9crLe7jh4_TCCJpzVRfQdcIp44InResHA06SwytDA5wjKB9O5i5-NZjyoa8gwv15aUCN2k'
);

var_dump($result);