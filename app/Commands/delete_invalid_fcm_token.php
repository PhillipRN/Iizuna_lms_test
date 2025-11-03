<?php
require_once (__DIR__ . '/../bootstrap.php');

use IizunaLMS\Models\StudentFcmTokenModel;

(new StudentFcmTokenModel())->DeleteExpiredData();
(new StudentFcmTokenModel())->DeleteFailedData();