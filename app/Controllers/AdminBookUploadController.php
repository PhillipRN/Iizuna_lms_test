<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Services\BookUpload\BookUploadService;

class AdminBookUploadController
{
    private BookUploadService $service;

    public function __construct()
    {
        $this->service = new BookUploadService();
    }

    /**
     * @param array $filePayload
     * @param array $params
     * @param int|null $userId
     * @return array
     */
    public function QueueUpload(array $filePayload, array $params, ?int $userId = null): array
    {
        return $this->service->queueUpload($filePayload, $params, $userId);
    }

    public function RunJob(string $jobUuid): void
    {
        $this->service->processJob($jobUuid);
    }

    /**
     * @param int $limit
     * @return array
     */
    public function GetLatestJobs(int $limit = 10): array
    {
        return $this->service->getJobHistories($limit);
    }

    /**
     * @param string $jobUuid
     * @return array|null
     */
    public function GetJobStatus(string $jobUuid): ?array
    {
        return $this->service->getJobStatus($jobUuid);
    }
}
