<?php
require_once (__DIR__ . '/../bootstrap.php');

use IizunaLMS\Commands\SetupBookRange;

try {
    $SetupBookRange = new SetupBookRange();
    $SetupBookRange->Setup();
    exit(0);
} catch (\Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
    exit(1);
}
