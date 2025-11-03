<?php

namespace IizunaLMS\Schools;

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Models\LmsCodeAmountModel;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Models\SchoolModel;

class SchoolRegister
{
    /**
     * @param $params
     * @return array|false
     */
    public function Add($params)
    {
        // LMSコード生成
        $lmsCode = (new LmsCodeGenerator())->Generate();

        $resultLmsCode = (new LmsCodeModel())->Add(new LmsCode([
            'lms_code' => $lmsCode
        ]));

        if ($resultLmsCode) {
            $lmsCodeId = PDOHelper::GetLastInsertId(PDOHelper::GetPDO());

            $params['lms_code_id'] = $lmsCodeId;

            $result = (new SchoolModel())->Add(new School($params));

            $resultLmsCodeAmount = (new LmsCodeAmountModel())->Add(new LmsCodeAmount($lmsCodeId));

            if ($result && $resultLmsCodeAmount) {
                return [
                    'school_id' => PDOHelper::GetLastInsertId(PDOHelper::GetPDO())
                ];
            }
        }

        return false;
    }

    /**
     * @param $params
     * @return bool
     */
    public function Update($params)
    {
        return (new SchoolModel())->Update([
            'id' => $params['id'],
            'name' => $params['school_name'],
            'zip' => $params['school_zip'],
            'pref' => $params['school_pref'],
            'address' => $params['school_address'],
            'phone' => $params['school_phone'],
            'is_paid' => $params['is_paid'],
            'is_juku' => empty($params['is_juku']) ? 0 : 1 // チェックボックスの値はチェックをしないと飛んでこないため、値がない場合は0指定
        ]);
    }
}