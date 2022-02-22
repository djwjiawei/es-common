<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/8/23
 * Time: 9:48
 */

namespace EsSwoole\Base\Common;

use EsSwoole\Base\Exception\LogicAssertException;

/**
 * Class Assert
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class Assert
{
    /**
     * 断言等于
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     * @param bool   $contrastType
     * @param int    $errCode
     *
     * @throws LogicAssertException
     * User: dongjw
     * Date: 2022/2/22 15:22
     */
    public static function assertEquals($expected, $actual, string $message, $contrastType = false, $errCode = 0)
    {
        $result = static::equals($expected, $actual, $contrastType);
        static::throwException($result, $message, $errCode);
    }

    /**
     * 断言不等于
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     * @param bool   $contrastType
     * @param int    $errCode
     *
     * @throws LogicAssertException
     * User: dongjw
     * Date: 2022/2/22 15:23
     */
    public static function assertNotEquals($expected, $actual, string $message, $contrastType = false, $errCode = 0)
    {
        $result = static::equals($expected, $actual, $contrastType);
        static::throwException(!$result, $message, $errCode);
    }

    /**
     * 断言成功状态码
     *
     * @param int    $code
     * @param string $message
     * @param int    $errCode
     *
     * @throws LogicAssertException
     * User: dongjw
     * Date: 2022/2/22 15:26
     */
    public static function assertSuccessCode($code, string $message, $errCode = 0)
    {
        $result = static::equals($code, config('statusCode.success'), true);
        static::throwException(!$result, $message, $errCode);
    }

    /**
     * 断言是否等于true
     *
     * @param bool   $condition
     * @param string $message
     * @param int    $errCode
     *
     * @throws LogicAssertException
     * User: dongjw
     * Date: 2022/2/22 15:27
     */
    public static function assertTrue($condition, string $message, $errCode = 0)
    {
        static::throwException($condition === true, $message, $errCode);
    }

    /**
     * 断言是否等于false
     *
     * @param bool   $condition
     * @param string $message
     * @param int    $errCode
     *
     * @throws LogicAssertException
     * User: dongjw
     * Date: 2022/2/22 15:28
     */
    public static function assertFalse($condition, string $message, $errCode = 0)
    {
        static::throwException($condition === false, $message, $errCode);
    }

    /**
     * 断言大于
     *
     * @param int    $expected
     * @param int    $actual
     * @param string $message
     * @param int    $errCode
     *
     * @throws LogicAssertException
     * User: dongjw
     * Date: 2022/2/22 15:29
     */
    public static function assertGreaterThan($expected, $actual, string $message, $errCode = 0)
    {
        static::throwException($actual > $expected, $message, $errCode);
    }

    /**
     * 断言小于
     *
     * @param int    $expected
     * @param int    $actual
     * @param string $message
     * @param int    $errCode
     *
     * @throws LogicAssertException
     * User: dongjw
     * Date: 2022/2/22 15:29
     */
    public static function assertLessThan($expected, $actual, string $message, $errCode = 0)
    {
        static::throwException($actual < $expected, $message, $errCode);
    }

    /**
     * 断言是否为空
     *
     * @param mixed  $expected
     * @param string $message
     * @param int    $errCode
     *
     * @throws LogicAssertException
     * User: dongjw
     * Date: 2022/2/22 15:30
     */
    public static function assertEmpty($expected, string $message, $errCode = 0)
    {
        static::throwException(empty($expected), $message, $errCode);
    }

    /**
     * 断言是否不为空
     *
     * @param mixed  $expected
     * @param string $message
     * @param int    $errCode
     *
     * @throws LogicAssertException
     * User: dongjw
     * Date: 2022/2/22 15:31
     */
    public static function assertNotEmpty($expected, string $message, $errCode = 0)
    {
        static::throwException(!empty($expected), $message, $errCode);
    }

    /**
     * 检测等于
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param bool  $contrastType
     *
     * @return bool
     * User: dongjw
     * Date: 2022/2/22 15:31
     */
    protected static function equals($expected, $actual, $contrastType = false)
    {
        if ($contrastType === true) {
            return $expected === $actual;
        }

        return $expected == $actual;
    }

    /**
     * 断言失败后抛出异常
     *
     * @param bool   $bool
     * @param string $message
     * @param int    $errCode
     *
     * @throws LogicAssertException
     * User: dongjw
     * Date: 2022/2/22 15:32
     */
    protected static function throwException($bool, $message, $errCode = 0)
    {
        if ($bool === false) {
            throw new LogicAssertException($message, $errCode);
        }
    }
}
