<?php

require_once (__DIR__ . '/../app/bootstrap.php');
require_once ('TestBase.php');

use IizunaLMS\EBook\EbookExample;
use IizunaLMS\EBook\EbookQuiz;
use IizunaLMS\EBook\Requests\RequestParamEbookDailyQuiz;
use IizunaLMS\EBook\Requests\RequestParamEbookFlashCard;
use IizunaLMS\EBook\Requests\RequestParamEbookQuiz;

class EbookExampleTest extends TestBase
{
    /**
     * @covers EbookExample::ConvertInformationResultData
     */
    public function testConvertInformationResultData()
    {
        $records = [
            ['page' => 101],
            ['page' => 102],
            ['page' => 103]
        ];

        $result = $this->doMethod((new EbookExample()), 'ConvertInformationResultData', [$records]);

        $this->assertTrue(isset($result['result']) && is_array($result['result']['quizzes']) && is_array($result['result']['voices']));

        $result = $this->doMethod((new EbookExample()), 'ConvertInformationResultData', [[]]);

        // 空を渡した場合はエラーコードがある
        $this->assertTrue(isset($result['error']));
    }
    /**
     * @covers EbookExample::ConvertVoiceResultData
     */
    public function testConvertVoiceResultData()
    {
        $records = [
            ['id' => 101, 'english' => 'hogehoge', 'voice' => 'aaa'],
            ['id' => 102, 'english' => 'hugahuga', 'voice' => 'bbb'],
            ['id' => 103, 'english' => 'ugougo', 'voice' => 'ccc']
        ];

        $result = $this->doMethod((new EbookExample()), 'ConvertVoiceResultData', [$records]);

        $this->assertTrue(isset($result['result']) && !empty($result['result'][0]['id']) && !empty($result['result'][0]['en']) && !empty($result['result'][0]['voice_file']));

        $result = $this->doMethod((new EbookExample()), 'ConvertVoiceResultData', [[]]);

        // 空を渡した場合はエラーコードがある
        $this->assertTrue(isset($result['error']));
    }

    public function testConvertFlashCardResultData()
    {
        $records = [
            ['id' => 101, 'english' => 'hogehoge','japanese' => 'ほげほげ',  'voice' => 'aaa'],
            ['id' => 102, 'english' => 'hugahuga','japanese' => 'ふがふが',  'voice' => 'bbb'],
            ['id' => 103, 'english' => 'ugougo','japanese' => 'うごうご',  'voice' => 'ccc']
        ];

        $result = $this->doMethod((new EbookExample()), 'ConvertFlashCardResultData', [$records, 3]);

        $this->assertTrue(isset($result['result']) && !empty($result['result'][0]['id']) && !empty($result['result'][0]['en']) && !empty($result['result'][0]['voice_file']));

        $result = $this->doMethod((new EbookExample()), 'ConvertFlashCardResultData', [[], 4]);

        // 空を渡した場合はエラーコードがある
        $this->assertTrue(isset($result['error']));
    }

    public function testGenerateFlashCardResultData()
    {
        $_POST['t'] = 10052;
        $_POST['c'] = '1_3';
        $_POST['n'] = 5;
        $_POST['nt_ja'] = 1;

        $params = new RequestParamEbookFlashCard();

        $result = $this->doMethod(new EbookExample(), 'GenerateFlashCardResultData', [$params]);

        $this->assertTrue(count($result['result']) == $_POST['n']);
    }

    public function testGenerateQuiz()
    {
        $_POST['t'] = 99093;
        $_POST['c'] = '1_2-1_3-2-1_4-3-2-1';
        $_POST['n'] = 5;
        $_POST['k'] = 2;

        $params = new RequestParamEbookQuiz();

        $result = $this->doMethod(new EbookQuiz(), 'GenerateQuiz', [$params]);

        $this->assertTrue(!isset($result['error']));
    }

    public function testGenerateDailyQuiz()
    {
        $_POST['t'] = 99093;
        $_POST['l'] = 2;

        $params = new RequestParamEbookDailyQuiz();

        $result = $this->doMethod(new EbookQuiz(), 'GenerateDailyQuiz', [$params]);

        $this->assertTrue(!isset($result['error']));
    }
}
