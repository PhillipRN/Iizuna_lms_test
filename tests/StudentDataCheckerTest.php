<?php

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\PDOHelper;
use IizunaLMS\Students\StudentDataChecker;

require_once (__DIR__ . '/../app/bootstrap.php');
require_once ('TestBase.php');


class StudentDataCheckerTest extends TestBase
{
    private $validLmsCodeId = 'qh3k4bgzwuzio';

    protected function setUp(): void
    {
        $_POST = null;
        PDOHelper::GetPDO()->beginTransaction();
    }

    protected function tearDown(): void
    {
        PDOHelper::GetPDO()->rollBack();
    }

    public function test_パスワードのフォーマットが正しくない場合はエラー()
    {
        $result = (new StudentDataChecker())->CheckPassword('a');
        $this->assertFalse($result);
    }

    public function test_パスワードのフォーマットが正しい()
    {
        $result = (new StudentDataChecker())->CheckPassword('aaaaaaa1');
        $this->assertTrue($result);
    }

    public function test_ログインIDのフォーマットが正しくない場合はエラー()
    {
        $result = (new StudentDataChecker())->CheckLoginId('a');
        $this->assertTrue(empty($result));
    }

    public function test_ログインIDのフォーマットが正しい()
    {
        $result = (new StudentDataChecker())->CheckLoginId('aaa1');
        $this->assertTrue(!empty($result) && is_int($result));
    }

//IsRegisteredOtherStudentLoginId

    public function test_LMSコードが正しくない場合はエラー()
    {
        $result = (new StudentDataChecker())->IsLmsCode('a');
        $this->assertFalse($result);
    }

    public function test_LMSコードが正しい()
    {
        $result = (new StudentDataChecker())->IsLmsCode($this->validLmsCodeId);
        $this->assertTrue($result);
    }

    public function test_使うことができないLMSコードの場合はエラー()
    {
        $result = (new StudentDataChecker())->IsLmsCode('a');
        $this->assertFalse($result);
    }

    public function test_使うことができるLMSコード()
    {
        $result = (new StudentDataChecker())->IsLmsCode($this->validLmsCodeId);
        $this->assertTrue($result);
    }
}
