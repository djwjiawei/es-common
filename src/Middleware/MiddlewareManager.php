<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/12/14
 * Time: 14:26
 */

namespace EsSwoole\Base\Middleware;


use EasySwoole\Component\Singleton;
use EasySwoole\Http\Request;
use EsSwoole\Base\Abstracts\BaseHttpController;
use Swoole\Coroutine;

class MiddlewareManager
{
    use Singleton;

    const GLOBAL_TYPE = 'global';

    const STATIC_TYPE = 'static';

    const REGULAR_TYPE = 'regular';

    //全部中间件列表
    protected $middlewareList = [];

    //uri对应的正则中间件列表
    protected $uriRegularMiddlewareIndex = [];

    //中间件分组
    protected $middlewareRule = [
        self::GLOBAL_TYPE => [],
        self::STATIC_TYPE => [],
        self::REGULAR_TYPE => []
    ];

    protected $currentRequestIndex = [];

    public function __construct()
    {
        $this->initMiddleware();
    }

    /**
     * 执行before
     * @param Request $request
     * @param BaseHttpController $response
     * @return bool
     * User: dongjw
     * Date: 2021/12/14 15:48
     */
    public function handelBefore(Request $request,BaseHttpController $response)
    {
        $middlewareIndex = $this->getUriMiddleware($request->getUri()->getPath());
        if (!$middlewareIndex) {
            return true;
        }
        //将路由中间件信息缓存,在after中可以直接用
        $cid = Coroutine::getCid();
        $this->currentRequestIndex[$cid] = $middlewareIndex;
        //请求退出删除缓存
        defer(function () use ($cid){
            unset($this->currentRequestIndex[$cid]);
        });
        foreach ($middlewareIndex as $index) {
            if (!$this->middlewareList[$index]) {
                continue;
            }
            $res = ((new $this->middlewareList[$index]))->before($request, $response);
            if (!$res) {
                return false;
            }
        }
        return true;
    }

    /**
     * 执行after
     * @param Request $request
     * @param BaseHttpController $response
     * @return bool
     * User: dongjw
     * Date: 2021/12/14 16:03
     */
    public function handelAfter(Request $request,BaseHttpController $response)
    {
        $middlewareIndex = $this->currentRequestIndex[Coroutine::getCid()];
        if (!$middlewareIndex) {
            return true;
        }
        $reverseIndex = array_reverse($middlewareIndex);
        foreach ($reverseIndex as $index) {
            if (!$this->middlewareList[$index]) {
                continue;
            }
            (new $this->middlewareList[$index])->after($request, $response);
        }
        return true;
    }

    /**
     * 初始化中间件
     * @return bool
     * User: dongjw
     * Date: 2021/12/14 15:48
     */
    protected function initMiddleware()
    {
        $list = config('middleware');
        if (!$list) {
            return false;
        }
        //中间件索引
        $middlewareIndex = 0;
        foreach ($list as $uri => $uriMiddlewareArr) {
            if ($uri == '*') {
                $type = self::GLOBAL_TYPE;
            }else if (strpos($uri,'*') !== false) {
                $type = self::REGULAR_TYPE;
            }else{
                $type = self::STATIC_TYPE;
            }
            foreach ($uriMiddlewareArr as $middleware) {
                $tmpIndex = $this->middlewareList[$middleware] ?? null;
                if (!$tmpIndex) {
                    $tmpIndex = $this->middlewareList[$middleware] = $middlewareIndex;
                    $middlewareIndex++;
                }
                if ($type == self::GLOBAL_TYPE) {
                    $this->middlewareRule[$type][] = $tmpIndex;
                }else{
                    $this->middlewareRule[$type][$uri][] = $tmpIndex;
                }
            }
        }
        $this->middlewareList = array_flip($this->middlewareList);
        return true;
    }

    /**
     * 获取uri对应的中间件列表
     * @param $uri
     * @return array
     * User: dongjw
     * Date: 2021/12/14 15:48
     */
    public function getUriMiddleware($uri)
    {
        //正则中间件
        $regularMiddleware = [];
        if ($this->uriRegularMiddlewareIndex[$uri]) {
            $regularMiddleware = $this->uriRegularMiddlewareIndex[$uri];
        }else{
            if ($this->middlewareRule[self::REGULAR_TYPE]) {
                foreach ($this->middlewareRule[self::REGULAR_TYPE] as $regularUri => $regularUriMiddleware) {
                    //正则匹配
                    $rule = str_replace(['/','*'],['\\/','.*'],$regularUri);
                    if(preg_match('/^'. $rule .'/',$uri)) {
                        $regularMiddleware = array_merge($regularMiddleware, $regularUriMiddleware);
                    }
                }
            }
            if ($regularMiddleware) {
                $regularMiddleware = $this->uriRegularMiddlewareIndex[$uri] = array_unique($regularMiddleware);
            }
        }
        return array_unique(
            array_merge(
                $this->middlewareRule[self::GLOBAL_TYPE],
                $this->middlewareRule[self::STATIC_TYPE][$uri],
                $regularMiddleware
            )
        );
    }
}