<?php

namespace PhpDisruptor\ExceptionHandler;

use Exception;
use Zend\Log\LoggerInterface;

final class IgnoreExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function handleEventException(Exception $ex, $sequence, $event)
    {
        $this->logger->info('Exception processing: "' . $sequence);
    }

    /**
     * @inheritdoc
     */
    public function handleOnStartException(Exception $ex)
    {
        $this->logger->info('Exception during onStart()');
    }

    /**
     * @inheritdoc
     */
    public function handleOnShutdownException(Exception $ex)
    {
        $this->logger->info('Exception during onShutdown()');
    }
}
