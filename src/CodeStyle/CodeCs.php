<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2022/2/21
 * Time: 14:29
 */

namespace EsSwoole\Base\CodeStyle;

class CodeCs
{
    protected $path = '';

    public function handle()
    {
        if (!file_exists(PROJECT_ROOT . '/vendor/bin/phpcs')) {
            $this->exitError('请先安装');
        }
    }

    public function exitError($msg = '')
    {
        echo $msg;
        exit(1);
    }
}