<?php

namespace IizunaLMS\Books;

use IizunaLMS\Models\BookModel;
use IizunaLMS\Models\TeacherBookModel;
use IizunaLMS\Models\TeacherBookTempModel;

class BookLoader
{
    private $availableTitleNos = [
        '10008',
        '10009',
        '10010',
        '10019',
        '10020',
        '10035',
        '10036',
        '10042',
        '10043',
        '10044',
        '10052',
        '10059',
        '10060',
        '10061',
        '10062',
        '10063',
        '10067',
        '10069',
        '10072',
        '10073',
        '10075',
        '10077',
        '10078',
        '10079',
        '10081',
        '10082',
        '10086',
        '10087',
        '10089',
        '10090',
        '10091',
        '10092',
        '10093',
        '10094',
        '10095',
        '10096',
        '10097',
        '10098',
        '10099',
        '10100',
        '10102',
        '10103',
        '10104',
        '10105',
        '10109',
        '10112',
        '10113',
        '20023',
        '20027',
        '20028',
        '20029',
        '20031',
        '20032',
        '20033',
        '20034',
        '20035',
        '20037',
        '20038',
        '20039',
        '20040',
        '20041'
    ];

    /**
     * @param $teacherId
     * @return array[]
     */
    public function GetAvailableBookList($teacherId)
    {
        $bookList = $this->GetBookList();
        $result = $this->FilterBookList($bookList, $this->availableTitleNos);

        // 所持タイトル取得
        $titleNos = $this->GetTeacherBookTitleNos($teacherId);

        return $this->FilterBookList($result, $titleNos);
    }

    /**
     * @return array
     */
    public function GetAvailableBookListForApplicationLMS()
    {
        $bookList = $this->GetBookList();
        return $this->FilterBookList($bookList, $this->availableTitleNos);
    }

    /**
     * @return array
     */
    public function GetBookList()
    {
        return array(
            Book::FLAG_ENGLISH_BOOK => $this->GetBookModel()->GetsByType(Book::FLAG_ENGLISH_BOOK),
            Book::FLAG_JAPANESE_BOOK => $this->GetBookModel()->GetsByType(Book::FLAG_JAPANESE_BOOK)
        );
    }

    /**
     * @return array
     */
    public function GetSortTitlenoBookList()
    {
        $bookList = $this->GetBookList();
        $result = [
            Book::FLAG_ENGLISH_BOOK => [],
            Book::FLAG_JAPANESE_BOOK => []
        ];

        foreach ($bookList as $key => $books) {

            $titleNos = [];

            foreach ($books as $book) {
                $titleNos[] = $book['title_no'];
            }

            array_multisort($titleNos, SORT_ASC, $books);

            $result[$key] = $books;
        }

        return $result;
    }

    /**
     * @param $teacherId
     * @return array
     */
    public function GetTeacherBookTitleNos($teacherId)
    {
        $teacherBooks = $this->GetTeacherBookModel()->GetsByKeyValue('teacher_id', $teacherId);

        $result = [];

        foreach ($teacherBooks as $teacherBook)
        {
            $result[] = $teacherBook['title_no'];
        }

        return $result;
    }

    /**
     * @param $teacherId
     * @return array
     */
    public function GetTeacherBookTempTitleNos($teacherId)
    {
        $userBooks = $this->GetTeacherBookTempModel()->GetsByKeyValue('teacher_id', $teacherId);

        $result = [];

        foreach ($userBooks as $userBook)
        {
            $result[] = $userBook["title_no"];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function GetBookDetails($titleNos)
    {
        if (empty($titleNos)) return [];
        return $this->GetBookModel()->GetBookListByTitleNos($titleNos);
    }

    /**
     * @param $bookList
     * @param $availableTitleNos
     * @return array[]
     */
    private function FilterBookList($bookList, $availableTitleNos)
    {
        $result = array(
            Book::FLAG_ENGLISH_BOOK => [],
            Book::FLAG_JAPANESE_BOOK => []
        );

        foreach ($bookList as $key => $books)
        {
            for ($i=0; $i<count($books); ++$i)
            {
                if (in_array($books[$i]['title_no'], $availableTitleNos, true))
                {
                    $result[$key][] = $books[$i];
                }
            }
        }

        return $result;
    }

    /**
     * @param $titleNo
     * @return mixed
     */
    public function GetBook($titleNo)
    {
        return $this->GetBookModel()->GetByTitleNo($titleNo);
    }

    /**
     * @param $teacherId
     * @param $titleNo
     * @return bool
     */
    public function IsRegisteredBook($teacherId, $titleNo)
    {
        return $this->GetTeacherBookModel()->IsRegisterd($teacherId, $titleNo);
    }

    // -------------------------------------------------------------------
    // Debug 用
    private ?BookModel $_BookModel = null;
    private function GetBookModel(): BookModel
    {
        if ($this->_BookModel != null) return $this->_BookModel;

        $this->_BookModel = new BookModel();

        return $this->_BookModel;
    }

    private ?TeacherBookModel $_TeacherBookModel = null;
    private function GetTeacherBookModel()
    {
        if ($this->_TeacherBookModel != null) return $this->_TeacherBookModel;

        $this->_TeacherBookModel = new TeacherBookModel();

        return $this->_TeacherBookModel;
    }

    private ?TeacherBookTempModel $_TeacherBookTempModel = null;
    private function GetTeacherBookTempModel()
    {
        if ($this->_TeacherBookTempModel != null) return $this->_TeacherBookTempModel;

        $this->_TeacherBookTempModel = new TeacherBookTempModel();

        return $this->_TeacherBookTempModel;
    }
}