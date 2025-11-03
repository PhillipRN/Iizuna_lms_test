<?php

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\LmsTicket;
use IizunaLMS\LmsTickets\LmsTicketApplication;
use IizunaLMS\LmsTickets\LmsTicketApplicationRegister;
use IizunaLMS\LmsTickets\LmsTicketGroup;
use IizunaLMS\LmsTickets\LmsTicketGroupRegister;
use IizunaLMS\LmsTickets\LmsTicketRegister;
use IizunaLMS\Models\LmsTicketGroupModel;
use IizunaLMS\Models\LmsTicketGroupViewModel;
use IizunaLMS\Requests\RequestParamLmsTicketApplication;
use IizunaLMS\Requests\RequestParamLmsTicketGroup;

require_once (__DIR__ . '/../app/bootstrap.php');
require_once ('TestBase.php');


class LmsTicketGroupTest extends TestBase
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
        $params = new RequestParamLmsTicketGroup();
        $errorCodes = (new LmsTicketGroupRegister())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_GROUP_INVALID_PARAMETER, $errorCodes));
    }

    public function test_lms_ticket_idが正しくない場合はエラー()
    {
        $_POST['lms_ticket_id'] = -1;

        $params = new RequestParamLmsTicketGroup();
        $errorCodes = (new LmsTicketGroupRegister())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_GROUP_INVALID_PARAMETER, $errorCodes));
    }

    public function test_nameがない場合はエラー()
    {
        $params = new RequestParamLmsTicketGroup();
        $errorCodes = (new LmsTicketGroupRegister())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_GROUP_EMPTY_NAME, $errorCodes));
    }

    public function test_lms_ticket_idが正しい()
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

        // 申請レコードを登録する
        $_POST = [
            'lms_ticket_id' => $lmsTicketId,
            'quantity' => 10,
        ];

        $params = new RequestParamLmsTicketApplication();
        $params->type = LmsTicketApplication::TYPE_GRANTED_ADMINISTRATOR;
        $result = (new LmsTicketApplicationRegister())->AddLmsTicketApplication($params);

        // 作成したデータのIDを取得
        $lmsTicketApplicationId = PDOHelper::GetPDO()->lastInsertId();

        // チケットを有効にする
        (new LmsTicketRegister())->UpdateStatus($lmsTicketId, LmsTicket::STATUS_ENABLE);
        (new LmsTicketApplicationRegister())->UpdateStatus($lmsTicketApplicationId, LmsTicketApplication::STATUS_APPROVED);

        $_POST = [
            'lms_ticket_id' => $lmsTicketId,
            'name' => 'テストチケットグループ',
            'quantity' => 10,
        ];

        $params = new RequestParamLmsTicketGroup();
        $errorCodes = (new LmsTicketGroupRegister())->CheckValidateParameters($params);
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
            'name' => 'テストチケットグループ',
            'quantity' => -1,
        ];

        $params = new RequestParamLmsTicketGroup();
        $errorCodes = (new LmsTicketGroupRegister())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_GROUP_INVALID_QUANTITY, $errorCodes));
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
            'name' => 'テストチケットグループ',
            'quantity' => -1,
        ];

        $params = new RequestParamLmsTicketGroup();
        $result = (new LmsTicketGroupRegister())->AddLmsTicketGroup(1, $params);
        $this->assertTrue($result);
    }

    public function test_チケット数が不足している場合はエラー()
    {
        $quantity = 10;
        $_POST = [
            'title_no' => '10086',
            'expire_year' => 2025,
            'expire_month' => 3,
            'quantity' => $quantity,
        ];

        $params = new RequestParamLmsTicketApplication();
        $result = $this->doMethod((new LmsTicketRegister()), 'AddLmsTicket', [1, $params]);

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


        $_POST = [
            'lms_ticket_id' => $lmsTicketId,
            'name' => 'テストチケットグループ',
            'quantity' => 5,
        ];

        $params = new RequestParamLmsTicketGroup();
        $result = (new LmsTicketGroupRegister())->AddLmsTicketGroup(1, $params);
        $this->assertTrue($result);

        $lmsTicketGroupId = PDOHelper::GetPDO()->lastInsertId();
        $result = $this->doMethod((new LmsTicketGroupRegister()), 'AddCountUpRecord', [$lmsTicketGroupId]);

        // ここで残数が0になる
        $result = (new LmsTicketGroupRegister())->AddLmsTicketGroup(1, $params);
        $this->assertTrue($result);

        $lmsTicketGroupId = PDOHelper::GetPDO()->lastInsertId();
        $result = $this->doMethod((new LmsTicketGroupRegister()), 'AddCountUpRecord', [$lmsTicketGroupId]);

        $errorCodes = (new LmsTicketGroupRegister())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_TEACHER_LMS_TICKET_GROUP_QUANTITY_NOT_ENOUGH, $errorCodes));
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
            'name' => 'テストチケットグループ',
            'quantity' => -1,
        ];

        $params = new RequestParamLmsTicketGroup();
        $result = (new LmsTicketGroupRegister())->AddLmsTicketGroup(1, $params);


        // 作成したデータのIDを取得
        $lmsTicketGroupId = PDOHelper::GetPDO()->lastInsertId();

        (new LmsTicketGroupRegister())->UpdateStatus($lmsTicketGroupId, LmsTicketGroup::STATUS_DELETE_BY_TEACHER);

        $record = (new LmsTicketGroupModel())->GetById($lmsTicketGroupId);

        $this->assertTrue($record['status'] == LmsTicketGroup::STATUS_DELETE_BY_TEACHER);
    }
}
