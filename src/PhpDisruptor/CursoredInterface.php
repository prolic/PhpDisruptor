<?php

namespace PhpDisruptor;

interface CursoredInterface
{
    /**
     * Get current cursor value
     *
     * @return int
     */
    public function getCursor();
}
