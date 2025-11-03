<?php

namespace IizunaLMS\Students;

use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\StudentRefreshTokenModel;

class RefreshTokenGenerator
{
    const KEY_LENGTH = 32;
    const KEY_REFRESH_TOKEN = 'refresh_token';
    private $maxLoopNum = 10;

    public function Generate()
    {
        for ($i=0; $i<$this->maxLoopNum; ++$i)
        {
            $refreshToken = StringHelper::GetRandomStringIncludeUpperLetterAndSymbol(self::KEY_LENGTH);

            $hashedRefreshToken = StringHelper::GetHashedString($refreshToken);
            $record = $this->GetStudentRefreshTokenModel()->GetByKeyValue(self::KEY_REFRESH_TOKEN, $hashedRefreshToken);

            if (empty($record)) {
                return $refreshToken;
            }
        }

        return null;
    }

    // テスト用 ------------------------------------------------------------------------
    private $_StudentRefreshTokenModel;

    private function GetStudentRefreshTokenModel(): StudentRefreshTokenModel
    {
        if ($this->_StudentRefreshTokenModel != null) return $this->_StudentRefreshTokenModel;
        $this->_StudentRefreshTokenModel = new StudentRefreshTokenModel();
        return $this->_StudentRefreshTokenModel;
    }
}