<?php

use PHPUnit\Framework\TestCase;

class TestBase extends TestCase
{
    /**
     * privateメソッドを実行する.
     * @param mixed $controller テスト対象のクラス
     * @param string $methodName privateメソッドの名前
     * @param array $param privateメソッドに渡す引数
     * @return mixed 実行結果
     * @throws \ReflectionException 引数のクラスがない場合に発生.
     */
    public static function doMethod($controller, string $methodName, array $param)
    {
        // ReflectionClassをテスト対象のクラスをもとに作る.
        $reflection = new \ReflectionClass($controller);
        // メソッドを取得する.
        $method = $reflection->getMethod($methodName);
        // アクセス許可をする.
        $method->setAccessible(true);
        // メソッドを実行して返却値をそのまま返す.
        return $method->invokeArgs($controller, $param);
    }
}