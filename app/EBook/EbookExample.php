<?php

namespace IizunaLMS\EBook;

use IizunaLMS\EBook\Models\EbookExampleModel;
use IizunaLMS\EBook\Requests\RequestParamEbookFlashCard;
use IizunaLMS\EBook\Requests\RequestParamEbookInformation;
use IizunaLMS\EBook\Requests\RequestParamEbookVoice;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;

class EbookExample
{
    /**
     * @param RequestParamEbookVoice $params
     * @return void
     */
    public function ShowEbookVoice(RequestParamEbookVoice $params)
    {
        if (!$params->IsValid()) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_EBOOK_VOICE_INVALID_PARAMETER);

        $records = (new EbookExampleModel())->GetVoiceRecords($params->titleNo, $params->page);

        $result = $this->ConvertVoiceResultData($records);

        DisplayJsonHelper::ShowAndExit($result);
    }

    /**
     * @param RequestParamEbookFlashCard $params
     * @return void
     */
    public function ShowFlashCard(RequestParamEbookFlashCard $params)
    {
        if (!$params->IsValid()) DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_EBOOK_FLASH_CARD_INVALID_PARAMETER);

        $result = $this->GenerateFlashCardResultData($params);

        DisplayJsonHelper::ShowAndExit($result);
    }

    /**
     * @param RequestParamEbookFlashCard $params
     * @return array[]
     */
    private function GenerateFlashCardResultData(RequestParamEbookFlashCard $params)
    {
        $records = (count($params->chapterArray) == 1)
            ? (new EbookExampleModel())->GetChapterRecords($params->titleNo, $params->chapterArray[0])
            : (new EbookExampleModel())->GetChapterRangeRecords(
                $params->titleNo,
                $params->chapterArray[0],
                $params->chapterArray[1]
            );

        $result = $this->ConvertFlashCardResultData($records, $params->number, $params->isRandom);

        if (isset($result['result']) && ($params->no_tag_ja || $params->no_tag_en)) {
            $result['result'] = $this->RemoveTagFromResult($result['result'], $params);
        }

        return $result;
    }

    /**
     * @param $records
     * @return array[]
     */
    private function ConvertVoiceResultData($records): array
    {
        if (empty($records))
        {
            return Error::GetErrorResultData(Error::ERROR_EBOOK_VOICE_NO_DATA);
        }

        $result = [];
        foreach ($records as $record)
        {
            $result[] = [
                'id' => $record['id'],
                'en' => $record['english'],
                'voice_file' => $record['voice']
            ];
        }

        return [
            'result' => $result
        ];
    }

    /**
     * @param $records
     * @param $number
     * @return array[]
     */
    private function ConvertFlashCardResultData($records, $number, $isRandom=true): array
    {
        if (empty($records))
        {
            return Error::GetErrorResultData(Error::ERROR_EBOOK_VOICE_NO_DATA);
        }

        if ($isRandom) shuffle($records);

        $result = [];
        $count = count($records);

        for ($i=0; $i<$count; ++$i)
        {
            $record = $records[$i];
            $result[] = [
                'id' => $record['id'],
                'en' => $record['english'],
                'ja' => $record['japanese'],
                'voice_file' => $record['voice']
            ];

            // 指定されている数で終了
            if ($i+1 == $number) break;
        }

        if ($number == 0 || count($result) == $number) {
            return [
                'result' => $result
            ];
        }
        else {
            $error = Error::GetErrorResultData(Error::ERROR_EBOOK_FLASH_CARD_NOT_ENOUGH);
            return array_merge(['result' => $result], $error);
        }
    }

    /**
     * @param $result
     * @param RequestParamEbookFlashCard $params
     * @return mixed
     */
    private function RemoveTagFromResult($result, RequestParamEbookFlashCard $params)
    {
        foreach ($result as $key => $data)
        {
            $result[$key] = [
                'id' => $data['id'],
                'en' => ($params->no_tag_en) ? strip_tags($data['en']) : $data['en'],
                'ja' => ($params->no_tag_ja) ? strip_tags($data['ja']) : $data['ja'],
                'voice_file' => $data['voice_file']
            ];
        }

        return $result;
    }
}