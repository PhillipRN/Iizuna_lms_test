<?php
require_once (__DIR__ . '/app/bootstrap.php');

use PHPMailer\PHPMailer\PHPMailer;

// 文字エンコードを指定
mb_language('uni');
mb_internal_encoding('UTF-8');

// インスタンスを生成（true指定で例外を有効化）
$mail = new PHPMailer(true);

// 文字エンコードを指定
$mail->CharSet = 'utf-8';

try {
    // デバッグ設定
     $mail->SMTPDebug = 2; // デバッグ出力を有効化（レベルを指定）
    // $mail->Debugoutput = function($str, $level) {echo "debug level $level; message: $str<br>";};

    // SMTPサーバの設定
    $mail->isSMTP();                          // SMTPの使用宣言
    $mail->Host       = MAIL_SMTP_HOST;   // SMTPサーバーを指定
    $mail->SMTPAuth   = true;                 // SMTP authenticationを有効化
    $mail->Username   = MAIL_SMTP_USER_NAME;   // SMTPサーバーのユーザ名
    $mail->Password   = MAIL_SMTP_PASSWORD;           // SMTPサーバーのパスワード
    $mail->SMTPSecure = MAIL_SMTP_SECURE;  // 暗号化を有効（tls or ssl）無効の場合はfalse
    $mail->Port       = MAIL_SMTP_PORT; // TCPポートを指定（tlsの場合は465や587）

    // 送受信先設定（第二引数は省略可）
    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME); // 送信者
    $mail->addAddress('mayarune@yahoo.co.jp', '受信者名');   // 宛先

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
    $mail->Subject = '件名';
    $mail->Body    = 'メッセージ本文';

    // 送信
    $mail->send();
} catch (Exception $e) {
    // エラーの場合
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}