<?php

namespace IizunaLMS\Students;

use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\IStudentAutoLoginTokenModel;
use IizunaLMS\Models\StudentAutoLoginTokenModel;
use IizunaLMS\Models\StudentAutoLoginTokenModelDynamoDB;

class AutoLoginTokenGenerator
{
    const KEY_LENGTH = 32;
    private $maxLoopNum = 10;

    public function Generate()
    {
        for ($i=0; $i<$this->maxLoopNum; ++$i)
        {
            $autoLoginToken = StringHelper::GetRandomStringIncludeUpperLetterAndUnderScore(self::KEY_LENGTH);
            $hashedAutoLoginToken = StringHelper::GetHashedString($autoLoginToken);
            $record = $this->GetStudentAutoLoginTokenModel()->GetByAutoLoginToken($hashedAutoLoginToken);

            if (empty($record)) {
                return $autoLoginToken;
            }
        }

        return null;
    }

    private $_StudentAutoLoginTokenModel;

    private function GetStudentAutoLoginTokenModel(): IStudentAutoLoginTokenModel
    {
        if ($this->_StudentAutoLoginTokenModel != null) return $this->_StudentAutoLoginTokenModel;
        $this->_StudentAutoLoginTokenModel =
            (USE_DYNAMO_DB) ? new StudentAutoLoginTokenModelDynamoDB()
                            : new StudentAutoLoginTokenModel();
        return $this->_StudentAutoLoginTokenModel;
    }
}