<?php

namespace PhpDisruptorTest\Pthreads;

use Cond;
use Mutex;
use PhpDisruptor\Pthreads\CyclicBarrier;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset\AbstractAwaiter;
use PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset\AwaiterFactory;
use PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset\FunOne;
use PhpDisruptorTest\Pthreads\CyclicBarrier\TestAsset\FunTwo;
use PhpDisruptorTest\Pthreads\CyclicBarrier\ToTheStartingGateTrait;

class CyclicBarrierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CyclicBarrier
     */
    protected $atTheStartingGate;

    protected $registeredDestroys = array();

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
        if (null === $c) {
            $t = $a->getResult();
            $this->assertNull($t);
        }
        // @todo for $c !== null
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

    public static function toTheStartingGate(CyclicBarrier $barrier)
    {
        try {
            $barrier->await(10000000); // 10 seks
        } catch (\Exception $e) {
            self::reset($barrier);
            throw $e;
        }
    }

    public static function reset(CyclicBarrier $barrier)
    {
        $barrier->reset();
        if ($barrier->isBroken()) {
            throw new \Exception('assertion failed in CyclicBarrierTest: expected broken = false');
        }
        if (0 != $barrier->getNumberWaiting()) {
            throw new \Exception('assertion failed in CyclicBarrierTest: expected number of waiting = 0');
        }
    }

    //----------------------------------------------------------------
    // Mechanism to get all victim threads into "running" mode.
    // The fact that this also uses CyclicBarrier is entirely coincidental.
    //----------------------------------------------------------------

    public function testNormalUse()
    {
        $mutexOne = Mutex::create(false);
        $mutexTwo = Mutex::create(false);

        $condOne = Cond::create();
        $condTwo = Cond::create();

        $this->registeredDestroys = array(
            'Mutex' => array(
                $mutexOne,
                $mutexTwo
            ),
            'Cond' => array(
                $condOne,
                $condTwo
            )
        );

        $this->atTheStartingGate = new CyclicBarrier($mutexOne, $condOne, 3, null);
        $this->atTheStartingGate->name = 'atTheStartingGate';
        $barrier = new CyclicBarrier($mutexTwo, $condTwo, 3);
        $barrier->name = 'barrier';
        $this->assertEquals($barrier->getParties(), 3);
        $awaiters = new AwaiterFactory($barrier, $this->atTheStartingGate);
        foreach (array(false, true) as $doReset) {
            for ($i = 0; $i < 4; $i++) {

                $a1 = $awaiters->newInstance();
                $a2 = $awaiters->newInstance();

                $a1->start();
                $a2->start();

                time_nanosleep(0, 100000);

                var_dump(microtime(1) . ' ' . 'Test Thread' . \Thread::getCurrentThreadId() );
                var_dump(microtime(1) . ' ' . get_class($a1) . ': '. $a1->getThreadId() );
                var_dump(microtime(1) . ' ' . get_class($a2) . ': ' . $a2->getThreadId() );

                self::toTheStartingGate($this->atTheStartingGate);

                var_dump(microtime(1) . ' ' . \Thread::getCurrentThreadId() . ' waiting...');
                $barrier->await();
                var_dump(microtime(1) . ' ' . \Thread::getCurrentThreadId() . ' waiting ok ...');
                var_dump(microtime(1) . ' ', $barrier, $this->atTheStartingGate);

                $a1->join();

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
        $this->destroy();
    }

    public function destroy()
    {
        foreach ($this->registeredDestroys as $destroyClass => $data)
        {
            foreach ($data as $int) {
                $destroyClass::destroy($int);
            }
        }
        $this->registeredDestroys = array();
    }
}
