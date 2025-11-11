<?php

namespace IizunaLMS\Services\BookUpload;

use RuntimeException;

class BookUploadPipelineException extends RuntimeException
{
    private string $step;
    private ?string $logPath;

    public function __construct(string $message, string $step, ?string $logPath = null)
    {
        parent::__construct($message);
        $this->step = $step;
        $this->logPath = $logPath;
    }

    public function getStep(): string
    {
        return $this->step;
    }

    public function getLogPath(): ?string
    {
        return $this->logPath;
    }
}
