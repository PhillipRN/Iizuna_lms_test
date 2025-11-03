<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Errors\Error;
use IizunaLMS\Models\ChapterModel;

class ChapterController
{
    /**
     * @param $titleNo
     * @return array
     */
    public function CreateChapter($titleNo)
    {
        $chapters = $this->GetChapterModel($titleNo)->Gets();

        $result = array(
            "chapters" => array(),
            "error" => ERROR_NONE
        );

        if (!empty($chapters))
        {
            for ($i=0; $i<count($chapters); ++$i)
            {
                $chapter = $chapters[$i];
                $currentChapterData = array();

                // 現在のチャプターデータを集める。
                // チャプターの昇順でソートしているので、必ずチャプターは連続している
                for ($ci=$i; $ci<count($chapters); ++$ci)
                {
                    $data = $chapters[$ci];

                    // CHAPNOが同じなのにCHANPNAMEが違うということがあり、CHAPNAMEが違う場合は違うチャプター扱いになっている模様
                    // それで本家が動いているので止む無くそれに合わせる
                    if ($chapter["CHAPNO"] == $data["CHAPNO"] && $chapter["CHAPNAME"] == $data["CHAPNAME"])
                    {
                        $currentChapterData[] = array(
                            "text" => $data["SECNAME"],
                            "id" => "sec_id_" . $data["SECNO"]
                        );
                    }
                    else
                    {
                        $i = $ci - 1;
                        break;
                    }

                    $i = $ci;
                }

                if (count($currentChapterData) == 1 && empty($chapter["SECNAME"]))
                {
                    $result["chapters"][] = array(
                        "text" => $chapter["CHAPNAME"],
                        "id" => "sec_id_" . $chapter["SECNO"]
                    );
                }
                else
                {
                    $result["chapters"][] = array(
                        "text" => $chapter["CHAPNAME"],
                        "children" => $currentChapterData
                    );
                }
            }
        }
        else
        {
            $result["error"] = Error::ERROR_NO_CHAPTER_DATA;
        }

        return $result;
    }

    /**
     * 単体テスト用としてモデルを注入できるようにする
     */
    private ?ChapterModel $_ChapterModel = null;

    private function GetChapterModel($titleNo): ChapterModel
    {
        if ($this->_ChapterModel != null) return $this->_ChapterModel;

        if (empty($titleNo))
        {
            LogHelper::OutputErrorLog("Please set titleNo.");
            exit;
        }

        $this->_ChapterModel = new ChapterModel($titleNo);

        return $this->_ChapterModel;
    }

    public function AttachChapterModel(ChapterModel $ChapterModel)
    {
        $this->_ChapterModel = $ChapterModel;
    }
}