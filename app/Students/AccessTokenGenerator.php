<?php

namespace IizunaLMS\Students;

use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\IStudentAccessTokenModel;
use IizunaLMS\Models\StudentAccessTokenModel;
use IizunaLMS\Models\StudentAccessTokenModelDynamoDB;

class AccessTokenGenerator
{
    const KEY_LENGTH = 32;
    private $maxLoopNum = 10;

    public function Generate()
    {
        for ($i=0; $i<$this->maxLoopNum; ++$i)
        {
            $accessToken = StringHelper::GetRandomStringIncludeUpperLetterAndSymbol(self::KEY_LENGTH);

            $hashedAccessToken = StringHelper::GetHashedString($accessToken);
            $record = $this->GetStudentAccessTokenModel()->GetByAccessToken($hashedAccessToken);

            if (empty($record)) {
                return $accessToken;
            }
        }

        return null;
    }

    // テスト用 ------------------------------------------------------------------------
    private $_StudentAccessTokenModel;

    private function GetStudentAccessTokenModel(): IStudentAccessTokenModel
    {
        if ($this->_StudentAccessTokenModel != null) return $this->_StudentAccessTokenModel;
        $this->_StudentAccessTokenModel =
            (USE_DYNAMO_DB) ? new StudentAccessTokenModelDynamoDB()
                            : new StudentAccessTokenModel();
        return $this->_StudentAccessTokenModel;
    }
}