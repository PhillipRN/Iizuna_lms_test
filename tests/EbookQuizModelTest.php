<?php

use IizunaLMS\EBook\Models\EbookQuizModel;

require_once (__DIR__ . '/../app/bootstrap.php');
require_once ('TestBase.php');

class EbookQuizModelTest extends TestBase
{
    /**
     * @covers EbookQuizModelTest::GenerateChapters
     */
    public function testGenerateChapters()
    {
        $chapters = [
            [
                'chapter' => 1,
                'primary_item' => 0,
                'secondary_item' => 0,
                'tertiary_item' => 0,
            ],
            [
                'chapter' => 2,
                'primary_item' => 1,
                'secondary_item' => 0,
                'tertiary_item' => 0,
            ],
            [
                'chapter' => 3,
                'primary_item' => 2,
                'secondary_item' => 1,
                'tertiary_item' => 0,
            ],
            [
                'chapter' => 4,
                'primary_item' => 3,
                'secondary_item' => 2,
                'tertiary_item' => 1,
            ],
            [
                'chapter' => 5,
                'primary_item' => 0,
                'secondary_item' => 2,
                'tertiary_item' => 1,
            ],
            [
                'chapter' => 6,
                'primary_item' => 1,
                'secondary_item' => 0,
                'tertiary_item' => 1,
            ]
        ];

        $result = $this->doMethod((new EbookQuizModel()), 'GenerateChaptersCondition', [$chapters]);

        $this->assertTrue(
            !empty($result['where']) &&
            substr_count($result['where'], 'chapter') == 12 &&
            substr_count($result['where'], 'primary_item') == 8 &&
            count($result['bind_values']) == 13
        );
    }
}
