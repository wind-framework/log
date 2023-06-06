<?php

namespace Wind\Log\Handler;

use Monolog\Logger;

abstract class AsyncAbstractHandler extends \Monolog\Handler\AbstractProcessingHandler
{

    /** @var string */
    protected $group;

    /** @var int */
    protected $index;

    /**
     * @param string $group Log group in config
     * @param int $index Hander index in log group
     * @param int|string $level  The minimum logging level at which this handler will be triggered
     * @param bool       $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(string $group, int $index, $level = Logger::DEBUG, bool $bubble = true)
    {
        $this->group = $group;
        $this->index = $index;
        parent::__construct($level, $bubble);
    }

    public function handle(array $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        if ($this->processors) {
            $record = $this->processRecord($record);
        }

        $this->write($record);

        return false === $this->bubble;
    }

}
