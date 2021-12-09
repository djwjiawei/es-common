<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/12/6
 * Time: 17:08
 */

namespace EsSwoole\Base\Service;


use EasySwoole\Component\Singleton;
use EsSwoole\Base\Util\RequestUtil;
use Swoole\Coroutine;

class MerchantService
{
    use Singleton;

    protected $currentMerchant = [];

    public function getCurrentMerchant()
    {
        $cid = Coroutine::getCid();
        if (!$cid) {
            return false;
        }
        if (isset($this->currentMerchant[$cid])) {
            return $this->currentMerchant[$cid];
        }
        //todo java改造完后不读表 直接从redis中获取

    }

    /**
     * 设置当前协程环境的merchantNum
     * @param $merchantNum
     * @return $this|bool
     * User: dongjw
     * Date: 2021/12/6 17:14
     */
    public function setCurrentMerchant($merchantNum = '')
    {
        $cid = Coroutine::getCid();
        if (!$cid) {
            return false;
        }
        if (!$merchantNum) {
            $request = RequestUtil::getRequest();
            if (!$request) {
                return false;
            }
            $merchantNum = $request->getRequestParam();
        }
        $this->currentMerchant[$cid] = $merchantNum;
        defer(function () use($cid) {
            unset($this->currentMerchant[$cid]);
        });
        return true;
    }
}