<?php
require_once (__DIR__ . '/../../app/bootstrap.php');

use IizunaLMS\Controllers\AdminBookUploadController;
use IizunaLMS\Controllers\AdminLoginController;
use IizunaLMS\Helpers\SmartyHelper;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Helpers\FileHelper;
use IizunaLMS\Services\BookUpload\BookUploadValidationException;

if (!AdminLoginController::IsLogin()) {
    header('Location: ./login.php');
    exit;
}

$controller = new AdminBookUploadController();

cleanupUploadArtifacts();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['mode'] ?? '') === 'start')) {
    header('Content-Type: application/json');
    try {
        $response = $controller->QueueUpload($_FILES['excel_files'] ?? [], $_POST);
        echo json_encode(['ok' => true] + $response, JSON_UNESCAPED_UNICODE);
    } catch (BookUploadValidationException $validationException) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'errors' => $validationException->getErrors()], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $throwable) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'errors' => [$throwable->getMessage()]], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if (($_GET['mode'] ?? '') === 'status') {
    header('Content-Type: application/json');
    $jobUuid = $_GET['job_uuid'] ?? '';
    $job = $controller->GetJobStatus($jobUuid);
    if (!$job) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'ジョブが見つかりませんでした。'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $warnings = json_decode($job['warnings_payload'] ?? '[]', true) ?: [];
    $resultPayload = json_decode($job['result_payload'] ?? 'null', true);

    echo json_encode([
        'ok' => true,
        'job' => [
            'job_uuid' => $job['job_uuid'],
            'status' => $job['status'],
            'message' => $job['message'],
            'file_count' => $job['file_count'],
            'folder_name' => $job['folder_name'],
            'is_dry_run' => (int)$job['is_dry_run'],
            'log_path' => $job['log_path'],
            'updated_at' => $job['updated_at'],
        ],
        'warnings' => $warnings,
        'result' => $resultPayload,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$jobs = $controller->GetLatestJobs(200);

$jobsPrimary = array_slice($jobs, 0, 5);
$jobsSecondary = array_slice($jobs, 5, 15);
$jobsModal = array_slice($jobs, 20);

$latestResult = null;
foreach ($jobs as $jobRow) {
    if (!empty($jobRow['result_payload'])) {
        $latestResult = json_decode($jobRow['result_payload'], true);
        break;
    }
}

$jobsModalJson = json_encode(array_values($jobsModal), JSON_UNESCAPED_UNICODE);

$smarty = SmartyHelper::GetSmarty();
$smarty->assign('jobsPrimary', $jobsPrimary);
$smarty->assign('jobsSecondary', $jobsSecondary);
$smarty->assign('jobsModal', $jobsModal);
$smarty->assign('jobsModalJson', $jobsModalJson);
$smarty->assign('jobs', $jobsPrimary); // backwards compat
$smarty->assign('latestResult', $latestResult);
$smarty->assign('defaultFolder', date('Ymd_His'));
$smarty->display('_book_upload.html');

function cleanupUploadArtifacts(): void
{
    $pdo = PDOHelper::GetPDO();
    $importBase = ROOT_DIR . '/setup_database/iizuna_lms/import';
    $exportBase = ROOT_DIR . '/setup_database/iizuna_lms/export';

    // Remove import folders for successful jobs (no longer needed once DBに反映済)
    $sth = $pdo->query("SELECT folder_name FROM book_upload_jobs WHERE status='success'");
    while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
        $folder = trim($row['folder_name'] ?? '');
        if ($folder === '') continue;
        FileHelper::DeleteDirectory($importBase . '/' . $folder);
    }

    // Remove export folders older than 60 days for successful jobs
    $cutoff = date('Y-m-d H:i:s', strtotime('-60 days'));
    $sth = $pdo->prepare("SELECT folder_name FROM book_upload_jobs WHERE status='success' AND updated_at < :cutoff");
    $sth->bindValue(':cutoff', $cutoff);
    $sth->execute();
    while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
        $folder = trim($row['folder_name'] ?? '');
        if ($folder === '') continue;
        FileHelper::DeleteDirectory($exportBase . '/' . $folder);
    }

    // Remove old dry-run folders except the most recent one
    $dryRuns = $pdo->query("SELECT folder_name FROM book_upload_jobs WHERE status='dry-run' ORDER BY updated_at DESC")->fetchAll(\PDO::FETCH_ASSOC);
    $keepFirst = true;
    foreach ($dryRuns as $row) {
        $folder = trim($row['folder_name'] ?? '');
        if ($folder === '') continue;
        if ($keepFirst) {
            $keepFirst = false;
            continue; // keep latest dry-run for potential反映
        }
        FileHelper::DeleteDirectory($importBase . '/' . $folder);
        FileHelper::DeleteDirectory($exportBase . '/' . $folder);
    }
}
