<?php

namespace IizunaLMS\LmsTickets;

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\Data\LmsTicketGroupData;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Models\LmsTicketApplicationModel;
use IizunaLMS\Models\LmsTicketGroupModel;
use IizunaLMS\Models\LmsTicketGroupUseCountModel;
use IizunaLMS\Models\LmsTicketGroupViewModel;
use IizunaLMS\Models\LmsTicketModel;
use IizunaLMS\Models\OnigiriLearningRangeModel;
use IizunaLMS\Requests\RequestParamLmsTicketGroup;
use IizunaLMS\Schools\LmsCode;
use IizunaLMS\Schools\LmsCodeGenerator;
use IizunaLMS\Schools\OnigiriLearningRange;

class LmsTicketGroupRegister
{
    public function CheckAndCreateLmsCodeAndAdd(RequestParamLmsTicketGroup $params)
    {
        $errorCodes = $this->CheckValidateParameters($params);
        if (!empty($errorCodes)) return $errorCodes[0];

        // LMSコード生成
        $lmsCode = (new LmsCodeGenerator())->Generate();

        $resultLmsCode = (new LmsCodeModel())->Add(new LmsCode([
            'lms_code' => $lmsCode,
            'type' => LmsCode::TYPE_LMS_TICKET
        ]));

        if (!$resultLmsCode) return Error::ERROR_TEACHER_LMS_TICKET_GROUP_ADD_FAILED;

        // LMSチケットグループを登録
        $lmsCodeId = PDOHelper::GetLastInsertId(PDOHelper::GetPDO());

        $result = $this->AddLmsTicketGroup($lmsCodeId, $params);
        if (!$result) return Error::ERROR_TEACHER_LMS_TICKET_GROUP_ADD_FAILED;

        // カウントアップレコードを追加
        $lmsTicketGroupId = PDOHelper::GetLastInsertId(PDOHelper::GetPDO());

        $result = $this->AddCountUpRecord($lmsTicketGroupId);
        if (!$result) return Error::ERROR_TEACHER_LMS_TICKET_GROUP_ADD_FAILED;

        // おにぎりの場合は、学習範囲を追加する
        $lmsTicket = (new LmsTicketModel)->GetById($params->lms_ticket_id);
        if ($lmsTicket['title_no'] == LmsTicket::TITLE_NO_ONIGIRI) {
            $result = $this->AddDefaultOnigiriLearningRange($lmsCodeId);
        }

        return ($result) ? Error::ERROR_NONE : Error::ERROR_TEACHER_LMS_TICKET_GROUP_ADD_FAILED;
    }

