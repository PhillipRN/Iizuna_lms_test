<?php

namespace IizunaLMS\Students;

use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\IStudentLoginTokenModel;
use IizunaLMS\Models\StudentLoginTokenModel;
use IizunaLMS\Models\StudentLoginTokenModelDynamoDB;

class LoginTokenGenerator
{
    const KEY_LENGTH = 32;
    const KEY_LOGIN_TOKEN = 'login_token';
    private $maxLoopNum = 10;

    public function Generate()
    {
        for ($i=0; $i<$this->maxLoopNum; ++$i)
        {
            $loginToken = StringHelper::GetRandomStringIncludeUpperLetterAndUnderScore(self::KEY_LENGTH);

            $hashedLoginToken = StringHelper::GetHashedString($loginToken);
            $record = $this->GetStudentLoginTokenModel()->GetByLoginToken($hashedLoginToken);

            if (empty($record)) {
                return $loginToken;
            }
        }

        return null;
    }

    // テスト用 ------------------------------------------------------------------------
    private $_StudentLoginTokenModel;

    private function GetStudentLoginTokenModel(): IStudentLoginTokenModel
    {
        if ($this->_StudentLoginTokenModel != null) return $this->_StudentLoginTokenModel;
        $this->_StudentLoginTokenModel =
            (USE_DYNAMO_DB) ? new StudentLoginTokenModelDynamoDB()
                            : new StudentLoginTokenModel();
        return $this->_StudentLoginTokenModel;
    }
}