<?php

namespace PhpDisruptor\ExceptionHandler;

use PhpDisruptor\Exception;
use Stackable;

abstract class AbstractExceptionHandler extends Stackable implements ExceptionHandlerInterface
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
        if (!is_writable($path)) {
            throw new Exception\InvalidArgumentException(
                'Invalid path given or not writeable'
            );
        }
        $this->fh = fopen($path, 'ba+');
    }

    public function run()
    {
    }
}
