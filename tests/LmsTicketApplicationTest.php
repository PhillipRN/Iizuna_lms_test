<?php

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\LmsTicketApplication;
use IizunaLMS\LmsTickets\LmsTicketApplicationRegister;
use IizunaLMS\LmsTickets\LmsTicketRegister;
use IizunaLMS\Models\LmsTicketApplicationModel;
use IizunaLMS\Requests\RequestParamLmsTicketApplication;

require_once (__DIR__ . '/../app/bootstrap.php');
require_once ('TestBase.php');


class LmsTicketApplicationTest extends TestBase
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

    public function test_lms_ticket_idがない場合はエラー()
    {
        $params = new RequestParamLmsTicketApplication();
        $errorCodes = (new LmsTicketApplicationRegister())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER, $errorCodes));
    }

    public function test_lms_ticket_idが正しくない場合はエラー()
    {
        $_POST['lms_ticket_id'] = -1;

        $params = new RequestParamLmsTicketApplication();
        $errorCodes = (new LmsTicketApplicationRegister())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_PARAMETER, $errorCodes));
    }

    public function test_lms_ticket_idが正しくい()
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

        $_POST = [
            'lms_ticket_id' => $lmsTicketId,
            'quantity' => 10,
        ];

        $params = new RequestParamLmsTicketApplication();
        $errorCodes = (new LmsTicketApplicationRegister())->CheckValidateParameters($params);
        $this->assertEmpty($errorCodes);
    }

    public function test_チケット数が正の数値以外の場合はエラー()
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

        $_POST = [
            'lms_ticket_id' => $lmsTicketId,
            'quantity' => -1,
        ];

        $params = new RequestParamLmsTicketApplication();
        $errorCodes = (new LmsTicketApplicationRegister())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_APPLICATION_INVALID_QUANTITY, $errorCodes));
    }

    public function test_AddLmsTicketApplication()
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

        $_POST = [
            'lms_ticket_id' => $lmsTicketId,
            'quantity' => 10,
        ];

        $params = new RequestParamLmsTicketApplication();
        $result = (new LmsTicketApplicationRegister())->AddLmsTicketApplication($params);
        $this->assertTrue($result);
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

        $_POST = [
            'lms_ticket_id' => $lmsTicketId,
            'quantity' => 10,
        ];

        $params = new RequestParamLmsTicketApplication();
        $params->type = LmsTicketApplication::TYPE_GRANTED_ADMINISTRATOR;
        $result = (new LmsTicketApplicationRegister())->AddLmsTicketApplication($params);

        // 作成したデータのIDを取得
        $lmsTicketApplicationId = PDOHelper::GetPDO()->lastInsertId();

        (new LmsTicketApplicationRegister())->UpdateStatus($lmsTicketApplicationId, LmsTicketApplication::STATUS_APPROVED);

        $record = (new LmsTicketApplicationModel())->GetById($lmsTicketApplicationId);

        $this->assertTrue($record['status'] == LmsTicketApplication::STATUS_APPROVED);
    }
}