    /**
     * @param RequestParamLmsTicketGroup $params
     * @return array
     */
    public function CheckValidateParameters(RequestParamLmsTicketGroup $params): array
    {
        $errors = [];

        if (empty($params->lms_ticket_id)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_GROUP_INVALID_PARAMETER;

        $lmsTicket = (new LmsTicketLoader())->GetTicket($params->lms_ticket_id);

        if (empty($lmsTicket)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_GROUP_INVALID_PARAMETER;

        if (empty($params->name)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_GROUP_EMPTY_NAME;

        if (empty($params->quantity)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_GROUP_INVALID_QUANTITY;
        else if (!is_numeric($params->quantity)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_GROUP_INVALID_QUANTITY;
        else if ($params->quantity < 1) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_GROUP_INVALID_QUANTITY;

        // チケットの残数が足りているか確認する
        if (!empty($lmsTicket))
        {
            $groups = (new LmsTicketLoader)->GetTicketGroups($params->lms_ticket_id);
            $groupQuantityCount = 0;

            foreach ($groups as $group) $groupQuantityCount += $group['quantity'];

            if ($lmsTicket['quantity'] < $groupQuantityCount + $params->quantity) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_GROUP_QUANTITY_NOT_ENOUGH;
        }

        return $errors;
    }

    /**
     * @param $lmsTicketGroupId
     * @param $record
     * @param RequestParamLmsTicketGroup $params
     * @return array
     */
    public function CheckValidateUpdateParameters($lmsTicketGroupId, $record, RequestParamLmsTicketGroup $params): array
    {
        $errors = [];

        if (empty($params->lms_ticket_id)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_GROUP_INVALID_PARAMETER;

        $lmsTicket = (new LmsTicketLoader())->GetTicket($params->lms_ticket_id);

        if (empty($lmsTicket)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_GROUP_INVALID_PARAMETER;

        if (empty($params->name)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_GROUP_EMPTY_NAME;

        if (empty($params->quantity)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_GROUP_INVALID_QUANTITY;
        else if (!is_numeric($params->quantity)) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_GROUP_INVALID_QUANTITY;
        else if ($params->quantity < 1) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_GROUP_INVALID_QUANTITY;

        // チケットの残数が足りているか確認する
        if (!empty($lmsTicket))
        {
            $groups = (new LmsTicketLoader)->GetTicketGroups($params->lms_ticket_id);
            $groupQuantityCount = 0;

            // 自身以外のものをカウントアップする
            foreach ($groups as $group)
            {
                if ($group['id'] == $lmsTicketGroupId) continue;
                $groupQuantityCount += $group['quantity'];
            }

            if ($lmsTicket['quantity'] < $groupQuantityCount + $params->quantity) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_GROUP_QUANTITY_NOT_ENOUGH;
        }

        if ($record['use_count'] > $params->quantity) $errors[] = Error::ERROR_TEACHER_LMS_TICKET_GROUP_USE_COUNT_OVER;

        return $errors;
    }

    /**
     * @param RequestParamLmsTicketGroup $params
     * @return bool
     */
    public function AddLmsTicketGroup($lmsCodeId, RequestParamLmsTicketGroup $params): bool
    {
        $data = $params->ToArray();
        $data['lms_code_id'] = $lmsCodeId;

        $lmsTicketGroup = new LmsTicketGroupData($data);

        return (new LmsTicketGroupModel())->Add($lmsTicketGroup);
    }

    /**
     * @param $lmsTicketGroupId
     * @param $status
     * @return bool
     */
    public function UpdateStatus($lmsTicketGroupId, $status): bool
    {
        $data = [
            'id' => $lmsTicketGroupId,
            'status' => $status,
        ];

        return (new LmsTicketGroupModel())->Update($data);
    }

    /**
     * @param $lmsTicketGroupId
     * @return bool
     */
    private function AddCountUpRecord($lmsTicketGroupId): bool
    {
        $data = [
            'lms_ticket_group_id' => $lmsTicketGroupId
        ];

        return (new LmsTicketGroupUseCountModel())->Add($data);
    }

    /**
     * @param $lmsTicketGroupId
     * @return bool
     */
    public function CountUp($lmsTicketGroupId): bool
    {
        return (new LmsTicketGroupUseCountModel())->CountUp($lmsTicketGroupId);
    }

    /**
     * @param $lmsCode
     * @return void
     */
    public function TryCountDownByLmsCode($lmsCode)
    {
        // LmsTicketGroup のレコードを取得する
        $record = (new LmsTicketGroupViewModel())->GetByKeyValue('lms_code', $lmsCode);
        if (empty($record)) return;

        $lmsTicketId = $record['lms_ticket_id'];
        $lmsTicketGroupId = $record['id'];
        $useCount = $record['use_count'];

        // あり得ないが1未満の場合はスルー
        if ($useCount < 1) return;

        $applicationRecord = (new LmsTicketApplicationModel())->GetOldestApplicationRecord($lmsTicketId);
        if (empty($applicationRecord)) return;

        // 更新日 = 承認日として扱う
        $applicationDate = $applicationRecord['update_date'];

        $this->CheckLimitAndCountDown($lmsTicketGroupId, $applicationDate);
    }

    /**
     * @param $lmsTicketGroupId
     * @param $applicationDate
     * @return bool
     * @throws \Exception
     */
    private function CheckLimitAndCountDown($lmsTicketGroupId, $applicationDate)
    {
        // 1ヶ月後が期限
        $limitDate = (new \DateTime($applicationDate))->modify('+1 months');

        // 期限を超えている場合は処理しない
        if ($limitDate < new \DateTime()) return false;

        return (new LmsTicketGroupUseCountModel())->CountDown($lmsTicketGroupId);
    }

    /**
     * @param $lmsCodeId
     * @return bool
     */
    public function AddDefaultOnigiriLearningRange($lmsCodeId): bool
    {
        $registerArray = [];
        $registerArray[] = new OnigiriLearningRange($lmsCodeId, 1, 'high_school', 0, 1);
        $registerArray[] = new OnigiriLearningRange($lmsCodeId, 2, 'high_school', 0, 2);
        $registerArray[] = new OnigiriLearningRange($lmsCodeId, 3, 'high_school', 0, 3);
        return (new OnigiriLearningRangeModel())->MultipleAdd($registerArray);
    }
}