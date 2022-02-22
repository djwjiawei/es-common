<?php
/**
 * Created by PhpStorm.
 * User: dongjw
 * Date: 2021/11/29
 * Time: 15:34
 */

namespace EsSwoole\Base\Common;

use EasySwoole\Component\Process\Manager;
use EasySwoole\EasySwoole\ServerManager;

/**
 * 进程消息同步
 *
 * @author dongjw <dongjw.1@jifenn.com>
 */
class ProcessSync
{

    /**
     * 同步到worker进程
     *
     * @param string $body
     * @param int    $workerId
     *
     * @return mixed
     * User: dongjw
     * Date: 2021/11/29 15:59
     */
    public static function syncWorker($body, $workerId)
    {
        return ServerManager::getInstance()->getSwooleServer()->sendMessage($body, $workerId);
    }

    /**
     * 同步到用户自定义进程
     *
     * @param string $body
     * @param int    $pid
     *
     * @return bool|mixed
     * User: dongjw
     * Date: 2021/11/29 15:59
     */
    public static function syncCustomProcess($body, $pid)
    {
        $process = Manager::getInstance()->getProcessByPid($pid);
        if (!$process) {
            return false;
        }

        return $process->getProcess()->write($body);
    }

    /**
     * 通过进程id同步
     *
     * @param string $body
     * @param int    $pid
     *
     * @return bool|int|mixed|null
     * User: dongjw
     * Date: 2021/11/29 16:00
     */
    public static function syncByPid($body, $pid)
    {
        $tableProcess = Manager::getInstance()->getProcessTable()->get($pid);
        if (!$tableProcess) {
            return false;
        }

        return self::syncProcess(
            $tableProcess['group'], $tableProcess['name'], $body, $pid
        );
    }

    /**
     * 同步到全部进程
     *
     * @param string $body
     * @param array  $noPidArr
     *
     * @return array
     * User: dongjw
     * Date: 2021/11/29 16:01
     */
    public static function syncAllProcess($body, $noPidArr = [])
    {
        $return = [];

        $noSync = [];
        foreach ($noPidArr as $noPid) {
            $noSync[$noPid] = 1;
        }

        $serverName  = config('SERVER_NAME');
        $noSyncGroup = [
            "{$serverName}.Bridge"  => 1,
            "{$serverName}.Crontab" => 1,
        ];

        $currentPid = posix_getpid();
        foreach (Manager::getInstance()->info() as $pid => $item) {
            if (isset($noSync[$pid]) || isset($noSyncGroup[$item['group']]) || $currentPid == $pid) {
                continue;
            }

            $return[$pid] = self::syncByPid($body, $pid);
        }

        return $return;
    }

    /**
     * 同步进程消息
     *
     * @param string $group
     * @param string $name
     * @param string $body
     * @param int    $pid
     *
     * @return bool|mixed
     * User: dongjw
     * Date: 2022/2/22 15:40
     */
    private static function syncProcess($group, $name, $body, $pid)
    {
        $serverName = config('SERVER_NAME');
        switch ($group) {
            case "{$serverName}.Worker":
                $workerId = explode('.', $name)[2];

                return self::syncWorker($body, $workerId);

            default:
                return self::syncCustomProcess($body, $pid);
        }
    }
}
