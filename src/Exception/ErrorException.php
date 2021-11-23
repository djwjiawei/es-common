<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/9/5
 * Time: 17:14
 */

namespace EsSwoole\Base\Exception;


class ErrorException extends \Exception
{
    public function __construct($message = "", $code = 0, $file = '', $line = '')
    {
        parent::__construct($message, $code);
        $this->file = $file;
        $this->line = $line;
    }
}