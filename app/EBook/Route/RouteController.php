<?php

namespace IizunaLMS\EBook\Route;

use IizunaLMS\EBook\EbookExample;
use IizunaLMS\EBook\EbookQuiz;
use IizunaLMS\EBook\EbookSchool;
use IizunaLMS\EBook\Requests\RequestParamEbookCodeCountUp;
use IizunaLMS\EBook\Requests\RequestParamEbookDailyQuiz;
use IizunaLMS\EBook\Requests\RequestParamEbookFlashCard;
use IizunaLMS\EBook\Requests\RequestParamEbookInformation;
use IizunaLMS\EBook\Requests\RequestParamEbookQuiz;
use IizunaLMS\EBook\Requests\RequestParamEbookSchool;
use IizunaLMS\EBook\Requests\RequestParamEbookVoice;
use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Students\StudentRegister;

class RouteController
{
    const MODE_QUIZ = 'quiz';
    const MODE_QUIZ_TODAY = 'quiz_today';
    const MODE_FLASH_CARD = 'flash_card';
    const MODE_VOICE = 'voice';
    const MODE_EBOOK_INFORMATION = 'ebook_information';
    const MODE_SCHOOL_BOOK = 'school_book';
    const MODE_CODE_COUNT_UP = 'code_count_up';
    const MODE_IIZUNA_LMS_USER_REGISTER = 'iizuna_lms_user_register';

    public static function Routing($mode)
    {
        switch ($mode){
            case self::MODE_QUIZ:
                (new EbookQuiz())->ShowQuiz(new RequestParamEbookQuiz());
                break;

            case self::MODE_QUIZ_TODAY:
                (new EbookQuiz())->ShowDailyQuiz(new RequestParamEbookDailyQuiz());
                break;

            case self::MODE_FLASH_CARD:
                (new EbookExample())->ShowFlashCard(new RequestParamEbookFlashCard());
                break;

            case self::MODE_VOICE:
                (new EbookExample())->ShowEbookVoice(new RequestParamEbookVoice());
                break;

            case self::MODE_EBOOK_INFORMATION:
                (new EbookQuiz())->ShowEbookInformation(new RequestParamEbookInformation());
                break;

            case self::MODE_IIZUNA_LMS_USER_REGISTER:
                self::IizunaLmsUserRegister();
                break;

            case self::MODE_SCHOOL_BOOK:
                DisplayJsonHelper::ShowAndExit(
                    (new EbookSchool())->GetBookStatuses(new RequestParamEbookSchool())
                );
                break;

            case self::MODE_CODE_COUNT_UP:
                DisplayJsonHelper::ShowAndExit(
                    (new EbookSchool())->CodeCountUp(new RequestParamEbookCodeCountUp())
                );
                break;

            default:
                DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_EBOOK_MODE_UNKNOWN);
                break;
        }
    }

    /**
     * @return void
     */
    private static function IizunaLmsUserRegister()
    {
        Error::ShowErrorJson(Error::ERROR_STUDENT_REGISTER_FAILED_AUTHORIZATION_KEY);
        exit;

//        // NOTE: Ebook 対応前の実装ロジック共有の為書き方が異なる
//        $params = RequestHelper::GetPostParams();
//
//        // 必要なパラメータがない場合はエラー
//        if (empty($params['lms_code']) || empty($params['user_id']))
//        {
//            Error::ShowErrorJson(Error::ERROR_STUDENT_REGISTER_INVALID_PARAMETER);
//            exit;
//        }
//
//        // onigiri_user_id と明確に分けるため、名称を変更する
//        $params['ebook_user_id'] = $params['user_id'];
//        unset($params['user_id']);
//
//        $result = (new StudentRegister())->Register($params);
//
//        if (isset($result['error_code']))
//        {
//            Error::ShowErrorJson($result['error_code']);
//            exit;
//        }
//
//        // 認証キー返す
//        $result = [
//            'result' => 'OK',
//            'authorization_key' => $result['authorization_key']
//        ];
//
//        DisplayJsonHelper::ShowAndExit($result);
    }
}