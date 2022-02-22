<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/24
 * Time: 13:51
 */

namespace EsSwoole\Base;

use EsSwoole\Base\Abstracts\ConfigPublishInterface;

/**
 * 包发布配置类
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class ConfigPublish implements ConfigPublishInterface
{
    /**
     * 发布配置
     *
     * @return array|mixed
     * User: dongjw
     * Date: 2022/2/22 17:01
     */
    public function publish()
    {
        return [
            __DIR__ . '/config/esCommon.php'   => configPath('esCommon.php'),
            __DIR__ . '/config/statusCode.php' => configPath('statusCode.php'),
            __DIR__ . '/config/middleware.php' => configPath('middleware.php'),
        ];
    }
}
