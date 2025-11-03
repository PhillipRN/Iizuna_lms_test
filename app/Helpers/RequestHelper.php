<?php

namespace IizunaLMS\Helpers;

class RequestHelper
{
    /**
     * POSTの値を配列で返す
     * @param $ignoreKeys
     * @return array
     */
    public static function GetPostParams($ignoreKeys=null)
    {
        $params = array();

        foreach ($_POST as $key => $val)
        {
            if (!empty($ignoreKeys) && in_array($key, $ignoreKeys)) continue;

            $params[$key] = $val;
        }

        return $params;
    }

    /**
     * POSTされてきたJsonを取得する
     * @return mixed
     */
    public static function GetPostJsonParams()
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true);
    }

    /**
     * @return mixed|string
     */
    public static function GetIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipArray = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return $ipArray[0];
        } else if (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        } else {
            return "UNKNOWN";
        }
    }

    // 以下 250322 追加 --------------------------------------------------------

    /**
     * リクエストパラメータを取得し、フィルタリングする
     *
     * @param array $definition パラメータ定義 ['パラメータ名' => ['filter' => フィルタータイプ, 'default' => デフォルト値]]
     * @param string $method リクエストメソッド ('GET', 'POST', 'REQUEST')
     * @return array フィルタリングされたパラメータの連想配列
     */
    public static function getParameters(array $definition, string $method = 'GET'): array
    {
        $source = self::getSource($method);
        $result = [];

        foreach ($definition as $paramName => $options) {
            $filter = $options['filter'] ?? FILTER_DEFAULT;
            $default = $options['default'] ?? null;

            if (isset($source[$paramName])) {
                $value = self::filterValue($source[$paramName], $filter, $options);
                $result[$paramName] = $value !== false ? $value : $default;
            } else {
                $result[$paramName] = $default;
            }
        }

        return $result;
    }

    /**
     * 単一のリクエストパラメータを取得する
     *
     * @param string $paramName パラメータ名
     * @param int $filter フィルタータイプ (FILTER_* 定数)
     * @param mixed $default デフォルト値
     * @param string $method リクエストメソッド
     * @return mixed フィルタリングされたパラメータ値
     */
    public static function getParameter(string $paramName, int $filter = FILTER_DEFAULT, $default = null, string $method = 'GET')
    {
        $source = self::getSource($method);

        if (!isset($source[$paramName])) {
            return $default;
        }

        $value = self::filterValue($source[$paramName], $filter);
        return $value !== false ? $value : $default;
    }

    /**
     * CSRFトークンを検証する
     *
     * @param string $tokenFieldName CSRFトークンのフィールド名
     * @param string $method リクエストメソッド
     * @return bool 検証結果
     */
    public static function validateCSRFToken(string $tokenFieldName = '_csrf', string $method = 'POST'): bool
    {
        $source = self::getSource($method);

        if (!isset($source[$tokenFieldName])) {
            return false;
        }

        return CSRFHelper::ValidateKey($source[$tokenFieldName]);
    }

    /**
     * リクエストメソッドに応じたソース配列を取得する
     *
     * @param string $method リクエストメソッド
     * @return array ソース配列
     */
    private static function getSource(string $method): array
    {
        switch (strtoupper($method)) {
            case 'GET':
                return $_GET;
            case 'POST':
                return $_POST;
            case 'REQUEST':
                return $_REQUEST;
            default:
                return $_GET;
        }
    }

    /**
     * 値をフィルタリングする
     *
     * @param mixed $value フィルタリングする値
     * @param int $filter フィルタータイプ
     * @param array $options フィルターオプション
     * @return mixed フィルタリングされた値
     */
    private static function filterValue($value, int $filter, array $options = [])
    {
        $filterOptions = $options['options'] ?? [];
        $flags = $options['flags'] ?? 0;

        if (is_array($value) && $filter === FILTER_VALIDATE_INT) {
            // 配列の各要素に対してフィルターを適用
            return array_map(function($item) use ($filter, $filterOptions, $flags) {
                return filter_var($item, $filter, [
                    'options' => $filterOptions,
                    'flags' => $flags
                ]);
            }, $value);
        }

        return filter_var($value, $filter, [
            'options' => $filterOptions,
            'flags' => $flags
        ]);
    }

    /**
     * リクエストがAJAXかどうかを判定する
     *
     * @return bool AJAXリクエストの場合はtrue
     */
    public static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * リクエストメソッドを取得する
     *
     * @return string HTTPメソッド (GET, POST, etc.)
     */
    public static function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }
}