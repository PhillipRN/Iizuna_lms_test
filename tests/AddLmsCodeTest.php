<?php

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\LmsTickets\LmsTicketApplication;
use IizunaLMS\LmsTickets\LmsTicketApplicationRegister;
use IizunaLMS\LmsTickets\LmsTicketGroupRegister;
use IizunaLMS\LmsTickets\LmsTicketRegister;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Models\LmsTicketGroupUseCountModel;
use IizunaLMS\Models\LmsTicketGroupViewModel;
use IizunaLMS\Models\SchoolGroupViewModel;
use IizunaLMS\Requests\RequestParamLmsTicketApplication;
use IizunaLMS\Requests\RequestParamLmsTicketGroup;
use IizunaLMS\Requests\RequestParamStudentAddLmsCode;
use IizunaLMS\Schools\LmsCode;
use IizunaLMS\Schools\LmsCodeApplication;
use IizunaLMS\Schools\LmsCodeGenerator;
use IizunaLMS\Students\AddLmsCode;

require_once (__DIR__ . '/../app/bootstrap.php');
require_once ('TestBase.php');


class AddLmsCodeTest extends TestBase
{
    public static $validLmsCode = 'qh3k4bgzwuzio';
    public static $validLmsCodeForOnigiri;

    public static function setUpBeforeClass(): void
    {
        $_POST = null;
        PDOHelper::GetPDO()->beginTransaction();

        // 無理やり利用許可タイトルにOnigiri追加
        if (!in_array(LmsTicket::TITLE_NO_ONIGIRI, LmsTicket::$AvailableTitleNos))
        {
            LmsTicket::$AvailableTitleNos[] = LmsTicket::TITLE_NO_ONIGIRI;
        }

        // テスト用に OnigiriのLMSチケットを作成する
        $quantity = 10;
        $_POST = [
            'title_no' => LmsTicket::TITLE_NO_ONIGIRI,
            'expire_year' => 2025,
            'expire_month' => 3,
            'quantity' => $quantity,
        ];

        $params = new RequestParamLmsTicketApplication();
        self::doMethod((new LmsTicketRegister()), 'AddLmsTicket', [1, $params]);

        // 作成したデータのIDを取得
        $lmsTicketId = PDOHelper::GetPDO()->lastInsertId();

        // 申請レコードを登録する
        $_POST = [
            'lms_ticket_id' => $lmsTicketId,
            'quantity' => $quantity,
        ];

        $params = new RequestParamLmsTicketApplication();
        $params->type = LmsTicketApplication::TYPE_GRANTED_ADMINISTRATOR;
        $result = (new LmsTicketApplicationRegister())->AddLmsTicketApplication($params);

        // 作成したデータのIDを取得
        $lmsTicketApplicationId = PDOHelper::GetPDO()->lastInsertId();

        // チケットを有効にする
        (new LmsTicketRegister())->UpdateStatus($lmsTicketId, LmsTicket::STATUS_ENABLE);
        (new LmsTicketApplicationRegister())->UpdateStatus($lmsTicketApplicationId, LmsTicketApplication::STATUS_APPROVED);

        // LMSコード生成
        $lmsCode = (new LmsCodeGenerator())->Generate();

        $resultLmsCode = (new LmsCodeModel())->Add(new LmsCode([
            'lms_code' => $lmsCode,
            'type' => LmsCode::TYPE_LMS_TICKET
        ]));

        // LMSチケットグループを登録
        $lmsCodeId = PDOHelper::GetLastInsertId(PDOHelper::GetPDO());

        $_POST = [
            'lms_ticket_id' => $lmsTicketId,
            'name' => 'テストチケットグループ',
            'quantity' => 5,
        ];

        $params = new RequestParamLmsTicketGroup();
        $result = (new LmsTicketGroupRegister())->AddLmsTicketGroup($lmsCodeId, $params);

        $lmsTicketGroupId = PDOHelper::GetLastInsertId(PDOHelper::GetPDO());

        // カウントアップレコード追加
        $result = self::doMethod((new LmsTicketGroupRegister()), 'AddCountUpRecord', [$lmsTicketGroupId]);

        $record = (new LmsTicketGroupViewModel())->GetById($lmsTicketGroupId);

        self::$validLmsCodeForOnigiri = $record['lms_code'];

    }

