<?php

require_once (__DIR__ . '/../app/bootstrap.php');
require_once ('TestBase.php');

use IizunaLMS\EBook\Requests\RequestParamEbookQuiz;

class RequestParamEbookQuizTest extends TestBase
{
    /**
     * @covers RequestParamEbookQuiz::GenerateChapters
     */
    public function testGenerateChapters()
    {
        $chapterString = '1_2-1_3-2-1_4-3-2-1';

        $result = $this->doMethod((new RequestParamEbookQuiz()), 'GenerateChapters', [$chapterString]);

        $this->assertTrue(
            $result[0]['chapter'] == 1 &&
            $result[0]['primary_item'] == 0 &&
            $result[1]['primary_item'] == 1 &&
            $result[2]['secondary_item'] == 1 &&
            $result[3]['tertiary_item'] == 1);
    }
}
