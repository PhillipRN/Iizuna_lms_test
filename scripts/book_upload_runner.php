#!/usr/bin/env php
<?php
require_once __DIR__ . '/../app/bootstrap.php';

use IizunaLMS\Services\BookUpload\BookUploadService;

if ($argc < 2) {
    fwrite(STDERR, "Usage: book_upload_runner.php <job_uuid>\n");
    exit(1);
}

$jobUuid = $argv[1];
$service = new BookUploadService();

try {
    $service->processJob($jobUuid);
    exit(0);
} catch (Throwable $e) {
    error_log(sprintf('[BookUploadRunner] %s: %s', $jobUuid, $e->getMessage()));
    exit(1);
}
