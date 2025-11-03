<?php

namespace IizunaLMS\Models;

class LmsCodeModel extends ModelBase
{
    function __construct() {
        $this->_tableName = 'lms_code';
    }

    /**
     * @param $lmsCodes
     * @return bool
     */
    public function CheckLmsCodes($lmsCodes): bool
    {
        $result = true;

        if (empty($lmsCodes) || !is_array($lmsCodes)) return false;

        $records = $this->GetsByKeyInValues('lms_code', $lmsCodes);

        foreach ($lmsCodes as $lmsCode)
        {
            $checkLmsCode = false;

            for ($i=0; $i<count($records); ++$i)
            {
                if ($records[$i]['lms_code'] == $lmsCode)
                {
                    $checkLmsCode = true;
                    break;
                }
            }

            // 一つでも正しくないlmsCodeが混じっていた場合はfalse
            if (!$checkLmsCode) {
                $result = false;
                break;
            }
        }

        return $result;
    }
}