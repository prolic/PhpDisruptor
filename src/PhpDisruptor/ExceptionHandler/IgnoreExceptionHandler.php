<?php

namespace PhpDisruptor\ExceptionHandler;

use PhpDisruptor\Exception;
use Stackable;

final class IgnoreExceptionHandler extends Stackable implements ExceptionHandlerInterface
{
    /**
     * @var resource
     */
    public $fh;

    /**
     * Constructor
     *
     * @param string $path
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($path)
    {
        if (!is_file($path) || !is_writable($path)) {
            throw new Exception\InvalidArgumentException(
                'Invalid path given or not writeable'
            );
        }
        $this->fh = fopen($path, 'ba+');
    }

    public function run()
    {
    }

    /**
     * @inheritdoc
     */
    public function handleEventException(\Exception $ex, $sequence, $event)
    {
        fwrite($this->fh, 'INFO: Exception processing: "' . $sequence);
    }

    /**
     * @inheritdoc
     */
    public function handleOnStartException(\Exception $ex)
    {
        fwrite($this->fh, 'INFO: Exception during onStart()');
    }

    /**
     * @inheritdoc
     */
    public function handleOnShutdownException(\Exception $ex)
    {
        fwrite($this->fh, 'INFO: Exception during onShutdown()');
    }
}
