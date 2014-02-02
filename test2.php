<?php

class ThreadOne extends Thread
{
    public $mutex;

    public $cond;

    public $name;

    public function __construct($mutex, $cond, $name)
    {
        $this->mutex = $mutex;
        $this->cond = $cond;
        $this->name = $name;
    }

    public function run()
    {
        echo $this->name . ' locking' . PHP_EOL;
        $this->wait(100);
        Mutex::lock($this->mutex);
        echo $this->name . ' locking ok' . PHP_EOL;

        echo $this->name . ' waiting' . PHP_EOL;
        $this->wait(100);
        Cond::wait($this->cond, $this->mutex);
        echo $this->name . ' waiting ok' . PHP_EOL;



        echo $this->name . ' ended' . PHP_EOL;
    }
}

$m = Mutex::create(false);
$c = Cond::create();

$t1 = new ThreadOne($m, $c, 't1');
$t1->start();
$t2 = new ThreadOne($m, $c, 't2');
$t2->start();


sleep(2);

Cond::broadcast($c);

sleep(2);

Cond::broadcast($c);

$t1->join();
$t2->join();