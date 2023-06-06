<?php

namespace Wind\Log;

use Monolog\Logger;
use RuntimeException;
use Wind\Log\Handler\LogWriterHandler;
use Wind\Log\Handler\TaskWorkerHandler;

class LogFactory
{

    /** Task Worker async mode */
    const ASYNC_TASK_WORKER = 0;

    /** Log Writer Process async mode */
    const ASYNC_LOG_WRITER = 1;

    private $handlers = [];

    /**
     * Get Logger
     *
     * @param string $name
     * @param string $group
     * @return Logger
     */
    public function get($name='app', $group='default')
    {
        $handlers = $this->getHandlers($group);
        return new Logger($name, $handlers);
    }

    /**
     * Get handlers for group
     *
     * @param string $group
     * @return \MonoLog\Handler\HandlerInterface[]
     */
    public function getHandlers($group)
    {
        if (isset($this->handlers[$group])) {
            $handlers = $this->handlers[$group];
        } else {
            $setting = \config('log.'.$group);

            if (empty($setting)) {
                throw new \Exception("Logger group '$group' not found in config.");
            }

            //支持多 Handlers 或单 Handler 配置
            if (!isset($setting['handlers'])) {
                $setting['handlers'] = [];
            }

            if (isset($setting['handler'])) {
                $setting['handlers'][] = $setting['handler'];
            }

            if (empty($setting['handlers'])) {
                throw new \Exception("No handlers config for logger group '$group'!");
            }

            $handlers = [];

            foreach ($setting['handlers'] as $i => $hc) {
                /**
                 * 关于日志的写入模式总共有以下几种情况
                 *
                 * 1. 普通同步模式，无论何处调用，均是该进程直接写入。
                 * 2. 在正常业务进程中调用的异步写入，日志异步发送至 Task Worker 中，由 Task Worker 同步写入。
                 * 3. 异步发送到 Task Worker 中运行的任务，本身写同步日志，与 1 相同。
                 * 4. 异步发送到 Task Worker 中运行的任务，写日志的 Handler 有 async 标记，此时要当成同步形式写入（已经在 Task Worker 中无需再发）。
                 */

                $async = $hc['async'] ?? null;
                $args = $hc['args'] ?? [];

                if ($async === null) {
                    $handler = di()->make($hc['class'], $args);
                } elseif ($async === self::ASYNC_TASK_WORKER || $async === true) {
                    if (defined('TASK_WORKER')) {
                        $handler = di()->make($hc['class'], $args);
                    } else {
                        $handler = new TaskWorkerHandler($group, $i);
                    }
                } elseif ($async === self::ASYNC_LOG_WRITER) {
                    if (defined('LOG_WRITER_PROCESS')) {
                        $handler = di()->make($hc['class'], $args);
                    } else {
                        $handler = new LogWriterHandler($group, $i);
                    }
                } else {
                    throw new RuntimeException("Unknown async option for log group '$group'.");
                }

                $fmt = $hc['formatter'] ?? $setting['formatter'] ?? false;
                if ($fmt) {
                    $formatter = di()->make($fmt['class'], $fmt['args'] ?? []);
                    $handler->setFormatter($formatter);
                }

                $handlers[$i] = $handler;
            }

            return $handlers;
        }
    }

}
