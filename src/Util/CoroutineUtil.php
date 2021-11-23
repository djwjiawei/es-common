<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/19
 * Time: 15:00
 */

namespace EsSwoole\Base\Util;

use Swoole\Coroutine;

class CoroutineUtil
{
    /**
     * 获取顶级协程id
     * @return mixed
     * User: dongjw
     * Date: 2021/11/19 15:00
     */
    public static function getTopCid()
    {
        if (($topId = Coroutine::getCid())) {
            $hasParent = true;
            while ($hasParent) {
                $tmpId = Coroutine::getPcid($topId);
                if ($tmpId > 0) {
                    $topId = $tmpId;
                }else{
                    $hasParent = false;
                }
            }
        }
        return $topId;
    }
}