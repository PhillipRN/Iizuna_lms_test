<?php

namespace IizunaLMS\EBook\Requests;

class RequestParamEbookQuiz extends RequestParams implements IRequestParams
{
    public $titleNo;
    public $page;
    public $genres;
    public $questionKind;
    public $chapters;
    public $number;
    public $isInput;
    public $isRandom;

    function __construct()
    {
        $this->titleNo = $this->GetPostParam('t');
        $this->page = $this->GetPostParam('p');
        $this->questionKind = $this->GetPostParam('k');
        $this->number = $this->GetPostParam('n', 0);
        $this->isInput = $this->GetPostParam('i', 1);
        $this->isRandom = ($this->GetPostParam('r', 1) == 1);

        if (empty($this->isInput)) $this->isInput = 0;

        $chapterString = $this->GetPostParam('c');
        $genreString = $this->GetPostParam('g');

        if (!empty($chapterString)) $this->chapters = $this->GenerateChapters($chapterString);
        if (!empty($genreString)) $this->genres = $this->GenerateGenres($genreString);
    }

    public function IsValid(): bool
    {
        return (
            !empty($this->titleNo) && (
                !empty($this->page) ||
                ( !empty($this->questionKind) && !empty($this->chapters) )
            )
        );
    }

    /**
     * @param $genreString
     * @return array
     */
    private function GenerateGenres($genreString): array
    {
        return explode('_', $genreString);
    }

    /**
     * @param $chapterString
     * @return array
     */
    private function GenerateChapters($chapterString): array
    {
        $chapterArray = explode('_', $chapterString);

        $chapters = [];
        foreach ($chapterArray as $tmpChapter) {
            $values = explode('-', $tmpChapter);

            $chapters[] = [
                'chapter' => $values[0],
                'primary_item' => $values[1] ?? 0,
                'secondary_item' => $values[2] ?? 0,
                'tertiary_item' => $values[3] ?? 0,
            ];
        }

        return $chapters;
    }
}