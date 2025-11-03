<?php

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\LmsTickets\LmsTicketRegister;
use IizunaLMS\Models\LmsTicketModel;
use IizunaLMS\Requests\RequestParamLmsTicketApplication;

require_once (__DIR__ . '/../app/bootstrap.php');
require_once ('TestBase.php');


class LmsTicketTest extends TestBase
{
    protected function setUp(): void
    {
        $_POST = null;
        PDOHelper::GetPDO()->beginTransaction();
    }

    protected function tearDown(): void
    {
        PDOHelper::GetPDO()->rollBack();
    }

    public function test_種別（title_no）がない場合はエラー()
    {
        $teacherId = 999999;
        $params = new RequestParamLmsTicketApplication();
        $errorCodes = (new LmsTicketRegister())->CheckValidateParameters($teacherId, $params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER, $errorCodes));
    }

    public function test_種別（title_no）が対象外の場合はエラー()
    {
        $teacherId = 999999;
        $_POST['title_no'] = 'aaa';

        $params = new RequestParamLmsTicketApplication();
        $errorCodes = (new LmsTicketRegister())->CheckValidateParameters($teacherId, $params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER, $errorCodes));
    }

    public function test_期限（年）がない場合はエラー()
    {
        $teacherId = 999999;
        $_POST = [
            'title_no' => '10086',
            'expire_year' => null,
            'expire_month' => '1',
        ];

        $params = new RequestParamLmsTicketApplication();

        $errorCodes = (new LmsTicketRegister())->CheckValidateParameters($teacherId, $params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER, $errorCodes));
    }

    public function test_期限（月）がない場合はエラー()
    {
        $teacherId = 999999;
        $_POST = [
            'title_no' => '10086',
            'expire_year' => 2025,
            'expire_month' => null,
        ];

        $params = new RequestParamLmsTicketApplication();

        $errorCodes = (new LmsTicketRegister())->CheckValidateParameters($teacherId, $params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER, $errorCodes));
    }

    public function test_期限が正しくない場合はエラー()
    {
        $teacherId = 999999;
        $_POST = [
            'title_no' => '10086',
            'expire_year' => 2025,
            'expire_month' => 13,
        ];

        $params = new RequestParamLmsTicketApplication();

        $errorCodes = (new LmsTicketRegister())->CheckValidateParameters($teacherId, $params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER, $errorCodes));


        $_POST = [
            'title_no' => '10086',
            'expire_year' => -2,
            'expire_month' => 1,
        ];

        $params = new RequestParamLmsTicketApplication();

        $errorCodes = (new LmsTicketRegister())->CheckValidateParameters($teacherId, $params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER, $errorCodes));
    }

    public function test_期限が過去の場合はエラー()
    {
        $teacherId = 999999;
        $_POST = [
            'title_no' => '10086',
            'expire_year' => 2024,
            'expire_month' => 3,
        ];

        $params = new RequestParamLmsTicketApplication();

        $errorCodes = (new LmsTicketRegister())->CheckValidateParameters($teacherId, $params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_OUTDATED, $errorCodes));
    }

    public function test_チケット数が正の数値以外の場合はエラー()
    {
        $teacherId = 999999;
        $_POST = [
            'title_no' => '10086',
            'expire_year' => 2025,
            'expire_month' => 3,
            'quantity' => -1,
        ];

        $params = new RequestParamLmsTicketApplication();

        $errorCodes = (new LmsTicketRegister())->CheckValidateParameters($teacherId, $params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_QUANTITY, $errorCodes));
    }

    public function test_既に登録済みの期間の場合はエラーにする()
    {
        $teacherId = 999999;
        $titleNo = '10086';

        $_POST = [
            'title_no' => $titleNo,
            'expire_year' => 2024,
            'expire_month' => 10,
            'quantity' => 10,
        ];

        $params = new RequestParamLmsTicketApplication();

        $errorCodes = (new LmsTicketRegister())->CheckValidateParameters($teacherId, $params);
        $this->assertEmpty($errorCodes);

        $result = $this->doMethod((new LmsTicketRegister()), 'AddLmsTicket', [$teacherId, $params]);
        $this->assertTrue($result);

        $errorCodes = (new LmsTicketRegister())->CheckValidateParameters($teacherId, $params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_ALREADY_ADD, $errorCodes));
    }

    public function test_Add()
    {
        $_POST = [
            'title_no' => '10086',
            'expire_year' => 2025,
            'expire_month' => 3,
            'quantity' => 10,
        ];

        $beforeCount = (new LmsTicketModel())->Count();

        $params = new RequestParamLmsTicketApplication();

        $result = $this->doMethod((new LmsTicketRegister()), 'AddLmsTicket', [1, $params]);
        $this->assertTrue($result);

        $afterCount = (new LmsTicketModel())->Count();

        $this->assertTrue($beforeCount + 1 === $afterCount);
    }

    public function test_UpdateStatus()
    {
        $_POST = [
            'title_no' => '10086',
            'expire_year' => 2025,
            'expire_month' => 3,
            'quantity' => 10,
        ];

        $params = new RequestParamLmsTicketApplication();
        $result = $this->doMethod((new LmsTicketRegister()), 'AddLmsTicket', [1, $params]);

        // 作成したデータのIDを取得
        $lmsTicketId = PDOHelper::GetPDO()->lastInsertId();

        (new LmsTicketRegister())->UpdateStatus($lmsTicketId, LmsTicket::STATUS_DELETE_BY_TEACHER);

        $record = (new LmsTicketModel())->GetById($lmsTicketId);

        $this->assertTrue($record['status'] == LmsTicket::STATUS_DELETE_BY_TEACHER);
    }
}
