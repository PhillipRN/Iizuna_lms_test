<?php

namespace IizunaLMS\Services\BookUpload;

use IizunaLMS\Helpers\FileHelper;
use IizunaLMS\Models\BookUploadJobModel;
use RuntimeException;

class BookUploadService
{
    private const MAX_FILES = 5;
    private const IMPORT_BASE = ROOT_DIR . '/setup_database/iizuna_lms/import';
    private const EXPORT_BASE = ROOT_DIR . '/setup_database/iizuna_lms/export';
    private const BATCH_BASE = ROOT_DIR . '/setup_database/batch';
    private const TMP_BASE = TEMP_DIR . '/book_upload';
    private const LOG_FILE = LOG_DIR . '/book_upload.log';

    private BookUploadValidator $validator;
    private BookUploadJobModel $jobModel;

    public function __construct()
    {
        $this->validator = new BookUploadValidator();
        $this->jobModel = new BookUploadJobModel();
        $this->ensureDirectory(self::TMP_BASE);
        $this->ensureDirectory(self::IMPORT_BASE);
        $this->ensureDirectory(self::EXPORT_BASE);
        $this->ensureDirectory(self::BATCH_BASE);
        $this->ensureDirectory(dirname(self::LOG_FILE));
    }

    /**
     * ユーザー入力をキューに入れ、バックグラウンド処理を起動する
     */
    public function queueUpload(array $filePayload, array $params, ?int $userId = null): array
    {
        $files = $this->normalizeFiles($filePayload);
        if (empty($files)) {
            throw new BookUploadValidationException(['アップロードするExcelファイルを選択してください。']);
        }

        if (count($files) > self::MAX_FILES) {
            throw new BookUploadValidationException([sprintf('一度にアップロードできるファイルは最大%d件です。', self::MAX_FILES)]);
        }

        $folderName = $this->resolveFolderName($params['folder_name'] ?? '');
        $memo = trim($params['memo'] ?? '');
        $isDryRun = !empty($params['dry_run']);
        $jobUuid = $this->generateJobUuid();
        $userId = $userId ?? 0;

        $prepared = $this->prepareJobFiles($files, $folderName);
        $warnings = $prepared['warnings'];

        $this->jobModel->Add([
            'job_uuid' => $jobUuid,
            'user_id' => $userId,
            'folder_name' => $folderName,
            'file_count' => count($files),
            'status' => 'queued',
            'is_dry_run' => $isDryRun ? 1 : 0,
            'memo' => $memo,
            'log_path' => '',
            'message' => 'バックグラウンド処理を待機中',
            'warnings_payload' => json_encode($warnings, JSON_UNESCAPED_UNICODE),
        ]);

        $this->dispatchBackgroundJob($jobUuid);

        return [
            'job_uuid' => $jobUuid,
            'folder_name' => $folderName,
            'warnings' => $warnings,
        ];
    }

