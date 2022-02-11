<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/8/18
 * Time: 18:05
 */

namespace EsSwoole\Base\Exception;


use EsSwoole\Base\Common\Mail;
use EsSwoole\Base\Util\AppUtil;
use EsSwoole\Base\Redis\ExceptionRedis;
use EasySwoole\Component\Di;
use EasySwoole\EasySwoole\SysConst;
use EasySwoole\EasySwoole\Trigger;
use EasySwoole\Http\Message\Status;
use EasySwoole\Trigger\Location;
use EsSwoole\Base\Util\RequestUtil;

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
        Di::getInstance()->set(SysConst::ERROR_REPORT_LEVEL, E_ALL^E_NOTICE);

        //设置set_exception_handler
        Di::getInstance()->set(SysConst::HTTP_EXCEPTION_HANDLER, function ($throwable, $request, $response) {
            $msg = '';
            $data = [];

           //如果是生产环境，不显示详细错误
            if(AppUtil::isProd()){
                if (!($throwable instanceof ApiException)) {
                    $msg = '系统异常';
                }
            }else{
                //测试环境返回trace信息
                $data['trace'] = $throwable->getTraceAsString();
            }
            $data['traceId'] = $request->getAttribute('traceId') ?: '';

            //error的已经在set_error_handler中记录了，这里只记录exception的
            if (!($throwable instanceof ErrorException)) {
                Trigger::getInstance()->throwable($throwable);
            }

            //设置响应头：500
            $response->withStatus(Status::CODE_INTERNAL_SERVER_ERROR);

            //输出异常返回
            RequestUtil::outJson(
                $response,
                LogicAssertException::getErrCode(LogicAssertException::NO_CATCH_CODE),
                $msg,
                [],
                $data
            );

            //发送邮件
            go(function () use($throwable) {
                ExceptionHandler::report($throwable);
            });
        });

        //设置set_error_handler
        Di::getInstance()->set(SysConst::ERROR_HANDLER, function ($errorCode, $description, $file = null, $line = null) {
            if (error_reporting() & $errorCode) {
                $l = new Location();
                $l->setFile($file);
                $l->setLine($line);
                Trigger::getInstance()->error($description, $errorCode, $l);

                $exception = new ErrorException($description,$errorCode,$file,$line);
                if (RequestUtil::getRequest()) {
                    //如果是在http请求内，则统一让http exception处理
                    throw $exception;
                }else{
                    //不在http请求内，发送邮件
                    ExceptionHandler::report($exception);
                }
            }
        });

        //设置register_shutdown_function
        Di::getInstance()->set(SysConst::SHUTDOWN_FUNCTION, function () {
            //只对非worker进程记录日志，worker进程在EasySwooleEvent onRequest全局事件中单独处理
            $error = error_get_last();
            if ($error) {
                $l = new Location();
                $l->setFile($error['file']);
                $l->setLine($error['line']);
                $message = 'shutdown错误: ' . $error['message'];
                Trigger::getInstance()->error($message, $error['type'], $l);

                //register_shut_down回调方法里协程已经不能用了，也不能用go开启协程
                //ExceptionHandler::report(new \ErrorException($message,-1,$error['type'],$error['file'],$error['line']));
            }
        });
    }

    /**
     * 发送异常邮件
     * @param \Throwable $exception
     * User: dongjw
     * Date: 2021/9/5 18:30
     */
    public static function report(\Throwable $exception, $msg = '')
    {
        $file = $exception->getFile();
        $line = $exception->getLine();
        $code = $exception->getCode();

        $request = RequestUtil::getRequest();
        //5分钟内只发送一次同样的错误
        if (config('esCommon.exception.sendBugMail') && ExceptionRedis::getInstance()->check(md5($file.$line))) {
            $body = "
        <b>服务器ip：</b>" . gethostbyname(gethostname()) . "<hr/>
        <b>文件地址：</b>" . $file . "<hr/>
        <b>错误编码：</b>" . $code . "<hr/>
        <b>行数：</b>" . $line . "<hr/>";

            $runEnv = $afterBody = '';

            if ($request) {
                //request不为空的话，则为http请求
                $runEnv = 'web';
                $requestUrl = $request->getUri()->getHost() . $request->getUri()->getPath();
                $queryParam = $request->getUri()->getQuery() ?: '';
                if ($queryParam) {
                    $requestUrl = $requestUrl . '?' . $queryParam;
                }
                $afterBody = "<b>请求地址：</b>" . $requestUrl . " [" . $request->getMethod() . "] <hr/>";
                if ($request->getMethod() != 'GET') {
                    $requestParam = $request->getParsedBody() ?: $request->getBody()->__toString();
                    if (is_array($requestParam) && $requestParam) {
                        $requestParam = json_encode($requestParam);
                    }
                    $afterBody .= "<b>请求参数：</b>" . $requestParam . "<hr/>";
                }
            }

            $des = $msg ?: $exception->getMessage();
            $errorMsg = "<b>错误描述：</b>" . $des . "<hr/>";
            $errorTrace = "<b>错误trace：</b><br/>" . $exception->getTraceAsString();
            //发送的邮件主题
            $subject = "【" . config('APP_ENV') . "环境】{$runEnv}错误报告【" . date('Y-m-d H:i:s') . "】";

            //发送的邮件内容
            $exceptionBody = $body . $afterBody . $errorMsg . $errorTrace;

            //根据请求环境选择同步/异步发送
            Mail::smartSend($subject,$exceptionBody,config('esCommon.exception.sendBugMail'));
        }
    }
}