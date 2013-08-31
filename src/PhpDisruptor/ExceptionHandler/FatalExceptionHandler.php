<?php

namespace PhpDisruptor\ExceptionHandler;

use PhpDisruptor\Exception;
use Zend\Log\LoggerInterface;

final class FatalExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function handleEventException(\Exception $ex, $sequence, $event)
    {
        $this->logger->err('Exception processing: ' . $sequence . ' ' . $event);
        throw new Exception\RuntimeException($ex);
    }

    /**
     * @inheritdoc
     */
    public function handleOnStartException(\Exception $ex)
    {
        $this->logger->err('Exception during onStart()');
    }

    /**
     * @inheritdoc
     */
    public function handleOnShutdownException(\Exception $ex)
    {
        $this->logger->err('Exception during onShutdown()');
    }
}
