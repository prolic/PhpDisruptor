<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\Exception\AlertException;
use PhpDisruptor\SequenceBarrierInterface;

class SequenceBarrierThread extends \Thread
{
    public $barrier;

    public $expectedNumberOfMessages;

    public $alerted;

    public function __construct(SequenceBarrierInterface $barrier, $expectedNumberOfMessages, $alerted)
    {
        $this->barrier = $barrier;
        $this->expectedNumberOfMessages = $expectedNumberOfMessages;
        $this->alerted = $alerted;
    }

    public function run()
    {
        try {
            $this->barrier->waitFor($this->expectedNumberOfMessages - 1);
        } catch (AlertException $e) {
            $this->alerted[0] = true;
        } catch (\Exception $e) {
            // ignore
        }
    }
}
