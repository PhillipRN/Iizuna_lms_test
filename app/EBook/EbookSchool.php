<?php

namespace IizunaLMS\EBook;

use IizunaLMS\EBook\Data\EbookSchoolData;
use IizunaLMS\EBook\Models\EbookSchoolModel;
use IizunaLMS\EBook\Models\EbookSchoolViewModel;
use IizunaLMS\EBook\Requests\RequestParamEbookCodeCountUp;
use IizunaLMS\EBook\Requests\RequestParamEbookSchool;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\LmsTickets\LmsTicketGroupRegister;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Models\LmsTicketGroupViewModel;
use IizunaLMS\Schools\LmsCode;

class EbookSchool
{
    const KEY_LMS_CODE = 'lms_code';
    const KEY_SCHOOL_ID = 'school_id';

    public array $enableTitleNos = [
        10052,  // 総合英語 Evergreen
        10086,  // 総合英語be 4th Edition
        10093,  // 総合英語Harmony
    ];

    public array $enableSampleTicketTitleNos = [
        99052, // EverGreenサンプル
        99086, // beサンプル
        99093, // harmonyサンプル
    ];

    function __construct() {
        if (DEBUG_MODE)
        {
            $this->enableSampleTicketTitleNos[] = 10052; // DEBUG evergreen 確認用。不要になったら削除。
            $this->enableSampleTicketTitleNos[] = 10093; // DEBUG harmony 確認用。不要になったら削除。
        }
    }

    /**
     * @param RequestParamEbookSchool $params
     * @return array
     */
    public function GetBookStatuses(RequestParamEbookSchool $params): array
    {
        if (!$params->IsValid())
            return Error::GetErrorResultData(Error::ERROR_EBOOK_SCHOOL_INVALID_PARAMETER);

        $lmsCodes = $params->lmsCodes;

        // sampleから始まるものは取り除いてチェック
        $checkLmsCodes = array_filter($lmsCodes, function($lmsCode) {
            return strpos($lmsCode, 'sample') === false;
        });

        // key を連番にする
        $checkLmsCodes = array_values($checkLmsCodes);

        if (!empty($checkLmsCodes))
        {
            if (!(new LmsCodeModel())->CheckLmsCodes($checkLmsCodes))
                return Error::GetErrorResultData(Error::ERROR_EBOOK_SCHOOL_INVALID_LMS_CODE);
        }

        // sampleから始まるものを取り出す
        $sampleTicketCodes = [];
        $sampleTicketCodes = array_filter($lmsCodes, function($lmsCode) {
            return strpos($lmsCode, 'sample') !== false;
        });

        // key を連番にする
        $sampleTicketCodes = array_values($sampleTicketCodes);

        if (!empty($sampleTicketCodes))
        {
            if (!$this->_CheckSampleTicketCodes ($sampleTicketCodes))
                return Error::GetErrorResultData(Error::ERROR_EBOOK_SCHOOL_INVALID_LMS_CODE);
        }

        $lmsCodeRecords = (new LmsCodeModel())->GetsByKeyInValues('lms_code', $params->lmsCodes);

        $schoolCodes = [];
        $lmsTicketCodeIds = [];

        foreach ($lmsCodeRecords as $lmsCodeRecord)
        {
            if ($lmsCodeRecord['type'] == LmsCode::TYPE_SCHOOL)
                $schoolCodes[] = $lmsCodeRecord['lms_code'];
            else if ($lmsCodeRecord['type'] == LmsCode::TYPE_LMS_TICKET)
                $lmsTicketCodeIds[] = $lmsCodeRecord['id'];
        }

        $records = [];

        if (!empty($schoolCodes))
        {
            $records = (new EbookSchoolViewModel())->GetsByKeyInValues(self::KEY_LMS_CODE, $schoolCodes);
        }

        if (!empty($lmsTicketCodeIds))
        {
            $records = array_merge($records, $this->_GetLmsTicketStatuses($lmsTicketCodeIds));
        }

        if (!empty($sampleTicketCodes))
        {
            $records = array_merge($records, $this->_GetSampleTicketStatuses($sampleTicketCodes));
        }

        return $this->_ConvertBookStatusesRecords($records);
    }

    /**
     * @param $schoolId
     * @return array
     */
    public function GetBookStatusesBySchoolId($schoolId): array
    {
        $records = (new EbookSchoolViewModel())->GetsByKeyValue(self::KEY_SCHOOL_ID, $schoolId);

        return $this->_ConvertBookStatusesRecords($records);
    }

