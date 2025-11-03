<?php

namespace IizunaLMS\EBook;

use IizunaLMS\Books\Book;
use IizunaLMS\Models\BookModel;
use IizunaLMS\Models\TeacherBookModel;
use IizunaLMS\Models\TeacherBookTempModel;
use IizunaLMS\Models\TeacherEbookModel;

class TeacherEBookLoader
{
        /**
     * @param $teacherId
     * @return array
     */
    public function GetTeacherBookTitleNos($teacherId)
    {
        $teacherBooks = (new TeacherEbookModel())->GetsByKeyValue('teacher_id', $teacherId);

        $result = [];

        foreach ($teacherBooks as $teacherBook)
        {
            $result[] = $teacherBook['title_no'];
        }

        return $result;
    }
}