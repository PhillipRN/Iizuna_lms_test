<?php

use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\LmsTickets\LmsTicketGroupRegister;
use IizunaLMS\Models\LmsTicketGroupUseCountModel;

require_once (__DIR__ . '/../app/bootstrap.php');
require_once ('TestBase.php');


class LmsTicketGroupCountUpTest extends TestBase
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

    public function test_AddCountRecord()
    {
        $lmsTicketGroupId = 999999;
        $result = $this->doMethod((new LmsTicketGroupRegister()), 'AddCountUpRecord', [$lmsTicketGroupId]);
        $this->assertTrue($result);
    }

    public function test_CountUp()
    {
        $lmsTicketGroupId = 999999;
        $result = $this->doMethod((new LmsTicketGroupRegister()), 'AddCountUpRecord', [$lmsTicketGroupId]);

        $record = (new LmsTicketGroupUseCountModel())->GetByKeyValue('lms_ticket_group_id', $lmsTicketGroupId);
        $this->assertTrue($record['use_count'] == 0);

        $result = (new LmsTicketGroupRegister())->CountUp($lmsTicketGroupId);

        $record = (new LmsTicketGroupUseCountModel())->GetByKeyValue('lms_ticket_group_id', $lmsTicketGroupId);
        $this->assertTrue($record['use_count'] == 1);
    }

    public function test_1ヶ月以上経過している場合はカウントダウンしない()
    {
        $lmsTicketGroupId = 999999;
        $applicationDate = (new \DateTime())->modify('-1 month')->modify('-1 day')->format('Y-m-d');
        $result = $this->doMethod((new LmsTicketGroupRegister()), 'CheckLimitAndCountDown', [$lmsTicketGroupId, $applicationDate]);

        $this->assertFalse($result);
    }

    public function test_CountDown()
    {
        $lmsTicketGroupId = 999999;
        $result = $this->doMethod((new LmsTicketGroupRegister()), 'AddCountUpRecord', [$lmsTicketGroupId]);

        $record = (new LmsTicketGroupUseCountModel())->GetByKeyValue('lms_ticket_group_id', $lmsTicketGroupId);
        $this->assertTrue($record['use_count'] == 0);

        $result = (new LmsTicketGroupRegister())->CountUp($lmsTicketGroupId);
        $result = (new LmsTicketGroupRegister())->CountUp($lmsTicketGroupId);

        $applicationDate = (new \DateTime())->modify('-1 month')->modify('+1 day')->format('Y-m-d');
        $result = $this->doMethod((new LmsTicketGroupRegister()), 'CheckLimitAndCountDown', [$lmsTicketGroupId, $applicationDate]);

        $this->assertTrue($result);

        $record = (new LmsTicketGroupUseCountModel())->GetByKeyValue('lms_ticket_group_id', $lmsTicketGroupId);
        $this->assertTrue($record['use_count'] == 1);
    }
}
