<?php
require_once (__DIR__ . '/app/bootstrap.php');

use IizunaLMS\JsonQuizzes\JsonQuizPushNotification;

(new JsonQuizPushNotification())->SendNotify([20]);