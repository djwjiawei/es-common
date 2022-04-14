<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/25
 * Time: 14:46
 */

namespace EsSwoole\Base\Exception;

use EasySwoole\Http\Request;
use EsSwoole\Base\Request\DingdingRequest;
use EsSwoole\Base\Util\AppUtil;

/**
 * 钉钉报告
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class DingdingReport implements ReportInterface
{
    /**
     * 发送报告
     *
     * @param mixed        $config
     * @param Request|null $request
     * @param \Throwable   $exception
     * @param string       $msg
     *
     * @return array|bool|mixed
     * User: dongjw
     * Date: 2022/2/25 16:36
     */
    public static function report($config, $request, \Throwable $exception, $traceId = '', $msg = '')
    {
        if (empty($config['token'])) {
            return false;
        }

        $file = $exception->getFile();
        $line = $exception->getLine();
        $code = $exception->getCode();

        $body = '服务器ip：' . AppUtil::getLocalIp() . PHP_EOL .
                '文件地址：' . $file . PHP_EOL .
                '行数：' . $line . PHP_EOL .
                '错误编码：' . $code . PHP_EOL;

        $runEnv = $afterBody = '';

        if ($request) {
            //request不为空的话，则为http请求
            $runEnv     = 'web';
            $requestUrl = $request->getUri()->getHost() . $request->getUri()->getPath();
            $queryParam = $request->getUri()->getQuery() ?: '';
            if ($queryParam) {
                $requestUrl = $requestUrl . '?' . $queryParam;
            }

            $afterBody = '请求地址：' . $requestUrl . ' [' . $request->getMethod() . ']' . PHP_EOL;
            if ($request->getMethod() != 'GET') {
                $requestParam = $request->getParsedBody() ?: $request->getBody()->__toString();
                if (is_array($requestParam) && $requestParam) {
                    $requestParam = json_encode($requestParam);
                }

                $afterBody .= '请求参数：' . $requestParam . PHP_EOL;
            }
        }

        $des        = $msg ?: $exception->getMessage();
        $errorMsg   = '错误描述：' . $des . PHP_EOL;

        if ($traceId) {
            $errorTrace = '错误trace ' . $traceId . '：';
        } else {
            $errorTrace = '错误trace：';
        }

        $errorTrace .=  PHP_EOL . $exception->getTraceAsString();

        //发送的邮件主题
        $subject = '【' .config('esCommon.serviceName') . ' ' . config('RUN_ENV') . "环境】{$runEnv}错误报告【" . date('Y-m-d H:i:s') . '】' . PHP_EOL;

        //发送的邮件内容
        $exceptionBody = $subject . $body . $afterBody . $errorMsg . $errorTrace;

        return (new DingdingRequest())->sendText(
            $config['token'], $exceptionBody, $config['atMobiles'], $config['isAtAll']
        );
    }
}
