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

abstract class AbstractRequest
{

    //option content-type
    const HEADER_CONTENT_TYPE = 'content-type';

    /**
     * @var HttpClient 协程请求客户端
     */
    protected $client;

    /**
     * @var int 连接超时时间
     */
    protected $connectTimeout = 3;

    /**
     * @var int 读取响应超时时间
     */
    protected $readTimeout = 5;

    /**
     * @var string 请求host
     */
    protected $apiDomain;

    /**
     * @var string 请求uri
     */
    protected $apiAction;

    /**
     * @var string 请求方法
     */
    protected $method;

    /**
     * @var int 最大重试次数
     */
    protected $maxRetryTimes = 3;

    /**
     * @var Response 响应对象
     */
    protected $response;

    /**
     * 获取请求的host
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
     * @param $action
     * @param array $params
     * @param string $method
     * @param array $header
     * @return Response|mixed
     * User: dongjw
     * Date: 2021/11/23 16:25
     */
    public function exec($action, $params = [], $method = HttpClient::METHOD_GET, $header = [])
    {

        $this->apiAction = $action;

        $this->method = $method;

        $client = $this->client = new HttpClient($this->getApiDomain());

        //设置连接超时时间
        $client->setConnectTimeout($this->connectTimeout);

        //设置请求超时时间
        $client->setReadTimeout($this->readTimeout);

        //设置请求uri
        $client->setPath($action);

        //beforeRequest可以重置params
        $params = $this->beforeRequest($params);

        $client->setHeaders($header);

        $startTime = getCurrentMilliseconds();
        $logAction = $action;
        $logParams = $params;
        if ($method == HttpClient::METHOD_GET) {
            $logParams = [];
            if(!empty($params)){
                $client->setQuery($params);
                $logAction .= '?' .  http_build_query($params);
            }
            $res = $client->get();
            }else if ($method == HttpClient::METHOD_POST) {
            $contentType = $header[self::HEADER_CONTENT_TYPE] ?? '';
            $res = $client->post($contentType == HttpClient::CONTENT_TYPE_APPLICATION_JSON ? json_encode($params) : $params);
        }else if ($method == HttpClient::METHOD_PUT) {
            $res = $client->put($params);
        }else if ($method == HttpClient::METHOD_DELETE) {
            $res = $client->delete();
        }

        $endTime = getCurrentMilliseconds();

        HttpClientLog::log([
            'logTag' => $res->getErrCode() === 0 ? '_http_success' : '_http_failure',
            'callee' => $this->apiDomain,
            'request' => $logParams,
            'uri' => $logAction,
            'response' => $res->getBody(),
            'elapsed' => $endTime - $startTime,
            'code' => $res->getStatusCode(),
            'fileName' => __FILE__,
            'functionName' => __FUNCTION__,
            'number' => __LINE__,
            'msg' => $res->getErrMsg()
        ]);

        $this->response = $res;

        return $this->afterRequest();
    }

    /**
     * 重复发起请求
     * @param $action
     * @param array $params
     * @param string $method
     * @param array $header
     * User: dongjw
     * Date: 2021/11/23 16:15
     */
    public function retryExec($action, $params = [], $method = HttpClient::METHOD_GET, $header = [])
    {
        $retryTimes = 0;
        do {
            $retryTimes++;
            $res = $this->exec($action, $params, $method, $header);
            $canRetryExec = $this->canRetryExec();
        } while ($canRetryExec && $retryTimes < $this->maxRetryTimes);

        return $res;
    }

    /**
     * 是否可以重复请求
     * @param Response $res
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
     * @param $params
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
     * @param Response $response
     * @return mixed
     * User: dongjw
     * Date: 2021/11/23 14:06
     */
    public function afterRequest()
    {
        return $this->response;
    }

    public function get($action, $params = [], $header = [])
    {
        return $this->exec($action,$params,HttpClient::METHOD_GET,$header);
    }

    public function retryGet($action, $params = [], $header = [])
    {
        return $this->retryExec($action,$params,HttpClient::METHOD_GET,$header);
    }

    public function post($action, $params = [], $header = [])
    {
        return $this->exec($action,$params,HttpClient::METHOD_POST,$header);
    }

    public function retryPost($action, $params = [], $header = [])
    {
        return $this->retryExec($action,$params,HttpClient::METHOD_POST,$header);
    }

    public function postJson($action, $params = [], $header = [])
    {
        $header[self::HEADER_CONTENT_TYPE] = HttpClient::CONTENT_TYPE_APPLICATION_JSON;
        return $this->exec($action,$params,HttpClient::METHOD_POST,$header);
    }

    public function retryPostJson($action, $params = [], $header = [])
    {
        $header[self::HEADER_CONTENT_TYPE] = HttpClient::CONTENT_TYPE_APPLICATION_JSON;
        return $this->retryExec($action,$params,HttpClient::METHOD_POST,$header);
    }

    public function put($action, $params = [], $header = [])
    {
        return $this->exec($action,$params,HttpClient::METHOD_PUT,$header);
    }

    public function retryPut($action, $params = [], $header = [])
    {
        return $this->retryExec($action,$params,HttpClient::METHOD_PUT,$header);
    }

    public function delete($action, $params = [], $header = [])
    {
        return $this->exec($action,$params,HttpClient::METHOD_DELETE,$header);
    }

    public function retryDelete($action, $params = [], $header = [])
    {
        return $this->retryExec($action,$params,HttpClient::METHOD_DELETE,$header);
    }

}