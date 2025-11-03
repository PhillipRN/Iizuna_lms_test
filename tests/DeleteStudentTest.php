<?php

require_once (__DIR__ . '/../app/bootstrap.php');
require_once ('TestBase.php');

use IizunaLMS\Students\DeleteStudent;
use IizunaLMS\Students\StudentSchool;

class DeleteStudentTest extends TestBase
{
    /**
     * @covers DeleteStudent::CheckStudentSchool
     */
    public function test生徒がその学校に所属している()
    {
        $result = (new StudentSchool())->Check(42, 1);
        $this->assertTrue($result);
    }

    /**
     * @covers DeleteStudent::CheckStudentSchool
     */
    public function test生徒がその学校に所属していない()
    {
        $result = (new StudentSchool())->Check(42, 2);
        $this->assertFalse($result);
    }
}
