<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/29
 * Time: 16:31
 */

namespace EsSwoole\Base\Abstracts;

/**
 * 进程收到消息要处理的接口类
 *
 * @package EsSwoole\Base\Abstracts
 */
interface ProcessMessageInterface
{
    /**
     * 收到消息后的处理逻辑
     *
     * @return mixed
     * User: dongjw
     * Date: 2022/2/22 13:44
     */
    public function run();

    /**
     * 触发异常后的执行方法
     *
     * @param \Throwable $throwable
     *
     * @return mixed
     * User: dongjw
     * Date: 2022/2/22 13:44
     */
    public function onException(\Throwable $throwable);
}
