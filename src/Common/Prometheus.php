<?php

namespace EsSwoole\Base\Common;

use EasySwoole\EasySwoole\Logger;

/**
 * Prometheus类
 *
 * @author wangy.3 <wangy.3@jifenn.com>
 */
class Prometheus
{
    public $tables         = []; // 共享内存表集合

    public $currentTable   = null; // 当前使用内存表

    public $fileName       = 'metrics.log';

    public $finishFileName = 'finish.log';

    public $tmpDir         = '/tmp/prometheus_metrics_data/';

    /**
     * Prometheus constructor.
     */
    public function __construct()
    {
        if (empty($this->tables)) { //初始化数据
            $this->currentTable = $this->getNewTable();
            $this->tables[]     = $this->currentTable;
        }
    }

    /**
     * 生成指标表
     *
     * @return \Swoole\Table|null
     */
    public function getNewTable(): ?\Swoole\Table
    {
        //初始化指标表
        $table = new \Swoole\Table(10240);
        $table->column('num', \Swoole\Table::TYPE_INT, 8); // 指标数
        $table->column('labels', \Swoole\Table::TYPE_STRING, 256); // 筛选条件
        $table->column('desc', \Swoole\Table::TYPE_STRING, 50); // 指标描述
        if (false === $table->create()) {
            Logger::getInstance()->waring('create Swoole\\Table failed');

            return null;
        }

        return $table;
    }

    /**
     * 记录指标
     *
     * @param string $key    指标名,最长43字节（key最大63字节，'|timestamp|crc32' 占20字节）
     * @param int    $num    指标数
     * @param array  $labels 筛选条件
     * @param string $desc   指标描述
     *
     * @return bool
     *
     * @example Di::getInstance()->get('prometheus')->add('return_num', 4, ['category' => 'clothes', 'type' => 1]);
     */
    public function add(string $key, int $num = 1, array $labels = [], string $desc = ''): bool
    {
        $table = $this->getCurrentTable();
        if (is_null($table)) {
            return false;
        }

        $time         = strtotime(date('Y-m-d H:i')) + 60; // 每分钟记录前一分钟内统计
        $formatLabels = empty($labels) ? '' : $this->formatLabels($labels);
        $tableKey     = $key . '|' . $time . '|' . hash('crc32', $formatLabels);
        if ($table->exist($tableKey)) {
            $table->incr($tableKey, 'num', $num ?? 0);
        } else { // 初始化数据
            empty($desc) && $desc = $key;
            $table->set($tableKey, ['num' => $num, 'labels' => $formatLabels, 'desc' => $desc]);
        }

        return true;
    }

    /**
     * 获取当前使用内存表
     *
     * @return \Swoole\Table
     */
    public function getCurrentTable(): \Swoole\Table
    {
        if (is_null($this->currentTable) || $this->currentTable->count() > 10000) { // 表长度大于1000时，初始化一张新表
            $table              = $this->getNewTable();
            $this->tables[]     = $table;
            $this->currentTable = $table;
        }

        return $this->currentTable;
    }

    /**
     * 删除指标key
     *
     * @param \Swoole\Table $table
     * @param string        $tableKey
     *
     * @return bool
     */
    public function delKey(\Swoole\Table $table, string $tableKey): bool
    {
        $table->del($tableKey);

        return true;
    }

    /**
     * 删除指标table
     *
     * @param \Swoole\Table $table
     *
     * @return bool
     */
    public function delTable(\Swoole\Table $table): bool
    {
        $key = array_search($table, $this->tables);
        if (false !== $key) {
            unset($this->tables[$key]);
        }

        return true;
    }

    /**
     * 指标落地到日志
     *
     * @param array $timeDatas
     *
     * @return bool
     */
    public function writeData(array $timeDatas): bool
    {
        foreach ($timeDatas as $time => $datas) {
            $dataDir = $this->tmpDir . date('Ymd/H/i/', $time);
            $str     = '';
            if (!is_dir($dataDir)) {
                try {
                    mkdir($dataDir, 0777, true);
                } catch (\Throwable $throwable) {
                    return false;
                }
            }

            foreach ($datas as $metricsKey => $labels) {
                $str .= $this->formatHead($metricsKey, $labels['desc']);
                unset($labels['desc']);
                foreach ($labels as $data) {
                    $str .= $this->formatData($metricsKey, $data);
                }
            }

            $str = trim($str);
            file_put_contents($dataDir . $this->fileName, $str, FILE_APPEND);
            $this->writeOk($time);
        }

        return true;
    }

    /**
     * 格式化指标数据
     *
     * @param string $metricsKey
     * @param array  $data
     *
     * @return string
     */
    public function formatData(string $metricsKey, array $data): string
    {
        $metricsStr = $metricsKey;
        if (!empty($data['labels'])) {
            $metricsStr .= $data['labels'];
        }

        return "$metricsStr {$data['num']}" . PHP_EOL;
    }

    /**
     * 打印统计完成标签
     *
     * @param int $time
     *
     * @return bool
     */
    public function writeOk(int $time): bool
    {
        $dataDir = $this->tmpDir . date('Ymd/H/i/', $time);
        if (!is_dir($dataDir)) {
            try {
                mkdir($dataDir, 0777, true);
            } catch (\Throwable $throwable) {
                return false;
            }
        }

        file_put_contents($dataDir . $this->finishFileName, 'ok');

        return true;
    }

    /**
     * 格式化筛选标签
     *
     * @param array $labels
     *
     * @return string
     */
    private function formatLabels(array $labels): string
    {
        $str = '{';
        foreach ($labels as $key => $val) {
            $str .= $key . '="' . $val . '",';
        }

        return trim($str, ',') . '}';
    }

    /**
     * 格式化指标头
     *
     * @param string $type
     * @param string $desc
     *
     * @return string
     */
    private function formatHead(string $type, string $desc): string
    {
        return "# HELP $desc" . PHP_EOL . "# TYPE $type counter" . PHP_EOL;
    }
}
