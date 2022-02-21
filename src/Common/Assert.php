<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/8/23
 * Time: 9:48
 */

namespace EsSwoole\Base\Common;

use EsSwoole\Base\Exception\LogicAssertException;

class Assert
{
    public static function assertEquals($expected, $actual, string $message, $contrastType = false, $errCode = 0)
    {
        $result = static::equals($expected, $actual, $contrastType);
        static::throwException($result, $message, $errCode);
    }

    public static function assertNotEquals($expected, $actual, string $message, $contrastType = false, $errCode = 0)
    {
        $result = static::equals($expected, $actual, $contrastType);
        static::throwException(!$result, $message, $errCode);
    }

    public static function assertSuccessCode($code, string $message, $errCode = 0)
    {
        $result = static::equals($code, config('statusCode.success'), true);
        static::throwException(!$result, $message, $errCode);
    }

    public static function assertTrue($condition, string $message, $errCode = 0)
    {
        static::throwException($condition === true, $message, $errCode);
    }

    public static function assertFalse($condition, string $message, $errCode = 0)
    {
        static::throwException($condition === false, $message, $errCode);
    }

    public static function assertGreaterThan($expected, $actual, string $message, $errCode = 0)
    {
        static::throwException($actual > $expected, $message, $errCode);
    }

    public static function assertLessThan($expected, $actual, string $message, $errCode = 0)
    {
        static::throwException($actual < $expected, $message, $errCode);
    }

    public static function assertEmpty($expected, string $message, $errCode = 0)
    {
        static::throwException(empty($expected), $message, $errCode);
    }

    public static function assertNotEmpty($expected, string $message, $errCode = 0)
    {
        static::throwException(!empty($expected), $message, $errCode);
    }

    protected static function equals($expected, $actual, $contrastType = false)
    {
        if ($contrastType === true) {
            return $expected === $actual;
        }

        return $expected == $actual;
    }

    protected static function throwException($bool, $message, $errCode = 0)
    {
        if ($bool === false) {
            throw new LogicAssertException($message, $errCode);
        }
    }
}