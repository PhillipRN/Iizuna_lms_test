<?php
require_once (__DIR__ . '/../bootstrap.php');

use IizunaLMS\Models\StudentAuthorizationKeyModel;

(new StudentAuthorizationKeyModel())->DeleteExpiredData();