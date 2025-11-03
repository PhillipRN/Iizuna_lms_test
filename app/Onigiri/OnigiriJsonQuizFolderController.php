<?php
namespace IizunaLMS\Onigiri;

use IizunaLMS\Models\OnigiriJsonQuizFolderModel;
use IizunaLMS\Models\OnigiriJsonQuizFolderViewModel;
use IizunaLMS\Onigiri\Data\OnigiriJsonQuizFolder;

class OnigiriJsonQuizFolderController
{
    private $folderMap;
    private $childrenFolderMap;

    function __construct($schoolId) {
        $this->InitializeFolderMap($schoolId);
    }

    /**
     * @param $schoolId
     * @return array|void
     */
    private function InitializeFolderMap($schoolId)
    {
        $this->folderMap = [];
        $this->childrenFolderMap = [];
        $records = (new OnigiriJsonQuizFolderViewModel())->GetsByKeyValue('school_id', $schoolId);

        foreach ($records as $record) {
            $this->folderMap[ $record['id'] ] = $record;
            $parentFolderId = $record['parent_folder_id'];

            if (!isset($this->childrenFolderMap[$parentFolderId])) $this->childrenFolderMap[$parentFolderId] = [];

            $this->childrenFolderMap[$parentFolderId][] = $record;
        }
    }

    public function GetFolder($folderId)
    {
        if ($folderId === 'all') return [
            'id' => 'all',
            'name' => OnigiriJsonQuizFolder::ALL_FOLDER_NAME
        ];

        if ($folderId == 0) return [
            'id' => 0,
            'name' => OnigiriJsonQuizFolder::ROOT_FOLDER_NAME
        ];

        if (isset($this->folderMap[$folderId])) return $this->folderMap[$folderId];

        return (new OnigiriJsonQuizFolderModel())->GetById($folderId);
    }

    /**
     * @param $parentFolderId
     * @return array|mixed
     */
    public function GetChildrenFolders($parentFolderId)
    {
        if (!isset($this->childrenFolderMap[$parentFolderId])) return [];

        return $this->childrenFolderMap[$parentFolderId];
    }

    /**
     * $targetFolderId が $checkFolderId の子孫かどうかチェックする
     * @param $checkFolderId
     * @param $targetFolderId
     * @return bool
     */
    public function CheckDescendants($checkFolderId, $targetFolderId)
    {
        if (!isset($this->folderMap[$targetFolderId])) return false;

        $targetFolder = $this->folderMap[$targetFolderId];
        $parentFolderId = $targetFolder['parent_folder_id'];

        if ($parentFolderId == 0) return false;
        else if ($checkFolderId == $parentFolderId) return true;

        return $this->CheckDescendants($checkFolderId, $parentFolderId);
    }

    /**
     * @return string
     */
    public function CreateFolderListHtml($enableRootClick=false)
    {
        $rootHtml = ($enableRootClick)
            ? '<li><a href="javascript:void(0)" data-id="0" data-parent_folder_id="0" data-teacher_id="0" class="folderNode">' . OnigiriJsonQuizFolder::ROOT_FOLDER_NAME . '</a>'
            : OnigiriJsonQuizFolder::ROOT_FOLDER_NAME;

        $html = '<ul class="folderTree"><li>' . $rootHtml;
        $html .= $this->CreateChildrenHtml(0);
        $html .= '</li></ul>';

        return $html;
    }

    /**
     * リスト表示に用いる子フォルダHTMLを生成する
     * @param $parentFolderId
     * @return string
     */
    private function CreateChildrenHtml($parentFolderId): string
    {
        if (!isset($this->childrenFolderMap[$parentFolderId])) return '';

        $tempList = [];
        $sortKeyNames = [];

        $childrenFolders = $this->childrenFolderMap[$parentFolderId];

        for ($i=0; $i<count($childrenFolders); ++$i)
        {
            $folder = $childrenFolders[$i];

            $tempHtml = '<li><a href="javascript:void(0)" data-id="' . $folder['id'] . '" data-parent_folder_id="' . $folder['parent_folder_id'] . '" data-teacher_id="' . $folder['teacher_id'] . '" class="folderNode">' . $folder['name'] . '</a>';

            // 自分を親にしている子がいれば処理する
            if (isset($this->childrenFolderMap[ $folder['id'] ]))
            {
                $tempHtml .= $this->CreateChildrenHtml($folder['id']);
            }
            $tempHtml .= '</li>';

            $sortKeyNames[] = $folder['name'];
            $tempList[] = $tempHtml;
        }

        array_multisort($sortKeyNames, SORT_ASC, $tempList);

        return '<ul>'
            . implode('', $tempList)
            . '</ul>';
    }

    /**
     * @return string
     */
    public function CreateFolderListOptions($selectedId=0)
    {
        $html = '<option value="0">' . OnigiriJsonQuizFolder::ROOT_FOLDER_NAME . '</option>';
        $html .= $this->CreateChildrenOptions(0, 1, $selectedId);

        return $html;
    }

    private function CreateChildrenOptions($parentFolderId, $level, $selectedId): string
    {
        if (!isset($this->childrenFolderMap[$parentFolderId])) return '';

        $prefix = str_repeat('　', $level);
        $tempList = [];
        $sortKeyNames = [];

        $childrenFolders = $this->childrenFolderMap[$parentFolderId];

        for ($i=0; $i<count($childrenFolders); ++$i)
        {
            $folder = $childrenFolders[$i];
            $selected = ($folder['id'] == $selectedId) ? 'selected' : '';

            $tempHtml = '<option value="' . $folder['id'] . '" '. $selected .'>' . $prefix . $folder['name'] . '</option>';

            // 自分を親にしている子がいれば処理する
            if (isset($this->childrenFolderMap[ $folder['id'] ]))
            {
                $tempHtml .= $this->CreateChildrenOptions($folder['id'], $level + 1, $selectedId);
            }

            $sortKeyNames[] = $folder['name'];
            $tempList[] = $tempHtml;
        }

        array_multisort($sortKeyNames, SORT_ASC, $tempList);

        return implode('', $tempList);
    }
}