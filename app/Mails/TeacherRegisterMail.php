<?php

namespace IizunaLMS\Mails;

use Exception;
use IizunaLMS\Books\BookLoader;
use IizunaLMS\Messages\Message;
use IizunaLMS\Models\SchoolModel;
use PHPMailer\PHPMailer\PHPMailer;

class TeacherRegisterMail
{
    private $messageData = [];

    public function __construct() {
        $this->messageData = Message::GetMessageData()['teacher_register'];
    }

    public function Send($params)
    {
        $mailTitle = $this->messageData['mail_title'];
        $mailBody = $this->GenerateMailBody($params);
        $userName = "{$params['name_1']}{$params['name_2']}様";
        $userMail = $params['mail'];

        // 文字エンコードを指定
        mb_language('uni');
        mb_internal_encoding('UTF-8');

        // インスタンスを生成（true指定で例外を有効化）
        $mail = new PHPMailer(true);

        // 文字エンコードを指定
        $mail->CharSet = 'utf-8';

        try {
            // SMTPサーバの設定
            $mail->isSMTP();                          // SMTPの使用宣言
            $mail->Host       = MAIL_SMTP_HOST;       // SMTPサーバーを指定
            $mail->SMTPAuth   = true;                 // SMTP authenticationを有効化
            $mail->Username   = MAIL_SMTP_USER_NAME;  // SMTPサーバーのユーザ名
            $mail->Password   = MAIL_SMTP_PASSWORD;   // SMTPサーバーのパスワード
            $mail->SMTPSecure = MAIL_SMTP_SECURE;     // 暗号化を有効（tls or ssl）無効の場合はfalse
            $mail->Port       = MAIL_SMTP_PORT;       // TCPポートを指定（tlsの場合は465や587）

            // 送受信先設定（第二引数は省略可）
            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME); // 送信者
            $mail->addAddress($userMail, $userName);   // 宛先

            if (defined('MAIL_REPLY_ADDRESS') && !empty(MAIL_REPLY_ADDRESS) && defined('MAIL_REPLY_NAME') && !empty(MAIL_REPLY_NAME)) {
                $mail->addReplyTo(MAIL_REPLY_ADDRESS, MAIL_REPLY_NAME); // 返信先
            }

            if (defined('MAIL_BCC') && !empty(MAIL_BCC)) {
                $mail->addBCC(MAIL_BCC);
            }

            if (defined('MAIL_RETURN_PATH') && !empty(MAIL_RETURN_PATH)) {
                $mail->Sender = MAIL_RETURN_PATH; // Return-path
            }

            // 送信内容設定
            $mail->Subject = $mailTitle;
            $mail->Body    = $mailBody;

            // 送信
            $mail->send();
        } catch (Exception $e) {
            // エラーの場合
            echo "メッセージの送信に失敗しました. Mailer Error: {$mail->ErrorInfo}";
            exit;
        }
    }

    /**
     * @param $params
     * @return array|string|string[]|null
     */
    private function GenerateMailBody($params)
    {
        $mailBody = $this->messageData['mail_body'];

        $schoolName = $this->GetSchoolName($params['school_id']);

        $isEOnigiri = !empty($params['is_e_onigiri']) ? 1 : 0;
        $bookList = $this->GetBookList($params['title_no'], $params['teacher_ebook'], $isEOnigiri);

        $mailBody = preg_replace('/##school_pref##/', $params['school_pref'], $mailBody);
        $mailBody = preg_replace('/##school_zip##/', $params['school_zip'], $mailBody);
        $mailBody = preg_replace('/##school_name##/', $schoolName, $mailBody);
        $mailBody = preg_replace('/##school_address##/', $params['school_address'], $mailBody);
        $mailBody = preg_replace('/##phone##/', $params['phone'], $mailBody);
        $mailBody = preg_replace('/##name_1##/', $params['name_1'], $mailBody);
        $mailBody = preg_replace('/##name_2##/', $params['name_2'], $mailBody);
        $mailBody = preg_replace('/##kana_1##/', $params['kana_1'], $mailBody);
        $mailBody = preg_replace('/##kana_2##/', $params['kana_2'], $mailBody);
        $mailBody = preg_replace('/##mail##/', $params['mail'], $mailBody);
        $mailBody = preg_replace('/##book_list##/', $bookList, $mailBody);

        return $mailBody;
    }

    /**
     * @param $schoolId
     * @return mixed
     */
    private function GetSchoolName($schoolId)
    {
        $school = (new SchoolModel())->GetById($schoolId);
        return $school['name'];
    }

    /**
     * @param $titleNos
     * @param $ebookTitleNos
     * @param $isEOnigiri
     * @return string
     */
    private function GetBookList($titleNos, $ebookTitleNos, $isEOnigiri): string
    {
        if (empty($titleNos) && empty($isEOnigiri)) return 'なし';

        $tempBooks = [];

        if (!empty($isEOnigiri)) $tempBooks[] = '・e-ONIGIRI英単語';

        if (!empty($ebookTitleNos))
        {
            $books = (new BookLoader())->GetBookDetails($ebookTitleNos);
            $bookMap = [];
            foreach ($books as $book) $bookMap[$book['title_no']] = $book;

            foreach ($ebookTitleNos as $ebookTitleNo)
            {
                $book = $bookMap[$ebookTitleNo];
                $tempBooks[] = "・e-ONIGIRI参考書 {$book['name']}";
            }
        }

        if (!empty($titleNos))
        {
            $books = (new BookLoader())->GetBookDetails($titleNos);

            foreach ($books as $book) $tempBooks[] = "・{$book['name']}";
        }

        return implode("\n", $tempBooks);
    }
}