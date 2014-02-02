<?php

namespace PhpDisruptorTest\Pthreads;

use Cond;
use Mutex;
use PhpDisruptor\Pthreads\CyclicBarrier;
use PhpDisruptor\Pthreads\Exception\BrokenBarrierException;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset\AbstractAwaiter;
use PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset\AwaiterIterator;
use PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset\FunOne;
use PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset\FunTwo;

class CyclicBarrierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CyclicBarrier
     */
    protected static $atTheStartingGate;

    /**
     * @param CyclicBarrier $barrier
     * @return void
     */
    protected function checkBroken(CyclicBarrier $barrier)
    {
        $this->assertTrue($barrier->isBroken());
        $this->assertEquals($barrier->getNumberWaiting(), 0);

        $funStack = new StackableArray();
        $funStack[] = new FunOne($barrier);
        $funStack[] = new FunTwo($barrier);

        $this->throws('PhpDisruptor\Pthreads\Exception\BrokenBarrierException', $funStack);
    }

    /**
     * @param AbstractAwaiter $a
     * @param $c exception classname
     * @return void
     */
    protected function checkResult(AbstractAwaiter $a, $c)
    {
        $t = $a->getResult();

        if (! (($t === null && $c === null) || ($c !== null && $t instanceof $c))) {
            $this->fail(
                'Mismatch in thread ' . $a->getName() . ': ' . get_class($t) . ', ' . ($c === null ? '<null>' : $c)
            );
        }
    }

    protected function throws($exceptionClassname, StackableArray $funStack)
    {
        foreach ($funStack as $fun) {
            try {
                /* @var FunOne $fun*/
                $fun->f();
                $this->fail('Expected ' . $exceptionClassname . ' not thrown');
            } catch (\Exception $e) {
                if (!$e instanceof $exceptionClassname) {
                    $this->fail('Unknown exception');
                }
            }
        }
    }

    protected function toTheStartingGate()
    {
        try {
            self::$atTheStartingGate->await(10000000); // 10 seks
        } catch (\Exception $e) {
            self::reset($barrier);
            throw $e;
        }
    }

    protected function reset(CyclicBarrier $barrier)
    {
        $barrier->reset();
        if ($barrier->isBroken()) {
            throw new \Exception('assertion failed in CyclicBarrierTest: expected broken = false');
        }
        if (0 != $barrier->getNumberWaiting()) {
            throw new \Exception('assertion failed in CyclicBarrierTest: expected number of waiting = 0');
        }
    }

    protected function setUp()
    {
        //----------------------------------------------------------------
        // Mechanism to get all victim threads into "running" mode.
        // The fact that this also uses CyclicBarrier is entirely coincidental.
        //----------------------------------------------------------------
        self::$atTheStartingGate = new CyclicBarrier(3);

        // name is just for debugging now
        self::$atTheStartingGate->name = 'atTheStartingGate';
    }

    public function testNormalUse()
    {
        $barrier = new CyclicBarrier(3);
        // name is just for debugging now
        $barrier->name = 'barrier';

        $this->assertEquals($barrier->getParties(), 3);

        $awaiters = new AwaiterIterator($barrier, self::$atTheStartingGate);

        foreach (array(false, true) as $doReset) {
            for ($i = 0; $i < 4; $i++) {

                $a1 = $awaiters->next();
                $a1->start();
                $a2 = $awaiters->next();
                $a2->start();

                self::toTheStartingGate();

                $barrier->await();

                $a1->join();
                $a2->join();

                $this->checkResult($a1, null);
                $this->checkResult($a2, null);

                $this->assertFalse($barrier->isBroken());
                $this->assertEquals($barrier->getParties(), 3);
                $this->assertEquals($barrier->getNumberWaiting(), 0);

                if ($doReset) {
                    $this->reset($barrier);
                }
            }
        }
    }

    /*
    public function testOneThreadKilled()
    {
        $barrier = new CyclicBarrier(3);
        $awaiters = new AwaiterIterator($barrier, self::$atTheStartingGate);
        for ($i = 0; $i < 4; $i++) {
            $a1 = $awaiters->next();
            $a1->start();
            $a2 = $awaiters->next();
            $a2->start();

            self::toTheStartingGate();

            $a1->kill();

            $a1->join();
            $a2->join();

            //$this->checkResult($a1);
            $this->checkResult($a2, 'PhpDisruptor\Pthreads\Exception\BrokenBarrierException');
            $this->checkBroken($barrier);
            $this->reset($barrier);
        }

    }
    */

}
