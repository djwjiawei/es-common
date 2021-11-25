<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/19
 * Time: 14:48
 */

namespace EsSwoole\Base\Log;


use EasySwoole\Log\LoggerInterface;
use EasySwoole\Utility\File;
use DateTime;
use EsSwoole\Base\Util\RequestUtil;

class Logger implements LoggerInterface
{
    /**
     * http请求日志标签
     */
    const HTTP_REQUEST = 'http.client';

    /**
     * trigger标签
     */
    const TRIGGER = 'trigger';

    private $logDir;

    function __construct(string $logDir = null)
    {
        if(empty($logDir)){
            $logDir = EASYSWOOLE_LOG_DIR;
        }
        $this->logDir = $logDir;
    }

    /**
     * 打印文件日志(每一天一个目录，每个日志文件按小时记录)
     * @param string|null $msg 日志内容
     * @param int $logLevel 日志级别
     * @param string $category 日志分类/会以该分类作文件名
     * User: dongjw
     * Date: 2021/11/19 14:42
     */
    function log(?string $msg,int $logLevel = self::LOG_LEVEL_DEBUG,string $category = 'info')
    {
        $subDir = $this->logDir . '/' . date('Y-m-d');
        if (!is_dir($subDir)) {
            File::createDirectory($subDir);
        }
        $filePath = $subDir . '/' . $category . '_' . date('H') . '.log';

        file_put_contents($filePath,$this->getFormat($msg,$logLevel,$category),FILE_APPEND|LOCK_EX);
    }

    /**
     * 输出到控制台
     * @param string|null $msg
     * @param int $logLevel
     * @param string $category
     * User: dongjw
     * Date: 2021/11/19 14:42
     */
    function console(?string $msg,int $logLevel = self::LOG_LEVEL_DEBUG,string $category = 'info')
    {
        echo $this->getFormat($msg,$logLevel,$category,true);
    }

    /**
     * 获取日志格式化内容
     * @param $msg
     * @param $logLevel
     * @param $category
     * @param bool $isConsole
     * @return string
     * User: dongjw
     * Date: 2021/11/19 14:42
     */
    private function getFormat($msg,$logLevel,$category,$isConsole = false)
    {
        $date = (new DateTime())->format('Y-m-d\TH:i:s.uP');
        $levelStr = $this->levelMap($logLevel);

        //日期
        $str = "[{$date}]";
        //如果是console加上分类信息，文件会以分类为名则不用加
        if ($isConsole) {
            $str .= "[{$category}]";
        }
        //加log级别信息
        $str .= "[{$levelStr}]";
        //加traceid
        if (RequestUtil::getRequest()) {
            $traceId = RequestUtil::getRequest()->getAttribute('traceId');
            $str .= "[{$traceId}]";
        }

        $str .= " : ";

        //如果不是httpclient或trigger，则再获取打印日志的代码位置信息
        if (!in_array($category,[self::HTTP_REQUEST,self::TRIGGER])) {
            $debugTrace = debug_backtrace();
            //清除当前调用栈
            array_shift($debugTrace);
            //清除框架logger调用栈
            array_shift($debugTrace);
            for($i = 0;$i<6;$i++){
                //最多查询6层
                $tempLocation = array_shift($debugTrace);
                if (!$tempLocation) {
                    break;
                }
                if($tempLocation['class'] == \EasySwoole\EasySwoole\Logger::class){
                    $tempBeforeLocation = $tempLocation;
                }else{
                    //如果不是说明调用log停止
                    $logLocation = $tempBeforeLocation ?? null;
                    break;
                }
            }
            if ($logLocation) {
                $str .= "[" . $logLocation['file'] . ":" . $logLocation['line'] . "]";
            }
        }

        //添加主体消息
        $str .= "{$msg}" . PHP_EOL;
        return $str;
    }

    private function levelMap(int $level)
    {
        switch ($level)
        {
            case self::LOG_LEVEL_DEBUG:
                return 'debug';
            case self::LOG_LEVEL_INFO:
                return 'info';
            case self::LOG_LEVEL_NOTICE:
                return 'notice';
            case self::LOG_LEVEL_WARNING:
                return 'warning';
            case self::LOG_LEVEL_ERROR:
                return 'error';
            default:
                return 'unknown';
        }
    }
}