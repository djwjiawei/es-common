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

/**
 * 日志处理器
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class Logger implements LoggerInterface
{
    use Singleton;

    const TASK_MODE = 1;

    /**
     * 日志根目录
     *
     * @var string
     */
    private $logDir;

    /**
     * 日志处理器
     *
     * @var array TimeSizeHandler
     */
    protected $logHandler = [];

    /**
     * 格式化日志类
     *
     * @var FormatterInterface
     */
    protected $formatHandler;

    /**
     * Logger constructor.
     *
     * @param string|null $logDir
     */
    public function __construct(string $logDir = null)
    {
        if (empty($logDir)) {
            $logDir = EASYSWOOLE_LOG_DIR;
        }

        $this->logDir = $logDir;

        $this->formatHandler = new DefaultFormatter();
    }

    /**
     * 记录日志
     *
     * @param string|null $msg      日志内容
     * @param int         $logLevel 日志级别
     * @param string      $category 日志分类/会以该分类作文件名
     */
    public function log(?string $msg, int $logLevel = self::LOG_LEVEL_DEBUG, string $category = 'info')
    {
        $format = $this->getFormat($msg, $this->levelMap($logLevel), false);

        go(
            function () use ($format, $category) {
                try {
                    $this->logWrite($format, $category);
                } catch (\Throwable $e) {
                    $this->console('log异常 msg:' . $e->getMessage(), self::LOG_LEVEL_WARNING);
                }
            }
        );
    }

    /**
     * 写日志
     *
     * @param string $format
     * @param string $category
     * User: dongjw
     * Date: 2022/2/22 17:05
     */
    public function logWrite($format, $category)
    {
        if (!isset($this->logHandler[$category])) {
            $this->logHandler[$category] = new TimeSizeHandler($this->logDir, $category . '.log');
        }

        $this->logHandler[$category]->write($format);
    }

    /**
     * 输出到控制台
     *
     * @param string|null $msg
     * @param int         $logLevel
     * @param string      $category
     * User: dongjw
     * Date: 2021/11/19 14:42
     */
    public function console(?string $msg, int $logLevel = self::LOG_LEVEL_DEBUG, string $category = 'info')
    {
        echo $this->getFormat($msg, $this->levelMap($logLevel), true);
    }

    /**
     * 获取日志格式化处理器
     *
     * @return DefaultFormatter|FormatterInterface
     * User: dongjw
     * Date: 2022/2/23 18:43
     */
    protected function getFormatHandle()
    {
        return $this->formatHandler;
    }

    /**
     * 设置日志格式化处理器
     *
     * @param FormatterInterface $handle
     *
     * User: dongjw
     * Date: 2022/2/23 18:43
     */
    public function setFormatHandle(FormatterInterface $handle)
    {
        $this->formatHandler = $handle;
    }

    /**
     * 获取日志格式化内容
     *
     * @param string $msg
     * @param string $logLevel
     * @param bool   $isConsole
     *
     * @return string
     * User: dongjw
     * Date: 2021/11/19 14:42
     */
    private function getFormat($msg, $logLevel, $isConsole)
    {
        $debugTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        //清楚当前调用栈
        array_shift($debugTrace);
        //清楚框架logger调用栈
        array_shift($debugTrace);

        $logLocation = [];
        for ($i = 0; $i < 6; $i++) {
            //最多查询6层
            $tempLocation = array_shift($debugTrace);
            if (!$tempLocation) {
                break;
            }

            if ($tempLocation['class'] == \EasySwoole\EasySwoole\Logger::class) {
                $tempBeforeLocation = $tempLocation;
            } else {
                //如果不是说明调用log停止
                $logLocation = $tempBeforeLocation ?? null;
                break;
            }
        }

        return $this->getFormatHandle()->format($msg, $logLevel, $isConsole, $logLocation);
    }

    /**
     * 隔离级别对应的字符串
     *
     * @param int $level
     *
     * @return string
     * User: dongjw
     * Date: 2022/2/22 17:08
     */
    private function levelMap(int $level)
    {
        switch ($level) {
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
