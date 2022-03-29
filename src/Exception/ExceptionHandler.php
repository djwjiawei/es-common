<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/8/18
 * Time: 18:05
 */

namespace EsSwoole\Base\Exception;

use EasySwoole\EasySwoole\Logger;
use EsSwoole\Base\Log\TriggerHandle;
use EsSwoole\Base\Util\AppUtil;
use EsSwoole\Base\Redis\ExceptionRedis;
use EasySwoole\Component\Di;
use EasySwoole\EasySwoole\SysConst;
use EasySwoole\EasySwoole\Trigger;
use EasySwoole\Http\Message\Status;
use EsSwoole\Base\Util\RequestUtil;
use EsSwoole\Base\Util\TraceIdUtil;

/**
 * 异常处理器
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class ExceptionHandler
{
    /**
     * 注入异常回调
     * User: dongjw
     * Date: 2021/9/5 18:30
     */
    public static function injectException()
    {
        //设置报错级别(去除notice错误)
        Di::getInstance()->set(SysConst::ERROR_REPORT_LEVEL, E_ALL ^ E_NOTICE);

        //设置set_exception_handler
        Di::getInstance()->set(
            SysConst::HTTP_EXCEPTION_HANDLER, function ($throwable, $request, $response) {
            $data = [];

            //如果是生产环境，不显示详细错误
            if (AppUtil::isProd()) {
                $msg = '系统异常';
            } else {
                //测试环境返回trace信息
                $msg           = $throwable->getMessage();
                $data['trace'] = $throwable->getTraceAsString();
            }

            $data['traceId'] = getTraceId();

            //记录trigger日志
            Trigger::getInstance()->throwable($throwable);

            //设置响应头：500
            $response->withStatus(Status::CODE_INTERNAL_SERVER_ERROR);

            //输出异常返回
            RequestUtil::outJson(
                $response, LogicAssertException::getErrCode(LogicAssertException::NO_CATCH_CODE), $msg, [], $data
            );

            //异常报告
            go(
                function () use ($throwable) {
                    ExceptionHandler::report($throwable);
                }
            );
        }
        );

        //设置set_error_handler
        Di::getInstance()->set(
            SysConst::ERROR_HANDLER, function ($errorCode, $description, $file = null, $line = null) {
            if (error_reporting() & $errorCode) {
                throw new ErrorException($description, $errorCode, $file, $line);
            }
        }
        );

        //设置register_shutdown_function
        Di::getInstance()->set(
            SysConst::SHUTDOWN_FUNCTION, function () {
            //只对非worker进程记录日志，worker进程在EasySwooleEvent onRequest全局事件中单独处理
            $error = error_get_last();
            if ($error) {
                $message = 'shutdown错误: ' . $error['message'];

                Logger::getInstance()->error(
                    "{$message} at file:{$error['file']} line:{$error['line']}", TriggerHandle::TRIGGER_NAME
                );

                //register_shut_down回调方法里协程已经不能用了，也不能用go开启协程
                //ExceptionHandler::report(new \ErrorException($message,-1,$error['type'],$error['file'],$error['line']));
            }
        }
        );
    }

    /**
     * 发送异常邮件
     *
     * @param \Throwable $exception
     * @param string     $msg
     * User: dongjw
     * Date: 2022/2/22 15:44
     */
    public static function report(\Throwable $exception, $msg = '')
    {
        if (!config('esCommon.exception.isReport')) {
            return false;
        }

        //获取请求
        $request = RequestUtil::getRequest();

        $file = $exception->getFile();
        $line = $exception->getLine();

        //获取当前协程traceid
        $traceId = TraceIdUtil::getCurrentTraceId();

        if (!ExceptionRedis::getInstance()->check(md5($file . $line))) {
            return;
        }

        foreach (config('esCommon.exception.report') as $type => $config) {
            if (empty($config['handle']) || !is_subclass_of(
                    $config['handle'], ReportInterface::class
                ) || empty($config['isReport'])) {
                continue;
            }

            $config['handle']::report($config, $request, $exception, $traceId, $msg);
        }
    }

    /**
     * 记录异常并发送报告
     *
     * @param \Throwable $exception
     * @param string     $msg
     * User: dongjw
     * Date: 2022/3/23 16:59
     */
    public static function logReport(\Throwable $exception, $msg = '')
    {
        Trigger::getInstance()->throwable($exception);
        self::report($exception, $msg);
    }
}
