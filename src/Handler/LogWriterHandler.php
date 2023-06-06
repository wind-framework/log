<?php

namespace Wind\Log\Handler;

use Wind\Base\Channel;

/**
 * Wind Writer Log Handler
 */
class LogWriterHandler extends AsyncAbstractHandler
{

    const QUEUE_CHANNEL = 'async-log-writer';

    /** @var \Monolog\Handler\AbstractHandler */
    protected $handler;

    protected function write(array $record): void
    {
        $channel = di()->get(Channel::class);
        $channel->enqueue(self::QUEUE_CHANNEL, [$this->group, $this->index, $record]);
    }

}