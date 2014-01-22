<?php

namespace PhpDisruptorTest\Pthreads;

use PhpDisruptor\Pthreads\CyclicBarrier;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\Pthreads\TimeUnit;
use PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset\AbstractAwaiter;
use PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset\AwaiterIterator;
use PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset\FunOne;
use PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset\FunTwo;

class CyclicBarrierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CyclicBarrier
     */
    protected $atTheStartingGate;

    protected function setUp()
    {
        $this->atTheStartingGate = new CyclicBarrier(3);
    }

    /**
     * @param CyclicBarrier $barrier
     * @return void
     */
    public static function checkBroken(CyclicBarrier $barrier)
    {
        self::assertTrue($barrier->isBroken());
        self::assertEquals($barrier->getNumberWaiting(), 0);

        $funStack = new StackableArray();
        $funStack[] = new FunOne($barrier);
        $funStack[] = new FunTwo($barrier);

        self::throws('PhpDisruptor\Pthreads\Exception\BrokenBarrierException', $funStack);
    }

    /**
     * @param CyclicBarrier $barrier
     * @return void
     */
    public static function reset(CyclicBarrier $barrier)
    {
        $barrier->reset();
        self::assertTrue(!$barrier->isBroken());
        self::assertEquals($barrier->getNumberWaiting(), 0);
    }

    /**
     * @param AbstractAwaiter $a
     * @param $c exception classname
     * @return void
     */
    public static function checkResult(AbstractAwaiter $a, $c)
    {
        $t = $a->getResult();
        if (! (($t == null && $c == null) || ($c != null && $t instanceof $c))) {
            self::fail('Mismatch in thread ' .
                $a->getName() . ": " .
                $t . ", " .
                ($c == null ? "<null>" : $c));
        }
    }

    public static function throws($exceptionClassname, StackableArray $funStack)
    {
        foreach ($funStack as $fun) {
            try {
                /* @var FunOne $fun*/
                $funStack->f();
                self::fail('Expected ' . $exceptionClassname . ' not thrown');
            } catch (\Exception $e) {
                if (!$e instanceof $exceptionClassname) {
                    self::fail('Unknown exception');
                }
            }
        }
    }

    //----------------------------------------------------------------
    // Mechanism to get all victim threads into "running" mode.
    // The fact that this also uses CyclicBarrier is entirely coincidental.
    //----------------------------------------------------------------

    /**
     * @return void
     */
    public function toTheStartingGate()
    {
        try {
            $this->atTheStartingGate->await(10);
        } catch (\Exception $e) {
            $this->reset($this->atTheStartingGate);
            $this->fail($e->getMessage());
        }
    }

    public function testNormalUse()
    {
        try {
            $barrier = new CyclicBarrier(3);
            $this->assertEquals($barrier->getParties(), 3);
            $awaiters = new AwaiterIterator($this->atTheStartingGate);
            foreach (array(false, true) as $doReset) {
                for ($i = 0; $i < 4; $i++) {
                    $a1 = $awaiters->next();
                    $a2 = $awaiters->next();
                    $a1->start();
                    $a2->start();
                    $this->toTheStartingGate();
                    $barrier->await();
                    $a1->join();
                    $a2->join();
                    CyclicBarrierTest::checkResult($a1, null);
                    CyclicBarrierTest::checkResult($a2, null);
                    self::assertTrue(!$barrier->isBroken());
                    $this->assertEquals($barrier->getParties(), 3);
                    $this->assertEquals($barrier->getNumberWaiting(), 0);
                    if ($doReset) {
                        self::reset($barrier);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

}
