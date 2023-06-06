<?php

namespace Wind\Log\Handler;

abstract class AsyncAbstractHandler extends \Monolog\Handler\AbstractProcessingHandler
{

    /** @var string */
    protected $group;

    /** @var int */
    protected $index;

    /**
     * @param string $group Log group in config
     * @param int $index Hander index in log group
     */
    public function __construct(string $group, int $index)
    {
        $this->group = $group;
        $this->index = $index;
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
