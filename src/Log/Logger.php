<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/19
 * Time: 14:48
 */

namespace EsSwoole\Base\Log;


use EasySwoole\Component\Singleton;
use EasySwoole\Log\LoggerInterface;
use DateTime;
use EsSwoole\Base\Util\RequestUtil;

class Logger implements LoggerInterface
{
    use Singleton;

    const TASK_MODE = 1;

    /**
     * http请求日志标签
     */
    const HTTP_REQUEST = 'http.client';

    /**
     * trigger标签
     */
    const TRIGGER = 'trigger';

    /**
     * 日志根目录
     */
    private $logDir;

    /**
     * @var array \Monolog\Logger
     */
    protected $logHandler = [];

    public function __construct(string $logDir = null)
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
    public function log(?string $msg,int $logLevel = self::LOG_LEVEL_DEBUG,string $category = 'info')
    {
        $levelMap = $this->levelMap($logLevel);
        $format = $this->getFormat($msg, $levelMap, $category);

        go(function () use($format, $levelMap, $category){
            try {
                $this->logWrite($format, $levelMap, $category);
            } catch (\Throwable $e) {
                $this->console('log异常 msg:' . $e->getMessage(), self::LOG_LEVEL_WARNING);
            }
        });

        //下边为投递到task中执行 但实际压测效果直接开启协程处理效果更优
//        if (isWorkerProcess() && config('esCommon.log.mode') == self::TASK_MODE) {
//            goTry(function () use($category, $levelMap, $format){
//                $taskRes = TaskManager::getInstance()->async(new LogTask($msg, $levelMap, $category));
//
//                //投递异常 转为协程记录
//                if ($taskRes < 0) {
//                    $this->console('log投递异常 code:' . $taskRes, self::LOG_LEVEL_WARNING);
////                    goto goLog;
//                    $this->logWrite($category, $levelMap, $format);
//                }
//            });
//        }else{
//            goLog:
//            goTry(function () use($category, $levelMap, $format){
//                $this->logWrite($category, $levelMap, $format);
//            });
//        }
    }

    public function logWrite($format, $levelMap, $category)
    {
        if (!isset($this->logHandler[$category])) {
            $this->logHandler[$category] = new \Monolog\Logger('log');
            $this->logHandler[$category]->pushHandler(new FileHourHandler(
                    $this->logDir, $category . '.log')
            );
        }

        if (method_exists($this->logHandler[$category], $levelMap)) {
            $this->logHandler[$category]->$levelMap($format);
        }else{
            $this->logHandler[$category]->info($format);
        }
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
        echo $this->getFormat($msg, $this->levelMap($logLevel), $category,true) . PHP_EOL;
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
    private function getFormat($msg, $logLevel, $category, $isConsole = false)
    {
        $str = '';

        //如果是console加上分类信息，文件会以分类为名则不用加
        if ($isConsole) {
            $date = (new DateTime())->format('Y-m-d\TH:i:s.uP');
            $str .= "[{$date}] [{$category}][{$logLevel}] ";
        }

        //加traceid
        if (RequestUtil::getRequest()) {
            $traceId = RequestUtil::getRequest()->getAttribute('traceId');
            $str .= "[{$traceId}] ";
        }

        //如果不是httpclient或trigger，则再获取打印日志的代码位置信息
        if (!in_array($category,[self::HTTP_REQUEST,self::TRIGGER])) {
            $logLocation = [];
            $debugTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
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
                    $logLocation = $tempLocation;
                }else{
                    break;
                }
            }
            if ($logLocation) {
                $str .= "[" . $logLocation['file'] . ":" . $logLocation['line'] . "]";
            }
        }

        //添加主体消息
        $str .= $msg;
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