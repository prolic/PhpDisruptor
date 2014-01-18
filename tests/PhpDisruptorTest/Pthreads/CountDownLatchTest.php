<?php

namespace PhpDisruptorTest\Pthreads;

use PhpDisruptor\Pthreads\CountDownLatch;
use PhpDisruptorTest\Pthreads\CountDownLatch\TestAsset;

class CountDownLatchTest extends \PHPUnit_Framework_TestCase
{
    private function toTheStartingGate(CountDownLatch $gate)
    {
        try {
            $gate->await();
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    private function awaiterFactories(CountDownLatch $latch, CountDownLatch $gate, $int)
    {
        if ($int == 1) {
            return new TestAsset\AwaiterFactoryOne($latch, $gate);
        } else {
            return new TestAsset\AwaiterFactoryTwo($latch, $gate, 100);
        }
    }

    public function testNormalUse()
    {
        $count = 0;
        $latch = new CountDownLatch(3);
        $a = array();

        for ($i = 0; $i < 3; $i++) {
            $gate = new CountDownLatch(4);
            $factory1 = $this->awaiterFactories($latch, $gate, 1);
            $factory2 = $this->awaiterFactories($latch, $gate, 0);
            $a[$count] = $factory1->getAwaiter();
            $a[$count++]->start();
            $a[$count] = $factory1->getAwaiter();
            $a[$count++]->start();
            $a[$count] = $factory2->getAwaiter();
            $a[$count++]->start();
            $a[$count] = $factory2->getAwaiter();
            $a[$count++]->start();
            $this->toTheStartingGate($gate);
            $latch->countDown();
            $this->checkCount($latch, 2-$i);
        }

        for ($i = 0; $i < 12; $i++) {
            $a[$i]->join();
        }

        for ($i = 0; $i < 12; $i++) {
            $this->checkResult($a[$i], null);
        }
    }

    private function checkCount(CountDownLatch $b, $expected)
    {
        if ($b->getCount() != $expected) {
            $this->fail('Count = ' . $b->getCount() . ', expected = ' . $expected);
        }
    }

    private function checkResult(TestAsset\AbstractAwaiter $a, $type)
    {
        $t = $a->getResult();
        if (! (($t == null && $type == null) || $t instanceof $type)) {
            $this->fail('Mismatch: ' . get_class($t) . ", " . $type);
        }
    }
}
