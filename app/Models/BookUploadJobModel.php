<?php

namespace IizunaLMS\Models;

use IizunaLMS\Helpers\PDOHelper;

class BookUploadJobModel extends ModelBase
{
    public function __construct()
    {
        $this->_tableName = 'book_upload_jobs';
    }

    public function GetByUuid(string $uuid): ?array
    {
        $pdo = $this->GetPDO();
        $sql = "SELECT * FROM {$this->_tableName} WHERE job_uuid = :job_uuid LIMIT 1";
        $sth = $pdo->prepare($sql);
        $sth->bindValue(':job_uuid', $uuid);
        PDOHelper::ExecuteWithTry($sth);
        $row = $sth->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function UpdateByUuid(string $uuid, array $data): bool
    {
        if (empty($data)) {
            return true;
        }

        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setSql = implode(', ', $setParts);
        $sql = "UPDATE {$this->_tableName} SET {$setSql}, updated_at = NOW() WHERE job_uuid = :job_uuid";

        $pdo = $this->GetPDO();
        $sth = $pdo->prepare($sql);
        foreach ($data as $key => $value) {
            $sth->bindValue(":{$key}", $value);
        }
        $sth->bindValue(':job_uuid', $uuid);

        return PDOHelper::ExecuteWithTry($sth);
    }

    public function GetLatest(int $limit = 10): array
    {
        $pdo = $this->GetPDO();
        $sql = "SELECT job_uuid, folder_name, status, file_count, is_dry_run, memo, message, warnings_payload, result_payload, created_at, updated_at FROM {$this->_tableName} ORDER BY id DESC LIMIT :limit";
        $sth = $pdo->prepare($sql);
        $sth->bindValue(':limit', $limit, \PDO::PARAM_INT);
        PDOHelper::ExecuteWithTry($sth);
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}
