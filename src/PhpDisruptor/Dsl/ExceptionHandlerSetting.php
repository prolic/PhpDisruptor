<?php

namespace PhpDisruptor\Dsl;

use PhpDisruptor\EventClassCapableInterface;
use PhpDisruptor\EventHandlerInterface;
use PhpDisruptor\Exception;
use PhpDisruptor\ExceptionHandler\ExceptionHandlerInterface;
use Stackable;

class ExceptionHandlerSetting extends Stackable implements EventClassCapableInterface
{
    /**
     * @var string
     */
    public $eventClass;

    /**
     * @var EventHandlerInterface
     */
    public $eventHandler;

    /**
     * @var ConsumerRepository
     */
    public $consumerRepository;

    /**
     * Constructor
     *
     * @param EventHandlerInterface $eventHandler
     * @param ConsumerRepository $consumerRepository
     * @throws Exception\InvalidArgumentException if event handler and consumer repository differ in event classes
     */
    public function __construct(EventHandlerInterface $eventHandler, ConsumerRepository $consumerRepository)
    {
        if ($eventHandler->getEventClass() != $consumerRepository->getEventClass()) {
            throw new Exception\InvalidArgumentException(
                '$consumerRepository uses event class ' . $consumerRepository->getEventClass()
                . ' but $eventHandler uses event class ' . $eventHandler->getEventClass()
            );
        }
        $this->eventClass = $eventHandler->getEventClass();
        $this->eventHandler = $eventHandler;
        $this->consumerRepository = $consumerRepository;
    }

    public function run()
    {
    }

    /**
     * @inheritdoc
     */
    public function getEventClass()
    {
        return $this->eventClass;
    }

    /**
     * Specify the ExceptionHandler to use with the event handler
     *
     * @param ExceptionHandlerInterface $exceptionHandler
     * @return void
     */
    public function with(ExceptionHandlerInterface $exceptionHandler)
    {
        $this->consumerRepository->getEventProcessorFor($this->eventHandler)->setExceptionHandler($exceptionHandler);
        $this->consumerRepository->getBarrierFor($this->eventHandler)->alert();
    }
}