    /**
     * CLIワーカーから呼び出される実処理
     */
    public function processJob(string $jobUuid): void
    {
        $job = $this->jobModel->GetByUuid($jobUuid);
        if (!$job) {
            throw new RuntimeException("ジョブ {$jobUuid} が見つかりません。");
        }

        $warnings = json_decode($job['warnings_payload'] ?? '[]', true) ?: [];
        $result = null;

        try {
            $result = $this->executePipeline($job, $warnings);
            $isDryRun = (bool)$job['is_dry_run'];
            $finalStatus = $isDryRun ? 'dry-run' : 'success';
            $finalMessage = $isDryRun ? 'データチェック：正常' : 'アップロードとDB反映が完了しました。';

            $this->jobModel->UpdateByUuid($jobUuid, [
                'status' => $finalStatus,
                'message' => $finalMessage,
                'log_path' => $result['log_path'] ?? '',
                'result_payload' => json_encode($result, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable $e) {
            $isDryRun = (bool)$job['is_dry_run'];
            $failureMessage = $isDryRun ? 'データチェック完了：不備' : $e->getMessage();
            $failedStep = null;
            $failureLogPath = $result['log_path'] ?? '';

            if ($e instanceof BookUploadPipelineException) {
                $failedStep = $e->getStep();
                $failureLogPath = $e->getLogPath() ?? $failureLogPath;
            }

            $payload = [];
            if ($failedStep) {
                $payload['failed_step'] = $failedStep;
            }
            if ($failureLogPath) {
                $payload['log_path'] = $failureLogPath;
            }

            $this->jobModel->UpdateByUuid($jobUuid, [
                'status' => 'failed',
                'message' => $failureMessage,
                'log_path' => $failureLogPath,
                'result_payload' => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            ]);
            throw $e;
        }
    }

    public function getJobHistories(int $limit = 10): array
    {
        return $this->jobModel->GetLatest($limit);
    }

    public function getJobStatus(string $jobUuid): ?array
    {
        return $this->jobModel->GetByUuid($jobUuid);
    }

    private function prepareJobFiles(array $files, string $folderName): array
    {
        $importFolder = self::IMPORT_BASE . '/' . $folderName;
        if (is_dir($importFolder)) {
            FileHelper::DeleteDirectory($importFolder);
        }
        $this->ensureDirectory($importFolder);

        $validationErrors = [];
        $warnings = [];

        foreach ($files as $file) {
            $bookId = $this->extractBookId($file['name']);
            if ($bookId === null) {
                $validationErrors[] = sprintf('%s から書籍ID(TCxxxxx)を判別できません。', $file['name']);
                continue;
            }

            $tmpPath = $this->moveToTemp($file);
            [$fileErrors, $fileWarnings] = $this->validator->validate($tmpPath);

            if (!empty($fileErrors)) {
                foreach ($fileErrors as $error) {
                    $validationErrors[] = sprintf('%s: %s', $file['name'], $error);
                }
                unlink($tmpPath);
                continue;
            }

            if (!empty($fileWarnings)) {
                foreach ($fileWarnings as $warning) {
                    $warnings[] = sprintf('%s: %s', $file['name'], $warning);
                }
            }

            $destination = $importFolder . '/' . $file['name'];
            if (!copy($tmpPath, $destination)) {
                unlink($tmpPath);
                throw new RuntimeException(sprintf('%s のコピーに失敗しました。', $file['name']));
            }
            unlink($tmpPath);
        }

        if (!empty($validationErrors)) {
            throw new BookUploadValidationException($validationErrors);
        }

        return [
            'warnings' => $warnings,
        ];
    }

    private function executePipeline(array $job, array $warnings): array
    {
        $folderName = $job['folder_name'];
        $isDryRun = (bool)$job['is_dry_run'];
        $importFolder = self::IMPORT_BASE . '/' . $folderName;
        $exportFolder = self::EXPORT_BASE . '/' . $folderName;

        $bookSummaries = $this->collectBookSummariesFromImport($importFolder);
        $commandLogs = [];

        $this->jobModel->UpdateByUuid($job['job_uuid'], [
            'status' => 'converting',
            'message' => 'converter.php 実行中',
        ]);

        if (is_dir($exportFolder)) {
            FileHelper::DeleteDirectory($exportFolder);
        }

        $env = ['IMPORT_FOLDER' => $folderName];
        $commandLogs[] = $this->runCommand('converter', 'php converter.php', ROOT_DIR . '/setup_database/iizuna_lms', $env);

        if ($commandLogs[0]['exitCode'] !== 0) {
            $logPath = $this->writeLog($job['job_uuid'], $commandLogs, $warnings);
            throw new BookUploadPipelineException('ExcelのCSV変換に失敗しました。ログを確認してください。', 'converting', $logPath);
        }

        if (!is_dir($exportFolder)) {
            $logPath = $this->writeLog($job['job_uuid'], $commandLogs, $warnings);
            throw new BookUploadPipelineException('CSV出力先が作成されませんでした。', 'converting', $logPath);
        }

        $bookDirs = FileHelper::GetDirectories($exportFolder);
        if (empty($bookDirs)) {
            $logPath = $this->writeLog($job['job_uuid'], $commandLogs, $warnings);
            throw new BookUploadPipelineException('書籍ごとのCSVフォルダが見つかりません。', 'converting', $logPath);
        }

        foreach ($bookDirs as $dir) {
            $bookId = basename($dir);
            $target = self::BATCH_BASE . '/' . $bookId;
            if (is_dir($target)) {
                FileHelper::DeleteDirectory($target);
            }
            $this->mirrorDirectory($dir, $target);
            if (!isset($bookSummaries[$bookId])) {
                $bookSummaries[$bookId] = [
                    'book_id' => $bookId,
                    'file_name' => '(converter出力)',
                ];
            }
            $bookSummaries[$bookId]['csv_path'] = $this->relativePath($target);
        }

        if ($isDryRun) {
            $logPath = $this->writeLog($job['job_uuid'], $commandLogs, $warnings);
            return [
                'job_uuid' => $job['job_uuid'],
                'folder_name' => $folderName,
                'import_path' => $this->relativePath($importFolder),
                'export_path' => $this->relativePath($exportFolder),
                'books' => array_values($bookSummaries),
                'warnings' => $warnings,
                'commands' => $commandLogs,
                'is_dry_run' => true,
                'log_path' => $logPath,
            ];
        }

        foreach (array_keys($bookSummaries) as $bookId) {
            $this->jobModel->UpdateByUuid($job['job_uuid'], [
                'status' => 'importing',
                'message' => sprintf('load_data.sh 実行中 (%s)', $bookId),
            ]);
            $commandLogs[] = $this->runCommand(
                sprintf('load_data %s', $bookId),
                sprintf('./load_data.sh %s', escapeshellarg($bookId)),
                self::BATCH_BASE,
                $this->makeMysqlCliEnv()
            );
            $lastLog = end($commandLogs);
            if ($lastLog['exitCode'] !== 0) {
                $logPath = $this->writeLog($job['job_uuid'], $commandLogs, $warnings);
                $friendly = $this->getFriendlyImportError($bookId, $lastLog);
                throw new BookUploadPipelineException($friendly, 'importing', $logPath);
            }
        }

        $this->jobModel->UpdateByUuid($job['job_uuid'], [
            'status' => 'building_range',
            'message' => 'setup_book_range.php 実行中',
        ]);

        $commandLogs[] = $this->runCommand(
            'setup_book_range',
            'php app/Commands/setup_book_range.php',
            ROOT_DIR
        );

        $lastLog = end($commandLogs);
        if ($lastLog['exitCode'] !== 0) {
            $logPath = $this->writeLog($job['job_uuid'], $commandLogs, $warnings);
            throw new BookUploadPipelineException('範囲情報の再生成に失敗しました。', 'building_range', $logPath);
        }

        $logPath = $this->writeLog($job['job_uuid'], $commandLogs, $warnings);

        return [
            'job_uuid' => $job['job_uuid'],
            'folder_name' => $folderName,
            'import_path' => $this->relativePath($importFolder),
            'export_path' => $this->relativePath($exportFolder),
            'books' => array_values($bookSummaries),
            'warnings' => $warnings,
            'commands' => $commandLogs,
            'is_dry_run' => false,
            'log_path' => $logPath,
        ];
    }

    private function collectBookSummariesFromImport(string $importFolder): array
    {
        $summaries = [];
        $files = glob($importFolder . '/*.xls*');
        foreach ($files as $path) {
            $fileName = basename($path);
            $bookId = $this->extractBookId($fileName);
            if ($bookId) {
                $summaries[$bookId] = [
                    'book_id' => $bookId,
                    'file_name' => $fileName,
                ];
            }
        }
        return $summaries;
    }

    private function dispatchBackgroundJob(string $jobUuid): void
    {
        $phpBinary = PHP_BINARY ?: 'php';
        if (stripos($phpBinary, 'php-fpm') !== false) {
            $phpBinary = 'php';
        }
        $script = ROOT_DIR . '/scripts/book_upload_runner.php';
        $command = sprintf('%s %s %s > /dev/null 2>&1 &',
            escapeshellcmd($phpBinary),
            escapeshellarg($script),
            escapeshellarg($jobUuid)
        );
        exec($command);
    }

    private function normalizeFiles(array $filePayload): array
    {
        if (empty($filePayload) || !isset($filePayload['name'])) {
            return [];
        }

        $files = [];
        if (is_array($filePayload['name'])) {
            foreach ($filePayload['name'] as $index => $name) {
                if (empty($name)) {
                    continue;
                }
                $files[] = [
                    'name' => $name,
                    'tmp_name' => $filePayload['tmp_name'][$index] ?? '',
                    'size' => $filePayload['size'][$index] ?? 0,
                    'error' => $filePayload['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    'type' => $filePayload['type'][$index] ?? '',
                ];
            }
        } else {
            if (!empty($filePayload['name'])) {
                $files[] = $filePayload;
            }
        }

        $validFiles = [];
        foreach ($files as $file) {
            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                continue;
            }
            if (empty($file['tmp_name'])) {
                continue;
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['xls', 'xlsx'])) {
                continue;
            }
            $validFiles[] = $file;
        }

        return $validFiles;
    }

    private function resolveFolderName(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            $value = date('Ymd_His');
        }

        if (!preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
            throw new BookUploadValidationException(['フォルダ名は英数字・ハイフン・アンダースコアのみ利用可能です。']);
        }

        return $this->ensureUniqueFolderName($value);
    }

    private function ensureUniqueFolderName(string $baseName): string
    {
        $candidate = $baseName;
        $suffix = 1;
        while ($this->folderExists($candidate)) {
            $candidate = sprintf('%s-%02d', $baseName, ++$suffix);
        }
        return $candidate;
    }

    private function folderExists(string $folderName): bool
    {
        $paths = [
            self::IMPORT_BASE . '/' . $folderName,
            self::EXPORT_BASE . '/' . $folderName,
        ];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                return true;
            }
        }

