<?php

require_once (__DIR__ . '/../app/bootstrap.php');
require_once ('TestBase.php');

use IizunaLMS\EBook\EbookSchool;
use IizunaLMS\EBook\Requests\RequestParamEbookSchool;

class EbookSchoolTest extends TestBase
{
    protected function setUp(): void
    {
        $_POST = null;
    }

    public function test_LMSコードがない場合はパラメータエラー()
    {
        $params = new RequestParamEbookSchool();

        $result = (new EbookSchool())->GetBookStatuses($params);
        $this->assertTrue(isset($result['error']));
    }

    public function test_LMSコードが正しい場合()
    {
        $_POST['lms_code'] = 'qh3k4bgzwuzio';

        $params = new RequestParamEbookSchool();

        $result = (new EbookSchool())->GetBookStatuses($params);
        $this->assertTrue(!isset($result['error']));

        $this->assertTrue(isset($result['result']['10052']));
    }

    public function test_LMSコードが正しくてDBにデータが登録されていない場合でも値は帰ってくる()
    {
        $_POST['lms_code'] = '44vjmamgjhnwh';

        $params = new RequestParamEbookSchool();

        $result = (new EbookSchool())->GetBookStatuses($params);
        $this->assertTrue(!isset($result['error']));

        $this->assertTrue(isset($result['result']['10052']));
    }

    public function test_LMSコードが間違っている場合はエラー()
    {
        $_POST['lms_code'] = 'aaaaaaaaaaaaaaaaaaaaa';

        $params = new RequestParamEbookSchool();

        $result = (new EbookSchool())->GetBookStatuses($params);
        $this->assertTrue(isset($result['error']));
    }
}
