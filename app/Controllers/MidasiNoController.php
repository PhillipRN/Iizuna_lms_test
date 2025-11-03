<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Models\MidasiNoModel;
use IizunaLMS\Helpers\LogHelper;

class MidasiNoController
{
    /**
     * @param $titleNo
     * @return false|string
     */
    public function CreateMidasiNo($titleNo)
    {
        $midasiNos = $this->GetMidasiNoModel($titleNo)->Gets();

        $result = array(
            "error" => ERROR_NONE
        );

        if (!empty($midasiNos))
        {
            for ($i=0; $i<count($midasiNos); ++$i)
            {
                $midasiNo = $midasiNos[$i];

                $result["midasinos"][] = array(
                    "text" => $midasiNo["NAME"],
                    "id" => "midasino_" . $midasiNo["MIDASINO"]
                );
            }
        }
        else
        {
            $result = array(
                "error" => ERROR_UNKNOWN_DRIVE_ID
            );
        }

        return $result;
    }

    /**
     * 単体テスト用としてモデルを注入できるようにする
     */
    private ?MidasiNoModel $_MidasiNoModel = null;

    private function GetMidasiNoModel($titleNo): ?MidasiNoModel
    {
        if ($this->_MidasiNoModel != null) return $this->_MidasiNoModel;

        if (empty($titleNo))
        {
            LogHelper::OutputErrorLog("Please set titleNo.");
            exit;
        }

        $this->_MidasiNoModel = new MidasiNoModel($titleNo);

        return $this->_MidasiNoModel;
    }

    /**
     * @param MidasiNoModel $MidasiNoModel
     */
    public function AttachMidasiNoModel(MidasiNoModel $MidasiNoModel)
    {
        $this->_MidasiNoModel = $MidasiNoModel;
    }
}