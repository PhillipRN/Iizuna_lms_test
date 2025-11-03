<?php

namespace IizunaLMS\Controllers;

use IizunaLMS\Models\LogModel;

class LogController
{
    /**
     * @param $userId
     * @param $params
     * @return bool
     */
    public function AddCreateTestAutoLog($userId, $params)
    {
        return $this->AddLogWithType($userId, $params, LOG_TYPE_CREATE_TEST_AUTO);
    }

    /**
     * @param $userId
     * @param $params
     * @return bool
     */
    public function AddCreateTestManualLog($userId, $params)
    {
        return $this->AddLogWithType($userId, $params, LOG_TYPE_CREATE_TEST_MANUAL);
    }

    /**
     * @param $userId
     * @param $id
     * @param $isPreProcess
     * @return bool
     */
    public function AddFolderCopyLog($userId, $id, $isPreProcess)
    {
        $params = explode("_", $id);

        $titleNo = $params[0];
        $subNo = $params[1];

        $params = array(
            "title_no" => $titleNo,
            "sub_no" => $subNo,
        );

        $action = ($isPreProcess)? LOG_TYPE_FOLDER_COPY_PRE_PROCESS :  LOG_TYPE_FOLDER_COPY;

        return $this->AddLogWithType($userId, $params, $action);
    }

    /**
     * @param $userId
     * @param $params
     * @param $action
     * @return bool
     */
    public function AddLogWithType($userId, $params, $action)
    {
        // HTTP_USER_AGENT
        $userAgent = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : "";

        $parameterJson = json_encode($params, JSON_UNESCAPED_UNICODE);

        return $this->GetLogModel()->Add($userId, $action, $parameterJson, $userAgent);
    }

    /**
     * 単体テスト用としてモデルを注入できるようにする
     */
    private ?LogModel $_LogModel = null;

    private function GetLogModel(): ?LogModel
    {
        if ($this->_LogModel != null) return $this->_LogModel;

        $this->_LogModel = new LogModel();

        return $this->_LogModel;
    }

    /**
     * @param LogModel $LogModel
     */
    public function AttachLogModel(LogModel $LogModel)
    {
        $this->_LogModel = $LogModel;
    }
}