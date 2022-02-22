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
use EasySwoole\Http\Response;
use Swoole\Coroutine;

/**
 * 中间件manager
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
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
        self::GLOBAL_TYPE  => [],
        self::STATIC_TYPE  => [],
        self::REGULAR_TYPE => [],
    ];

    protected $currentRequestIndex = [];

    /**
     * MiddlewareManager constructor.
     */
    public function __construct()
    {
        $this->initMiddleware();
    }

    /**
     * 执行before
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     * User: dongjw
     * Date: 2021/12/14 15:48
     */
    public function handelBefore(Request $request, Response $response)
    {
        $middlewareIndex = $this->getUriMiddleware($request->getUri()->getPath());
        if (!$middlewareIndex) {
            return true;
        }

        //将路由中间件信息缓存,在after中可以直接用
        $cid                             = Coroutine::getCid();
        $this->currentRequestIndex[$cid] = $middlewareIndex;
        //请求退出删除缓存
        defer(
            function () use ($cid) {
                unset($this->currentRequestIndex[$cid]);
            }
        );
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
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     * User: dongjw
     * Date: 2021/12/14 16:03
     */
    public function handelAfter(Request $request, Response $response)
    {
        $middlewareIndex = $this->currentRequestIndex[Coroutine::getCid()];
        if (!$middlewareIndex) {
            return true;
        }

        //在after时翻转中间件索引
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
     *
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
            } else if (strpos($uri, '*') !== false) {
                //出现*时认为是正则中间件
                $type = self::REGULAR_TYPE;
            } else {
                $type = self::STATIC_TYPE;
            }

            foreach ($uriMiddlewareArr as $middleware) {
                $tmpIndex = $this->middlewareList[$middleware] ?? null;
                if (!$tmpIndex) {
                    //push中间件到list中,值为index
                    $tmpIndex = $this->middlewareList[$middleware] = $middlewareIndex;
                    $middlewareIndex++;
                }

                //全局中间件直接存储
                if ($type == self::GLOBAL_TYPE) {
                    $this->middlewareRule[$type][] = $tmpIndex;
                } else {
                    //静态和正则中间件需要加uri匹配
                    $this->middlewareRule[$type][$uri][] = $tmpIndex;
                }
            }
        }

        //key与value互换,将key设为索引,目的是减少内存开销
        $this->middlewareList = array_flip($this->middlewareList);

        return true;
    }

    /**
     * 获取uri对应的中间件列表
     *
     * @param string $uri
     *
     * @return array
     * User: dongjw
     * Date: 2021/12/14 15:48
     */
    public function getUriMiddleware($uri)
    {
        //正则中间件
        $regularMiddleware = [];
        if (isset($this->uriRegularMiddlewareIndex[$uri])) {
            $regularMiddleware = $this->uriRegularMiddlewareIndex[$uri];
        } else {
            if ($this->middlewareRule[self::REGULAR_TYPE]) {
                foreach ($this->middlewareRule[self::REGULAR_TYPE] as $regularUri => $regularUriMiddleware) {
                    //正则匹配
                    $rule = str_replace(['/', '*'], ['\\/', '.*'], $regularUri);
                    if (preg_match('/^' . $rule . '/', $uri)) {
                        $regularMiddleware = array_merge($regularMiddleware, $regularUriMiddleware);
                    }
                }
            }

            if ($regularMiddleware) {
                //如果匹配成功了,加到缓存中,下次直接冲缓存中取
                $regularMiddleware = $this->uriRegularMiddlewareIndex[$uri] = array_unique($regularMiddleware);
            }
        }

        return array_unique(
            array_merge(
                $this->middlewareRule[self::GLOBAL_TYPE], $this->middlewareRule[self::STATIC_TYPE][$uri] ?: [],
                $regularMiddleware
            )
        );
    }
}
