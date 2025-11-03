<?php

namespace IizunaLMS\Students;

use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Models\LmsTicketGroupViewModel;
use IizunaLMS\Models\SchoolGroupViewModel;
use IizunaLMS\Models\SchoolViewModel;
use IizunaLMS\Models\StudentLmsCodeViewModel;
use IizunaLMS\Models\StudentModel;
use IizunaLMS\Schools\LmsCodeApplication;

class StudentDataChecker
{
    /**
     * @param $string
     * @return bool
     */
    public function CheckPassword($string)
    {
        return preg_match('/^[a-zA-Z_\-0-9]{8,}$/', $string) &&
            preg_match('/[a-zA-Z]/', $string) &&
            preg_match('/[0-9]/', $string);
    }

    /**
     * @param $string
     * @return false|int
     */
    public function CheckLoginId($string)
    {
        return preg_match('/^[a-zA-Z_\-0-9]{4,}$/', $string);
    }

    /**
     * @param $loginId
     * @param null $studentId
     * @return bool
     */
    public function IsRegisteredOtherStudentLoginId($loginId, $studentId=null)
    {
        $record = (new StudentModel())->GetByKeyValue('login_id', $loginId);

        // レコードが存在しない場合は false を返す
        if (empty($record)) return false;

        // 自分の id だった場合は false を返す
        if ($studentId && $record['id'] == $studentId) return false;

        // レコードが存在し、自分の id でもないので登録済みの id
        return true;
    }

    /**
     * @param $lmsCode
     * @return bool
     */
    public function IsLmsCode($lmsCode)
    {
        return (new LmsCodeModel())->CheckLmsCodes([$lmsCode]);
    }

    /**
     * 使うことが可能なLMSコードかチェックする
     * @param $lmsCode
     * @return bool
     */
    public function IsValidLmsCode($lmsCode): bool
    {
        $schoolRecord = (new SchoolViewModel())->GetByKeyValue('lms_code', $lmsCode);

        // 学校の場合は制限はないため見つかれば OK
        if (!empty($schoolRecord)) return true;

        $schoolGroupRecord = (new SchoolGroupViewModel())->GetByKeyValue('lms_code', $lmsCode);
        return $this->IsValidSchoolGroupLmsCode($schoolGroupRecord);;
    }

    /**
     * @param $record
     * @return bool
     */
    private function IsValidSchoolGroupLmsCode($record): bool
    {
        // 無効なレコードの場合
        if (empty($record) || $record['is_enable'] == 0) return false;

        // 有料コードじゃない場合は抜ける
        if ($record['is_paid'] == 0) return true;

        // 新規申請中はNG
        if ($record['paid_application_status'] == LmsCodeApplication::STATUS_APPLICATION_WAITING_NEW_APPROVAL)
        {
            return false;
        }

        // NOTE iizunaLMSの生徒登録時にはLMSコードの使用回数をチェックしない
//        if ($record['use_count'] >= $record['available_total'])
//        {
//            return false;
//        }

        return true;
    }

    /**
     * 使うことが可能なLMSチケットのコードかチェックする
     * @param $lmsCode
     * @return bool
     */
    public function IsValidLmsTicketForOnigiri($lmsCode): bool
    {
        $record = (new LmsTicketGroupViewModel())->GetByKeyValue('lms_code', $lmsCode);

        if (empty($record)) return false;

        // Onigiri のチケットでない場合はエラー
        if ($record['title_no'] != LmsTicket::TITLE_NO_ONIGIRI) return false;

        // チケットが有効でない場合はエラー
        if ($record['lms_ticket_status'] != LmsTicket::STATUS_ENABLE) return false;

        // チケット数が残っていない場合はエラー
        if (intval($record['quantity']) <= intval($record['use_count'])) return false;

        $expireDate = (new \DateTime("{$record['expire_year']}/{$record['expire_month']}/01"))
            ->modify('+1 months');

        // 有効期限が切れている場合はエラー
        if (new \DateTime() >= $expireDate) return false;

        return true;
    }

    /**
     * @param $studentId
     * @param $lmsCode
     * @return bool
     */
    public function AlreadyRegisteredLmsCode($studentId, $lmsCode)
    {
        $record = (new StudentLmsCodeViewModel())->GetsByKeyValues(
            ['student_id', 'lms_code'],
            [$studentId, $lmsCode]);

        return !empty($record);
    }
}