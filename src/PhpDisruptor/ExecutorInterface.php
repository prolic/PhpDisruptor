<?php

namespace PhpDisruptor;

/**
 * ExecutorInterface
 * @todo: in java this is java.util.concurrent.Executor, so this may be factored out into an own library
 */
interface ExecutorInterface
{
    /**
     * @param RunnableInterface $r
     * @return void
     */
    public function execute(RunnableInterface $r);
}
