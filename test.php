<?php

    class BoolStack extends Stackable
    {
        public $result;

        public function __construct()
        {
            $this->result = false;
        }

        public function run()
        {
        }
    }

    class T extends Thread
    {
        public function __construct($mutex, $cond, $name)
        {
            $this->mutex = $mutex;
            $this->cond = $cond;
            $this->threadname = $name;
            $this->bool = new BoolStack();
        }

        public function run()
        {
            Mutex::lock($this->mutex);
            echo $this->threadname . ' waiting...' . PHP_EOL;
            if (Cond::wait($this->cond, $this->mutex)) {
                echo $this->threadname . ' waiting finished.' . PHP_EOL;
            } else {
                Mutex::unlock($this->mutex);
                throw new Exception($this->threadname . ' cannot happen?');
            }
            Mutex::unlock($this->mutex);
        }

        public function destroy()
        {
            echo 'destroying ' . Thread::getCurrentThreadId() . PHP_EOL;
            Cond::destroy($this->cond);
            Mutex::destroy($this->mutex);
            echo 'destroyed ' . Thread::getCurrentThreadId() . PHP_EOL;
        }
    }

    $m = Mutex::create(false);
    $c = Cond::create();

    $t1 = new T($m, $c, 'thread 1');
    $t2 = new T($m, $c, 'thread 2');

    $t1->start();
    $t2->start();

    time_nanosleep(2, 1000); // sleep for a while, to threads have to to call wait()

    Cond::broadcast($c);

    $t1->join();
    $t2->join();

    Cond::destroy($c);
    Mutex::destroy($m);
    echo 'end..' . PHP_EOL;

var_dump($t1);
