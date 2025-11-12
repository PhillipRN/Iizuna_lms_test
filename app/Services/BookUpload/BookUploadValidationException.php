<?php

namespace IizunaLMS\Services\BookUpload;

use RuntimeException;

class BookUploadValidationException extends RuntimeException
{
    private array $errors;

    public function __construct(array $errors)
    {
        parent::__construct('アップロードファイルの検証に失敗しました。');
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
