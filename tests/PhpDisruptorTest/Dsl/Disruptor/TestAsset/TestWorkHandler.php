<?php

namespace PhpDisruptorTest\Dsl\Disruptor\TestAsset;

use ConcurrentPhpUtils\CasThreadedMemberTrait;
use Threaded;
use Exception;
use PhpDisruptor\WorkHandlerInterface;

class TestWorkHandler extends Threaded implements WorkHandlerInterface
{
    public $stopped;

    public $readyToProcessEvent;

    use CasThreadedMemberTrait;

    public function __construct()
    {
        $this->stopped = false;
        $this->readyToProcessEvent = false;
    }

    /**
     * Return the used event class name
     *
     * @return string
     */
    public function getEventClass()
    {
        return 'PhpDisruptorTest\TestAsset\TestEvent';
    }

    /**
     * @param object $event
     * @return void
     * @throws Exception
     */
    public function onEvent($event)
    {
        $this->waitForAndSetFlag(false);
    }

    public function processEvent($event)
    {
        $this->waitForAndSetFlag(true);
    }

    public function stopWaiting()
    {
        $this->stopped = true;
    }

    public function waitForAndSetFlag($bool)
    {
        while (!$this->casMember('readyToProcessEvent', !$bool, $bool)) {
            $this->wait(1);
        }
    }
}
