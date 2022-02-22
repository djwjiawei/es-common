<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/23
 * Time: 11:35
 */

namespace EsSwoole\Base\Request;

use EsSwoole\Base\Log\HttpClientLog;
use EasySwoole\HttpClient\HttpClient;
use EasySwoole\HttpClient\Bean\Response;

/**
 * 请求抽象类
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
abstract class AbstractRequest
{

    //option content-type
    const HEADER_CONTENT_TYPE = 'content-type';

    /**
     * 协程请求客户端
     *
     * @var HttpClient
     */
    protected $client;

    /**
     * 连接超时时间
     *
     * @var int
     */
    protected $connectTimeout = 3;

    /**
     * 读取响应超时时间
     *
     * @var int
     */
    protected $readTimeout = 5;

    /**
     * 请求host
     *
     * @var string
     */
    protected $apiDomain;

    /**
     * 请求uri
     *
     * @var string
     */
    protected $apiAction;

    /**
     * 请求方法
     *
     * @var string
     */
    protected $method;

    /**
     * 最大重试次数
     *
     * @var int
     */
    protected $maxRetryTimes = 3;

    /**
     * 响应对象
     *
     * @var Response
     */
    protected $response;

    /**
     * 获取请求的host
     *
     * @return string
     * User: dongjw
     * Date: 2021/11/23 16:14
     */
    public function getApiDomain()
    {
        if (!$this->apiDomain) {
            throw new \Exception('请设置apiDomain');
        }

        return $this->apiDomain;
    }

    /**
     * 发起请求
     *
     * @param string $action
     * @param array  $params
     * @param string $method
     * @param array  $header
     *
     * @return Response|mixed
     * User: dongjw
     * Date: 2021/11/23 16:25
     */
    public function exec($action, $params = [], $method = HttpClient::METHOD_GET, $header = [])
    {
        $this->apiAction = $action;

        $this->method = $method;

        $apiDomain = $this->getApiDomain();
        $client    = $this->client = new HttpClient($apiDomain);

        //设置连接超时时间
        $client->setConnectTimeout($this->connectTimeout);

        //设置请求超时时间
        $client->setReadTimeout($this->readTimeout);

        //设置请求uri
        $client->setPath($action);

        //beforeRequest可以重置params
        $beforeParams = $this->beforeRequest($params);
        if ($beforeParams) {
            $params = $beforeParams;
        }

        $client->setHeaders($header);

        $startTime = getCurrentMilliseconds();
        $logAction = $action;
        $logParams = $params;
        if ($method == HttpClient::METHOD_GET) {
            $logParams = [];
            if (!empty($params)) {
                $client->setQuery($params);
                $logAction .= '?' . http_build_query($params);
            }

            $res = $client->get();
        } else if ($method == HttpClient::METHOD_POST) {
            $contentType = $header[self::HEADER_CONTENT_TYPE] ?? '';
            $res         = $client->post(
                $contentType == HttpClient::CONTENT_TYPE_APPLICATION_JSON ? json_encode($params) : $params
            );
        } else if ($method == HttpClient::METHOD_PUT) {
            $res = $client->put($params);
        } else if ($method == HttpClient::METHOD_DELETE) {
            $res = $client->delete();
        }

        $endTime = getCurrentMilliseconds();

        HttpClientLog::log(
            [
                'logTag'       => $res->getErrCode() === 0 ? '_http_success' : '_http_failure',
                'callee'       => $apiDomain,
                'request'      => $logParams,
                'uri'          => $logAction,
                'response'     => $res->getBody(),
                'elapsed'      => $endTime - $startTime,
                'code'         => $res->getStatusCode(),
                'fileName'     => __FILE__,
                'functionName' => __FUNCTION__,
                'number'       => __LINE__,
                'msg'          => $res->getErrMsg(),
            ]
        );

        $this->response = $res;

        return $this->afterRequest();
    }

    /**
     * 重复发起请求
     *
     * @param string $action
     * @param array  $params
     * @param string $method
     * @param array  $header
     * User: dongjw
     * Date: 2021/11/23 16:15
     */
    public function retryExec($action, $params = [], $method = HttpClient::METHOD_GET, $header = [])
    {
        $retryTimes = 0;
        do {
            $retryTimes++;
            $res          = $this->exec($action, $params, $method, $header);
            $canRetryExec = $this->canRetryExec();
        } while ($canRetryExec && $retryTimes < $this->maxRetryTimes);

        return $res;
    }

    /**
     * 是否可以重复请求
     *
     * @return bool
     * User: dongjw
     * Date: 2021/11/23 16:26
     */
    public function canRetryExec()
    {
        return false;
    }

    /**
     * 请求前的调用方法
     *
     * @param array $params
     *
     * @return mixed
     * User: dongjw
     * Date: 2021/11/23 14:07
     */
    public function beforeRequest($params)
    {
        return $params;
    }

    /**
     * 请求后调用的方法
     *
     * @return mixed
     * User: dongjw
     * Date: 2021/11/23 14:06
     */
    public function afterRequest()
    {
        return $this->response;
    }

    /**
     * 发起get请求
     *
     * @param string $action
     * @param array  $params
     * @param array  $header
     *
     * @return Response|mixed
     * User: dongjw
     * Date: 2022/2/22 17:19
     */
    public function get($action, $params = [], $header = [])
    {
        return $this->exec($action, $params, HttpClient::METHOD_GET, $header);
    }

    /**
     * 重试get请求
     *
     * @param string $action
     * @param array  $params
     * @param array  $header
     *
     * @return Response|mixed
     * User: dongjw
     * Date: 2022/2/22 17:19
     */
    public function retryGet($action, $params = [], $header = [])
    {
        return $this->retryExec($action, $params, HttpClient::METHOD_GET, $header);
    }

    /**
     * 发起post请求
     *
     * @param string $action
     * @param array  $params
     * @param array  $header
     *
     * @return Response|mixed
     * User: dongjw
     * Date: 2022/2/22 17:20
     */
    public function post($action, $params = [], $header = [])
    {
        return $this->exec($action, $params, HttpClient::METHOD_POST, $header);
    }

    /**
     * 重试post请求
     *
     * @param string $action
     * @param array  $params
     * @param array  $header
     *
     * @return Response|mixed
     * User: dongjw
     * Date: 2022/2/22 17:20
     */
    public function retryPost($action, $params = [], $header = [])
    {
        return $this->retryExec($action, $params, HttpClient::METHOD_POST, $header);
    }

    /**
     * 发起postJson请求
     *
     * @param string $action
     * @param array  $params
     * @param array  $header
     *
     * @return Response|mixed
     * User: dongjw
     * Date: 2022/2/22 17:20
     */
    public function postJson($action, $params = [], $header = [])
    {
        $header[self::HEADER_CONTENT_TYPE] = HttpClient::CONTENT_TYPE_APPLICATION_JSON;

        return $this->exec($action, $params, HttpClient::METHOD_POST, $header);
    }

    /**
     * 重试postJson请求
     *
     * @param string $action
     * @param array  $params
     * @param array  $header
     *
     * @return Response|mixed
     * User: dongjw
     * Date: 2022/2/22 17:20
     */
    public function retryPostJson($action, $params = [], $header = [])
    {
        $header[self::HEADER_CONTENT_TYPE] = HttpClient::CONTENT_TYPE_APPLICATION_JSON;

        return $this->retryExec($action, $params, HttpClient::METHOD_POST, $header);
    }

    /**
     * 发起put请求
     *
     * @param string $action
     * @param array  $params
     * @param array  $header
     *
     * @return Response|mixed
     * User: dongjw
     * Date: 2022/2/22 17:21
     */
    public function put($action, $params = [], $header = [])
    {
        return $this->exec($action, $params, HttpClient::METHOD_PUT, $header);
    }

    /**
     * 重试put请求
     *
     * @param string $action
     * @param array  $params
     * @param array  $header
     *
     * @return Response|mixed
     * User: dongjw
     * Date: 2022/2/22 17:21
     */
    public function retryPut($action, $params = [], $header = [])
    {
        return $this->retryExec($action, $params, HttpClient::METHOD_PUT, $header);
    }

    /**
     * 发起delete请求
     *
     * @param string $action
     * @param array  $params
     * @param array  $header
     *
     * @return Response|mixed
     * User: dongjw
     * Date: 2022/2/22 17:21
     */
    public function delete($action, $params = [], $header = [])
    {
        return $this->exec($action, $params, HttpClient::METHOD_DELETE, $header);
    }

    /**
     * 重试delete请求
     *
     * @param string $action
     * @param array  $params
     * @param array  $header
     *
     * @return Response|mixed
     * User: dongjw
     * Date: 2022/2/22 17:21
     */
    public function retryDelete($action, $params = [], $header = [])
    {
        return $this->retryExec($action, $params, HttpClient::METHOD_DELETE, $header);
    }

}
