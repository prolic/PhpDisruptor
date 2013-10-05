<?php

namespace PhpDisruptor\ExceptionHandler;

final class IgnoreExceptionHandler extends AbstractExceptionHandler
{
    /**
     * @inheritdoc
     */
    public function handleEventException(\Exception $ex, $sequence, $event)
    {
        fwrite($this->fh, 'INFO: Exception processing: ' . $sequence . ' ' . $event);
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
