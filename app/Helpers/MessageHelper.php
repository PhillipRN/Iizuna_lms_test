<?php

namespace IizunaLMS\Helpers;

class MessageHelper
{
    /**
     * @param $code
     * @return string
     */
    public static function GetMessage($code)
    {
        switch ($code)
        {
            case REGISTER_STATUS_REGISTERED:
                return 'ユーザーを登録しました';

            case REGISTER_STATUS_UPDATED:
                return 'ユーザーを更新しました';

            case REGISTER_STATUS_DELETED:
                return 'ユーザーを削除しました';

            case REGISTER_STATUS_SKIP:
                return 'ユーザーの登録をスキップしました';

            case REGISTER_STATUS_SKIP_WITHOUT_LOGIN_ID:
                return 'login_id が指定されていないためユーザーの登録をスキップしました';

            case REGISTER_STATUS_SKIP_WITHOUT_SCHOOL_ID:
                return 'school_id が指定されていない、又は学校名から school_id が取得できないためユーザーの登録をスキップしました';
        }

        return '未定義のコードです';
    }

    /**
     * @param $code
     * @return string
     */
    public static function GetErrorMessage($code)
    {
        switch ($code)
        {
            case ERROR_LOGIN_FAILED:
                return 'ログインに失敗しました。ログインIDかパスワードが間違っています。';

            case ERROR_LOGIN_BLOCK_IP:
                return 'ログインの失敗が一定回数を越えたため、ログインはブロックされています';

            case ERROR_LOGIN_LIMIT_IP:
                return 'ログインの失敗が一定回数を越えたため、このIPからのログインは制限されています。5分後に再度お試しください。';

            case ERROR_LOGIN_LIMIT_USER_ID:
                return 'ログインの失敗が一定回数を越えたため、このユーザーのログインは制限されています。5分後に再度お試しください。';


            // テスト作成
//            case ERROR_CREATE_PROCESS_KEY:
//                return 'テスト作成の準備に失敗しました';

//            case ERROR_LACK_OF_TOTAL:
//                return '指定された範囲では問題数が少ないため、テストの作成ができません。出題の範囲を広げて作成して下さい。';

//            case ERROR_INVALID_TOTAL:
//                return 'TOTALの値が不正です';

//            case ERROR_NO_DAIMON_DATA:
//                return '大問データがありません';

//            case ERROR_NO_CHAPTER_DATA:
//                return '章データがありません';

//            case ERROR_NO_MIDASI_DATA:
//                return '見出しデータがありません';


            // GAS
            case ERROR_GAS_NOT_FOUND_GAS_URL:
                return '接続先の設定を取得できませんでした。';


            // ユーザー登録
            case ERROR_ADMIN_USER_ADD_FAILED:
                return 'ユーザーの追加に失敗しました';

            case ERROR_ADMIN_USER_UPDATE_FAILED:
                return 'ユーザーの更新に失敗しました';

            case ERROR_ADMIN_USER_DELETE_FAILED:
                return 'ユーザーの削除に失敗しました';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_INVALID:
                return 'ユーザー登録のパラメータが不正です';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_LOGIN_ID:
                return 'ログインIDがセットされていません';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_PASSWORD:
                return 'パスワードがセットされていません';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_PREF:
                return '都道府県がセットされていません';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_SCHOOL:
                return '学校がセットされていません';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_SCH_ZIP:
                return '学校郵便番号がセットされていません';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_SCH_ADD:
                return '学校住所がセットされていません';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_SCH_PHONE:
                return '学校電話番号がセットされていません';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_NAME_SEI:
                return '姓がセットされていません';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_NAME_MEI:
                return '名がセットされていません';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_KANA_SEI:
                return 'セイがセットされていません';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_KANA_MEI:
                return 'メイがセットされていません';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_MAIL:
                return 'メールアドレスがセットされていません';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_MAIL_INVALID:
                return '正しいメールアドレスを入力してください';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_PHONE_INVALID:
                return '正しい学校電話番号を入力してください';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_ZIP_INVALID:
                return '正しい学校郵便番号を入力してください';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_NOT_SET_GACCOUNT:
                return 'Googleアカウントがセットされていません';

            case ERROR_ADMIN_USER_REGISTER_PARAMETER_REGISTERED_LOGIN_ID:
                return 'そのログインIDは既に登録されています';

//            case ERROR_ADMIN_USER_UPLOAD_FILE_FAILED:
//                return 'ファイルをアップロードできませんでした';
//
//            case ERROR_ADMIN_USER_UPLOAD_FILE_NOT_CSV:
//                return 'CSVファイルのみ対応しています';

            // 書籍キー登録
            case ERROR_REGISTRATION_KEY_NOT_FOUND:
                return '書籍キーが有効なキーではありません';

            case ERROR_REGISTRATION_KEY_ALREADY_REGISTERED:
                return 'その書籍キーは既に登録されています';

            case ERROR_REGISTRATION_KEY_DISABLED:
                return 'その書籍キーは無効になっています';

            case ERROR_REGISTRATION_KEY_FAILED:
                return '書籍キーの登録に失敗しました';

            case ERROR_REGISTRATION_KEY_ALREADY_HAVING_BOOK:
                return '既に所持している書籍のキーです';

            // 書籍キー登録
            case ERROR_ADMIN_REGISTRATION_ADD_FAILED:
                return '書籍キーの追加に失敗しました';

            case ERROR_ADMIN_REGISTRATION_UPDATE_FAILED:
                return '書籍キーの更新に失敗しました';

            case ERROR_ADMIN_REGISTRATION_DELETE_FAILED:
                return '書籍キーの削除に失敗しました';

            case ERROR_ADMIN_REGISTRATION_REGISTER_PARAMETER_INVALID:
                return '書籍キー登録のパラメータが不正です';

            case ERROR_ADMIN_USER_REGISTER_NOT_CHECK_PRIVACY:
                return '個人情報の取り扱いに同意するにチェックをいれてください';

            case ERROR_ADMIN_USER_REGISTER_NOT_CHECK_TERMS:
                return '利用規約に同意するにチェックをいれてください';
                
            // Json Quiz
            case ERROR_JSON_QUIZ_ADD_FAILED:
                return 'テストを作成できませんでした。';

            case ERROR_JSON_QUIZ_GET_LAST_INSERT_ID_FAILED:
                return '作成したテストIDの取得ができませんでした。';

            case ERROR_JSON_QUIZ_INVALID_URL:
                return 'URLが不正です。';

            case ERROR_JSON_QUIZ_PERMISSION:
                return 'アクセスする権限がありません。';

            case ERROR_JSON_QUIZ_NOT_RELEASE_OR_PERMISSION:
                return '公開されていないか、アクセスする権限がありません。';

            case ERROR_JSON_QUIZ_NO_DATA:
                return 'データがみつかりませんでした。';

            case ERROR_JSON_QUIZ_RESULT_INVALID_PARAMETER:
                return 'パラメータが不正です。';

            case ERROR_JSON_QUIZ_RESULT_ADD_FAILED:
                return '結果の登録に失敗しました。';

            case ERROR_JSON_QUIZ_RESULT_UPDATE_JSON_QUIZ:
                return 'テスト情報の更新に失敗しました。';
        }

        return '未定義のエラーです';
    }

    /**
     * @param $codes
     * @return array
     */
    public static function GetErrorMessages($codes)
    {
        $result = [];

        for ($i=0; $i<count($codes); ++$i)
        {
            $result[] = self::GetErrorMessage($codes[$i]);
        }

        return $result;
    }
}