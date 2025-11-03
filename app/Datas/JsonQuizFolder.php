<?php
namespace IizunaLMS\Datas;

class JsonQuizFolder
{
    const ALL_FOLDER_NAME = '全てのフォルダ';
    const ROOT_FOLDER_NAME = 'ルート';

    public $id;
    public $parent_folder_id;
    public $teacher_id;
    public $name;

    function __construct($data) {
        $this->parent_folder_id = $data['parent_folder_id'] ?? 0;
        $this->teacher_id = $data['teacher_id'] ?? null;
        $this->name = $data['name'] ?? null;
    }
}