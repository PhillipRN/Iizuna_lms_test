<?php

require_once (__DIR__ . '/../app/bootstrap.php');

use IizunaLMS\Models\StudentAuthorizationKeyModel;
use IizunaLMS\Students\StudentAuthorizationRegister;
use PHPUnit\Framework\TestCase;

class StudentAuthorizationRegisterTest extends TestCase
{
    /**
     * @covers StudentAuthorizationRegister::Add
     */
    public function testAdd()
    {
        $StudentAuthorizationRegister = new StudentAuthorizationRegister();

        $mockModel = $this->createMock(StudentAuthorizationKeyModel::class);
        $mockModel->method('Add')
            ->will($this->returnValue(true));

        $StudentAuthorizationRegister->AttachStudentAuthorizationKeyModel($mockModel);

        $result = $StudentAuthorizationRegister->Add(1, '1111');

        $this->assertTrue($result);
    }
}
