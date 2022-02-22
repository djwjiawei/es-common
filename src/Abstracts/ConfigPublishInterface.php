<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/24
 * Time: 14:24
 */

namespace EsSwoole\Base\Abstracts;

/**
 * Config发布接口类
 *
 * @package EsSwoole\Base\Abstracts
 */
interface ConfigPublishInterface
{
    /**
     * 要发布的配置
     *
     * @return mixed
     * User: dongjw
     * Date: 2022/2/22 13:43
     */
    public function publish();
}
