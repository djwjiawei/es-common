<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/25
 * Time: 15:05
 */

namespace EsSwoole\Base\Exception;

use EasySwoole\Http\Request;
use EsSwoole\Base\Common\Mail;

/**
 * 邮件报告
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class MailReport implements ReportInterface
{
    /**
     * 发送报告
     *
     * @param mixed        $config
     * @param Request|null $request
     * @param \Throwable   $exception
     * @param string       $msg
     *
     * @return mixed
     * User: dongjw
     * Date: 2022/2/25 16:36
     */
    public static function report($config, $request, \Throwable $exception, $msg = '')
    {
        if (empty($config['sendBugMail'])) {
            return;
        }

        $file = $exception->getFile();
        $line = $exception->getLine();
        $code = $exception->getCode();

        $body = '<b>服务器ip：</b>' . gethostbyname(gethostname()) .
                '<hr/><b>文件地址：</b>' . $file .
                '<hr/><b>错误编码：</b>' . $code .
                '<hr/><b>行数：</b>' . $line . '<hr/>';

        $runEnv = $afterBody = '';

        if ($request) {
            //request不为空的话，则为http请求
            $runEnv     = 'web';
            $requestUrl = $request->getUri()->getHost() . $request->getUri()->getPath();
            $queryParam = $request->getUri()->getQuery() ?: '';
            if ($queryParam) {
                $requestUrl = $requestUrl . '?' . $queryParam;
            }

            $afterBody = '<b>请求地址：</b>' . $requestUrl . ' [' . $request->getMethod() . '] <hr/>';
            if ($request->getMethod() != 'GET') {
                $requestParam = $request->getParsedBody() ?: $request->getBody()->__toString();
                if (is_array($requestParam) && $requestParam) {
                    $requestParam = json_encode($requestParam);
                }

                $afterBody .= '<b>请求参数：</b>' . $requestParam . '<hr/>';
            }
        }

        $des        = $msg ?: $exception->getMessage();
        $errorMsg   = '<b>错误描述：</b>' . $des . '<hr/>';
        $errorTrace = '<b>错误trace：</b><br/>' . $exception->getTraceAsString();
        //发送的邮件主题
        $subject = '【' . config('APP_ENV') . "环境】{$runEnv}错误报告【" . date('Y-m-d H:i:s') . '】';

        //发送的邮件内容
        $exceptionBody = $body . $afterBody . $errorMsg . $errorTrace;

        //根据请求环境选择同步/异步发送
        Mail::smartSend($subject, $exceptionBody, $config['sendBugMail']);
    }
}
