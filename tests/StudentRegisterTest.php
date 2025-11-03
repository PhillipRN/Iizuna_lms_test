<?php

use IizunaLMS\Errors\Error;
use IizunaLMS\Requests\RequestParamStudentRegisterForWeb;
use IizunaLMS\Students\StudentRegisterForWeb;

require_once (__DIR__ . '/../app/bootstrap.php');
require_once ('TestBase.php');


class StudentRegisterTest extends TestBase
{
    protected function setUp(): void
    {
        $_POST = null;
    }

    public function test_ログインIDがない場合はエラー()
    {
        $params = new RequestParamStudentRegisterForWeb();

        $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LOGIN_ID_NULL, $errorCodes));
    }

    public function test_ログインIDが4文字未満の場合はエラー()
    {
        $_POST['login_id'] = 'aaa';

        $params = new RequestParamStudentRegisterForWeb();

        $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LOGIN_ID_INVALID, $errorCodes));
    }

    public function test_ログインIDが正しい場合はエラーにならない()
    {
        $_POST['login_id'] = 'test_-23891';

        $params = new RequestParamStudentRegisterForWeb();

        $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
        $this->assertTrue(!in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LOGIN_ID_INVALID, $errorCodes));
    }

    public function test_ログインIDに指定された以外の文字が入っている場合はエラー()
    {
        $_POST['login_id'] = 'あ';

        $params = new RequestParamStudentRegisterForWeb();

        $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LOGIN_ID_INVALID, $errorCodes));
    }

    public function test_ログインIDが既に登録されている場合はエラー()
    {
        $_POST['login_id'] = 'aaa30';

        $params = new RequestParamStudentRegisterForWeb();

        $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LOGIN_ID_ALREADY_REGISTERED, $errorCodes));
    }

    public function test_パスワードがない場合はエラー()
    {
        $params = new RequestParamStudentRegisterForWeb();

        $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_PASSWORD_NULL, $errorCodes));
    }

    public function test_パスワードが8文字未満の場合はエラー()
    {
        $_POST['password'] = 'aaaaaaa';

        $params = new RequestParamStudentRegisterForWeb();

        $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_PASSWORD_INVALID, $errorCodes));
    }

    public function test_パスワードに数字が入っていない場合はエラー()
    {
        $_POST['password'] = 'aaaaaaaaaa';

        $params = new RequestParamStudentRegisterForWeb();

        $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_PASSWORD_INVALID, $errorCodes));
    }

    public function test_パスワードが正しい場合はエラーにならない()
    {
        $_POST['password'] = 'aaaa8aaaaa';
        $_POST['password_confirm'] = 'aaaa8aaaaa';

        $params = new RequestParamStudentRegisterForWeb();

        $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
        $this->assertTrue(!in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_PASSWORD_INVALID, $errorCodes));
        $this->assertTrue(!in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_PASSWORD_NOT_SAME, $errorCodes));
    }

    public function test_パスワード確認用が異なる場合はエラー()
    {
        $_POST['password'] = 'aaaa8aaaaa';

        $params = new RequestParamStudentRegisterForWeb();

        $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_PASSWORD_NOT_SAME, $errorCodes));
    }

    public function test_氏名がない場合はエラー()
    {
        $params = new RequestParamStudentRegisterForWeb();

        $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_NAME_NULL, $errorCodes));
    }

    public function test_氏名があるとエラーにならない()
    {
        $_POST['name'] = 'aaa';

        $params = new RequestParamStudentRegisterForWeb();

        $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
        $this->assertTrue(!in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_NAME_NULL, $errorCodes));
    }

    public function test_LMS_CODEがない場合はエラー()
    {
        $params = new RequestParamStudentRegisterForWeb();

        $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LMS_CODE_NULL, $errorCodes));
    }

    public function test_LMS_CODEが正しくない場合はエラー()
    {
        $_POST['lms_code'] = 'aaa';

        $params = new RequestParamStudentRegisterForWeb();

        $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
        $this->assertTrue(in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LMS_CODE_INVALID, $errorCodes));
    }

    public function test_LMS_CODEが正しい場合はエラーにならない()
    {
        $_POST['lms_code'] = 'qh3k4bgzwuzio';

        $params = new RequestParamStudentRegisterForWeb();

        $errorCodes = (new StudentRegisterForWeb())->CheckValidateParameters($params);
        $this->assertTrue(!in_array(Error::ERROR_STUDENT_REGISTER_FOR_WEB_FAILED_LMS_CODE_INVALID, $errorCodes));
    }
}
