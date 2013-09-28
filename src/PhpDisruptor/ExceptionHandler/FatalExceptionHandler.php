<?php

namespace PhpDisruptor\ExceptionHandler;

use PhpDisruptor\Exception;

final class FatalExceptionHandler extends AbstractExceptionHandler
{
    /**
     * @inheritdoc
     */
    public function handleEventException(\Exception $ex, $sequence, $event)
    {
        fwrite($this->fh, 'ERR: Exception processing: ' . $sequence . ' ' . $event);
        throw new Exception\RuntimeException($ex);
    }

    /**
     * @inheritdoc
     */
    public function handleOnStartException(\Exception $ex)
    {
        fwrite($this->fh, 'ERR: Exception during onStart()');
    }

    /**
     * @inheritdoc
     */
    public function handleOnShutdownException(\Exception $ex)
    {
        fwrite($this->fh, 'ERR: Exception during onShutdown()');
    }
}