    public static function tearDownAfterClass(): void
    {
        PDOHelper::GetPDO()->rollBack();
    }

    protected function setUp(): void
    {
        $_POST = null;
    }

    public function test_使うことのできないLMSコードの場合はエラー()
    {
        $AddLmsCode = new AddLmsCode();
        $studentId = 999999;
        $_POST['lms_code'] = 'a';

        $params = new RequestParamStudentAddLmsCode();

        $errorCodes = $AddLmsCode->CheckValidateParameters($studentId, $params);
        $this->assertTrue(in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LMS_CODE_INVALID, $errorCodes));


        $_POST['lms_code'] = '6wh5tee7uu3c5';

        $params = new RequestParamStudentAddLmsCode();

        $errorCodes = $AddLmsCode->CheckValidateParameters($studentId, $params);
        $this->assertTrue(in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LMS_CODE_INVALID, $errorCodes));
    }

    public function test_無料コードでもエラーにならない()
    {
        $AddLmsCode = new AddLmsCode();
        $studentId = 999999;
        $_POST['lms_code'] = 'fwytbq58qfm83';

        $params = new RequestParamStudentAddLmsCode();

        $errorCodes = $AddLmsCode->CheckValidateParameters($studentId, $params);
        $this->assertEmpty($errorCodes);
    }

    public function test_登録可能なLMSコードの場合はエラーにならない()
    {
        $records = (new SchoolGroupViewModel())->GetsByKeyValues(
            ['is_enable', 'is_paid', 'paid_application_status'],
            [1, 1, LmsCodeApplication::STATUS_ALLOWED]
        );

        $this->assertNotEmpty($records);

        $AddLmsCode = new AddLmsCode();
        $studentId = 999999;
        $_POST['lms_code'] = $records[0]['lms_code'];

        $params = new RequestParamStudentAddLmsCode();

        $errorCodes = $AddLmsCode->CheckValidateParameters($studentId, $params);
        $this->assertEmpty($errorCodes);
    }

    public function test_使うことが可能なOnigiriのLMSチケットの場合はエラーにならない()
    {
        $AddLmsCode = new AddLmsCode();
        $studentId = 999999;
        $_POST['lms_code'] = self::$validLmsCodeForOnigiri;

        $params = new RequestParamStudentAddLmsCode();

        $errorCodes = $AddLmsCode->CheckValidateParameters($studentId, $params);
        $this->assertEmpty($errorCodes);
    }

    public function test_OnigiriのLMSチケットの数が足りなくなった場合はエラーになる()
    {
        $AddLmsCode = new AddLmsCode();
        $studentId = 999999;
        $_POST['lms_code'] = self::$validLmsCodeForOnigiri;

        $record = (new LmsTicketGroupViewModel())->GetByKeyValue('lms_code', self::$validLmsCodeForOnigiri);
        self::doMethod((new LmsTicketGroupUseCountModel()), 'DebugCountSet', [$record['id'], 9999]);

        $params = new RequestParamStudentAddLmsCode();

        $errorCodes = $AddLmsCode->CheckValidateParameters($studentId, $params);

        // エラーチェックだけしたら値を戻しておく
        self::doMethod((new LmsTicketGroupUseCountModel()), 'DebugCountSet', [$record['id'], 0]);

        $this->assertTrue(in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LMS_CODE_INVALID, $errorCodes));
    }

    public function test_AddLmsCode()
    {
        $records = (new SchoolGroupViewModel())->GetsByKeyValues(
            ['is_enable', 'is_paid', 'paid_application_status'],
            [1, 1, LmsCodeApplication::STATUS_ALLOWED]
        );

        $this->assertNotEmpty($records);

        $AddLmsCode = new AddLmsCode();
        $studentId = 999999;
        $_POST['lms_code'] = $records[0]['lms_code'];

        $params = new RequestParamStudentAddLmsCode();

        $result = $AddLmsCode->AddLmsCode($studentId, $params);
        $this->assertTrue($result);
    }
}
