<?php

namespace PhpDisruptorTest\TestAsset;

final class StubEvent
{
    /**
     * @var int
     */
    private $value;

    /**
     * @var string
     */
    private $testString;

    public static $translator;

    public function __construct($value)
    {
        if (null === self::$translator) {
            self::$translator = new EventTranslator();
        }
        $this->value = $value;
    }

    public function copy(self $event)
    {
        $this->value = $event->value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getTestString()
    {
        return $this->testString;
    }

    public function setTestString($testString)
    {
        $this->testString = $testString;
    }
}
