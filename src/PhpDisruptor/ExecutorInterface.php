<?php

namespace PhpDisruptor;

interface ExecutorInterface
{
    /**
     * @param RunnableInterface $r
     * @return void
     */
    public function execute(RunnableInterface $r);
}
