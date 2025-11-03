<?php
require_once (__DIR__ . '/../bootstrap.php');

use IizunaLMS\Commands\SetupBookRange;

$SetupBookRange = new SetupBookRange();
$SetupBookRange->Setup();