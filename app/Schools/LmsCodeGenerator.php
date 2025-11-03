<?php

namespace IizunaLMS\Schools;

use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\LmsCodeModel;

class LmsCodeGenerator
{
    const LMS_CODE_LENGTH = 13;
    const KEY_LMS_CODE = 'lms_code';
    private $maxLoopNum = 10;

    public function Generate()
    {
        for ($i=0; $i<$this->maxLoopNum; ++$i)
        {
            $lmsCode = StringHelper::GetRandomString(self::LMS_CODE_LENGTH);

            $record = $this->GetLmsCodeModel()->GetByKeyValue(self::KEY_LMS_CODE, $lmsCode);

            if (empty($record)) {
                return $lmsCode;
            }
        }

        return null;
    }

    // テスト用 ------------------------------------------------------------------------
    private $_LmsCodeModel;

    private function GetLmsCodeModel(): LmsCodeModel
    {
        if ($this->_LmsCodeModel != null) return $this->_LmsCodeModel;
        $this->_LmsCodeModel = new LmsCodeModel();
        return $this->_LmsCodeModel;
    }
}