        return false;
    }

    private function generateJobUuid(): string
    {
        return sprintf('BOOK-%s-%s', date('YmdHis'), substr(bin2hex(random_bytes(4)), 0, 8));
    }

    private function extractBookId(string $fileName): ?string
    {
        if (preg_match('/TC(\d{4,5})/i', $fileName, $matches)) {
            return 'TC' . $matches[1];
        }
        return null;
    }

    private function moveToTemp(array $file): string
    {
        $this->ensureDirectory(self::TMP_BASE);
        $tmpPath = tempnam(self::TMP_BASE, 'book_');

        if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
            if (!rename($file['tmp_name'], $tmpPath)) {
                throw new RuntimeException(sprintf('%s を一時フォルダに保存できません。', $file['name']));
            }
        }

        return $tmpPath;
    }

    private function mirrorDirectory(string $source, string $destination): void
    {
        $this->ensureDirectory($destination);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $targetPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0775, true);
                }
            } else {
                if (!copy($item, $targetPath)) {
                    throw new RuntimeException(sprintf('CSVのコピーに失敗しました: %s', $targetPath));
                }
            }
        }
    }

    private function runCommand(string $label, string $command, string $workingDir, array $env = []): array
    {
        $envAssignments = [];
        foreach ($env as $key => $value) {
            $envAssignments[] = sprintf('%s=%s', $key, escapeshellarg($value));
        }
        $fullCommand = trim(implode(' ', $envAssignments) . ' ' . $command);

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open('bash -lc ' . escapeshellarg($fullCommand), $descriptorSpec, $pipes, $workingDir);
        if (!is_resource($process)) {
            throw new RuntimeException(sprintf('%s の実行開始に失敗しました。', $label));
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'label' => $label,
            'command' => $fullCommand,
            'stdout' => $stdout,
            'stderr' => $stderr,
            'exitCode' => $exitCode,
        ];
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }

    private function relativePath(string $absolutePath): string
    {
        $normalizedPath = str_replace('\\', '/', $absolutePath);
        $normalizedRoot = str_replace('\\', '/', ROOT_DIR);
        $relative = str_replace($normalizedRoot, '', $normalizedPath);
        return ltrim($relative, '/');
    }

    private function writeLog(string $jobUuid, array $commandLogs, array $warnings): string
    {
        $logRecord = [
            'timestamp' => date('c'),
            'job_uuid' => $jobUuid,
            'warnings' => $warnings,
            'commands' => $commandLogs,
        ];
        file_put_contents(self::LOG_FILE, json_encode($logRecord, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
        return $this->relativePath(self::LOG_FILE);
    }

    private function getFriendlyImportError(string $bookId, array $lastLog): string
    {
        $stderr = strtolower($lastLog['stderr'] ?? '');
        if (strpos($stderr, 'tls/ssl error') !== false) {
            return sprintf('%s のDB接続に失敗しました（証明書エラー）。', $bookId);
        }
        if (strpos($stderr, 'access denied') !== false) {
            return sprintf('%s のDB接続に失敗しました（認証エラー）。', $bookId);
        }
        if (strpos($stderr, 'doesn\'t exist') !== false) {
            return sprintf('%s のDBテーブルが存在しないため投入できませんでした。', $bookId);
        }
        return sprintf('%s のDB投入に失敗しました。', $bookId);
    }

    private function makeMysqlCliEnv(): array
    {
        $env = [];
        if (defined('DB_HOST')) {
            $env['MYSQL_HOST'] = DB_HOST;
        }
        if (defined('DB_NAME')) {
            $env['MYSQL_DATABASE'] = DB_NAME;
        }

        $cnfPath = ROOT_DIR . '/setup_database/batch/mysql-dbaccess.cnf';
        if (is_file($cnfPath)) {
            $env['MYSQL_CNF_FILE'] = $cnfPath;
        }

        return $env;
    }
}
