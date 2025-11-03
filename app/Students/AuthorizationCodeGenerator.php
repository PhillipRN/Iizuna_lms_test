<?php

namespace IizunaLMS\Students;

use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\StudentAuthorizationKeyModel;

class AuthorizationCodeGenerator
{
    const AUTHORIZATION_KEY_LENGTH = 13;
    const KEY_AUTHORIZATION_KEY = 'authorization_key';
    private $maxLoopNum = 10;

    public function Generate()
    {
        for ($i=0; $i<$this->maxLoopNum; ++$i)
        {
            $authorizationKey = StringHelper::GetRandomString(self::AUTHORIZATION_KEY_LENGTH);

            $record = $this->GetStudentAuthorizationKeyModel()->GetByKeyValue(self::KEY_AUTHORIZATION_KEY, $authorizationKey);

            if (empty($record)) {
                return $authorizationKey;
            }
        }

        return null;
    }

    // テスト用 ------------------------------------------------------------------------
    private $_StudentAuthorizationKeyModel;

    private function GetStudentAuthorizationKeyModel(): StudentAuthorizationKeyModel
    {
        if ($this->_StudentAuthorizationKeyModel != null) return $this->_StudentAuthorizationKeyModel;
        $this->_StudentAuthorizationKeyModel = new StudentAuthorizationKeyModel();
        return $this->_StudentAuthorizationKeyModel;
    }
}