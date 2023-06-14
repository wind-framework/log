<?php

namespace Wind\Log\Handler;

use Wind\Log\LogFactory;
use Wind\Task\Task;

use function Amp\Promise\rethrow;

/**
 * Wind Task Worker Handler
 */
class TaskWorkerHandler extends AsyncAbstractHandler
{

    /**
     * 将日志发送至 TaskWorker 处理
     *
     * @param array $record
     */
    protected function write(array $record): void
    {
        // reset LogFactory to prevent call TaskWorkerHandler in TaskWorker
        if (defined('TASK_WORKER')) {
            di()->get(LogFactory::class)->reset();
        }

        rethrow(Task::execute([self::class, 'log'], $this->group, $this->index, $record));
    }

    /**
     * 调用原 Handler 处理 $record
     *
     * @param string $group
     * @param int $index
     * @param array $record
     * @return bool
     */
    public static function log(string $group, int $index, array $record)
    {
        $factory = di()->get(LogFactory::class);
        $handler = $factory->getHandlers($group)[$index];
        return $handler->handle($record);
    }

}
