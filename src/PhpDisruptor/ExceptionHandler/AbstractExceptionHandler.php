<?php

namespace PhpDisruptor\ExceptionHandler;

use PhpDisruptor\Exception;
use ConcurrentPhpUtils\NoOpStackable;

abstract class AbstractExceptionHandler extends NoOpStackable implements ExceptionHandlerInterface
{
    /**
     * @var resource
     */
    public $fh;

    /**
     * Constructor
     *
     * @param string $file
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($file)
    {
        $path = dirname($file);
        if (!is_writable($path)) {
            throw new Exception\InvalidArgumentException(
                'Invalid path given or not writeable'
            );
        }
        $this->fh = fopen($file, 'a+b');
    }

    public function __destruct()
    {
        fclose($this->fh);
    }
}
