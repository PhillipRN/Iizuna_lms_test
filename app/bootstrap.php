<?php
// 設定ファイルがないと動かないので die させる
if (!file_exists(__DIR__ . '/config.ini')) {
    die('Config file not found.');
}

// NOTE iframe 対応
$cookieParams = session_get_cookie_params();
$cookieParams['samesite'] = 'None';
$cookieParams['secure'] = true;
session_set_cookie_params($cookieParams);

session_start();

// 設定ファイル読み込み
$setting = parse_ini_file(__DIR__ . '/config.ini');
foreach ($setting as $key => $value)
{
    define($key, $value);
}

// 設定ファイルに DEBUG_MODE が指定されていないと DEBUG_MODE の条件判定をすり抜けてしまうので
// すり抜けないように 0 を定義する
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', 0);
}

if (!defined('DISPLAY_ERROR_ALL')) {
    define('DISPLAY_ERROR_ALL', 0);
}

if (DEBUG_MODE) {
    ini_set('display_errors', "On");

    if (DISPLAY_ERROR_ALL) {
        error_reporting(E_ALL);
    }
    else {
        error_reporting(E_ALL & ~ E_DEPRECATED & ~ E_USER_DEPRECATED & ~ E_NOTICE);
    }
}

require_once (__DIR__ . '/../vendor/autoload.php');
require_once (__DIR__ . '/define.php');

if(isset($_SERVER['REQUEST_METHOD'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        // プリフライト対応
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Headers: *, Authorization, Content-Type');
        exit;
    }
}