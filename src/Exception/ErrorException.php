<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/9/5
 * Time: 17:14
 */

namespace EsSwoole\Base\Exception;

/**
 * Class ErrorException
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class ErrorException extends \Exception
{
    /**
     * ErrorException constructor.
     *
     * @param string $message
     * @param int    $code
     * @param string $file
     * @param string $line
     */
    public function __construct($message = '', $code = 0, $file = '', $line = '')
    {
        parent::__construct($message, $code);
        $this->file = $file;
        $this->line = $line;
    }
}
