<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/29
 * Time: 16:31
 */

namespace EsSwoole\Base\Abstracts;


interface ProcessMessageInterface
{
    public function run();
    public function onException(\Throwable $throwable);
}