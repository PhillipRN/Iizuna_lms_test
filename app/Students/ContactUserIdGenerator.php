<?php

namespace IizunaLMS\Students;

use IizunaLMS\Helpers\StringHelper;
use IizunaLMS\Models\StudentModel;

class ContactUserIdGenerator
{
    const CODE_LENGTH = 20;
    const KEY_CODE = 'contact_user_id';
    private $maxLoopNum = 10;

    public function Generate()
    {
        for ($i=0; $i<$this->maxLoopNum; ++$i)
        {
            $code = StringHelper::GetRandomString(self::CODE_LENGTH);

            $record = $this->GetStudentModel()->GetByKeyValue(self::KEY_CODE, $code);

            if (empty($record)) {
                return $code;
            }
        }

        return null;
    }

    // テスト用 ------------------------------------------------------------------------
    private $_StudentModel;

    private function GetStudentModel(): StudentModel
    {
        if ($this->_StudentModel != null) return $this->_StudentModel;
        $this->_StudentModel = new StudentModel();
        return $this->_StudentModel;
    }
}