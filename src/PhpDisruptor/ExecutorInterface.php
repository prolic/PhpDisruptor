<?php

namespace PhpDisruptor;
use PhpDisruptor\EventProcessor\AbstractEventProcessor;

/**
 * ExecutorInterface
 * @todo: in java this is java.util.concurrent.Executor, so this may be factored out into an own library
 */
interface ExecutorInterface
{
    /**
     * @param AbstractEventProcessor $eventProcessor
     * @return void
     */
    public function execute(AbstractEventProcessor $eventProcessor);
}