    /**
     * @param $lmsTicketCodeIds
     * @return array
     */
    private function _GetLmsTicketStatuses($lmsTicketCodeIds)
    {
        $result = [];

        $records = (new LmsTicketGroupViewModel())->GetsByKeyInValues('lms_code_id', $lmsTicketCodeIds);

        foreach ($records as $record)
        {
            // 利用可能なタイトルでない場合はスキップ
            $titleNo = $record['title_no'];
            if (!in_array($titleNo, $this->enableTitleNos)) continue;

            // チケットが有効でない場合はスキップ
            if ($record['lms_ticket_status'] != LmsTicket::STATUS_ENABLE) continue;

            // チケット数が残っていない
            $isOutOfStock = ($record['quantity'] <= $record['use_count']);

            $expireDate = (new \DateTime("{$record['expire_year']}/{$record['expire_month']}/01"))
                ->modify('+1 months');

            // 有効期限が切れている
            $isExpired = (new \DateTime() >= $expireDate);

            $result[] = [
                'title_no' => $titleNo,
                'is_buy' => $isExpired ? 0 : 1,
                'is_display' => $isOutOfStock ? 0 : ($isExpired ? 0 : 1)
            ];
        }

        return $result;
    }

    /**
     * @param $sampleTicketCodes
     * @return bool
     */
    private function _CheckSampleTicketCodes($sampleTicketCodes): bool
    {

        foreach ($sampleTicketCodes as $sampleTicketCode)
        {
            $titleNo = str_replace('sample', '', $sampleTicketCode);

            if (!is_numeric($titleNo)) return false;

            $titleNo = (int)$titleNo;
            if (!in_array($titleNo, $this->enableSampleTicketTitleNos)) return false;
        }

        return true;
    }

    /**
     * @param $sampleTicketCodes
     * @return array
     */
    private function _GetSampleTicketStatuses($sampleTicketCodes)
    {
        $result = [];

        foreach ($sampleTicketCodes as $sampleTicketCode)
        {
            $titleNo = str_replace('sample', '', $sampleTicketCode);

            if (!is_numeric($titleNo)) continue;

            $titleNo = (int)$titleNo;
            if (!in_array($titleNo, $this->enableSampleTicketTitleNos)) continue;

            $result[] = [
                'title_no' => $titleNo,
                'is_buy' => 1,
                'is_display' => 1
            ];
        }

        return $result;
    }

    /**
     * @param $records
     * @return array
     */
    private function _ConvertBookStatusesRecords($records)
    {
        $result = [];

        // 値を返却用の配列に詰め込む
        foreach ($records as $record)
        {
            $titleNo = $record['title_no'];
            $isBuy = $record['is_buy'];
            $isDisplay = $record['is_display'];

            if (isset($result[ $titleNo ]))
            {
                if ($result[ $titleNo ]['is_buy'] == 1)     $isBuy = 1;
                if ($result[ $titleNo ]['is_display'] == 1) $isDisplay = 1;
            }

            $result[ $titleNo ] = [
                'title_no' => $titleNo,
                'is_buy' => $isBuy,
                'is_display' => $isDisplay
            ];
        }

        // レコードがないものを埋める
        foreach ($this->enableTitleNos as $enableTitleNo)
        {
            if (isset($result[ $enableTitleNo ])) continue;

            $result[ $enableTitleNo ] = [
                'title_no' => $enableTitleNo,
                'is_buy' => 0,
                'is_display' => 0
            ];
        }

        return [
            'result' => $result
        ];
    }

    /**
     * @param $schoolId
     * @param $params
     * @return bool
     */
    public function DeleteAndInsertRecords($schoolId, $params)
    {
        $EbookSchoolModel = new EbookSchoolModel();

        $EbookSchoolModel->DeleteBySchoolId($schoolId);

        $records = [];

        foreach ($params['ebook'] as $titleNo => $ebook)
        {
            $records[] = new EbookSchoolData($schoolId, $titleNo, $ebook);
        }

        return $EbookSchoolModel->MultipleAdd($records);
    }

    /**
     * @param RequestParamEbookCodeCountUp $params
     * @return string[]
     */
    public function CodeCountUp(RequestParamEbookCodeCountUp $params)
    {
        $lmsCodeRecords = (new LmsCodeModel())->GetsByKeyInValues('lms_code', $params->lmsCodes);

        // LMSチケットのみ処理するため、LMSチケットのIDを取得
        $lmsTicketCodeIds = [];
        foreach ($lmsCodeRecords as $lmsCodeRecord)
        {
            if ($lmsCodeRecord['type'] == LmsCode::TYPE_LMS_TICKET)
                $lmsTicketCodeIds[] = $lmsCodeRecord['id'];
        }

        if (empty($lmsTicketCodeIds))
        {
            return [
                'result' => "OK"
            ];
        }

        $records = (new LmsTicketGroupViewModel())->GetsByKeyInValues('lms_code_id', $lmsTicketCodeIds);

        PDOHelper::GetPDO()->beginTransaction();
        $result = true;

        $LmsTicketGroupRegister = new LmsTicketGroupRegister();
        foreach ($records as $record)
        {
            $lmsTicketGroupId = $record['id'];
            $result = $LmsTicketGroupRegister->CountUp($lmsTicketGroupId);

            // 1つでも失敗したら終了
            if (!$result) break;
        }

        if ($result)
        {
            PDOHelper::GetPDO()->commit();
        }
        else
        {
            PDOHelper::GetPDO()->rollBack();
        }

        return [
            'result' => "OK"
        ];
    }
}