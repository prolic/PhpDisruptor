<?php


namespace PhpDisruptor\Pthreads;

use MabeEnum\Enum;

/**
 * A TimeUnit represents time durations at a given unit of
 * granularity and provides utility methods to convert across units,
 * and to perform timing and delay operations in these units.  A
 * TimeUnit does not maintain time information, but only
 * helps organize and use time representations that may be maintained
 * separately across various contexts.  A nanosecond is defined as one
 * thousandth of a microsecond, a microsecond as one thousandth of a
 * millisecond, a millisecond as one thousandth of a second, a minute
 * as sixty seconds, an hour as sixty minutes, and a day as twenty four
 * hours.
 *
 * A TimeUnit is mainly used to inform time-based methods
 * how a given timing parameter should be interpreted.
 *
 * Note however, that there is no guarantee that a particular timeout
 * implementation will be able to notice the passage of time at the
 * same granularity as the given TimeUnit.
 */
class TimeUnit extends Enum
{
    const NANOSECONDS = 0;
    const MICROSECONDS = 1;
    const MILLISECONDS = 2;
    const SECONDS = 3;
    const MINUTES = 4;
    const HOURS = 5;
    const DAYS = 6;

    // Handy numbers for conversion methods
    private $c0 = 1;
    private $c1 = 1000;
    private $c2 = 1000000;
    private $c3 = 1000000000;
    private $c4 = 60000000000;
    private $c5 = 3600000000000;
    private $c6 = 86400000000000;

    /**
     * Convert to nanos
     *
     * @param int $d
     * @return int
     */
    public function toNanos($d)
    {
        $v = $this->getValue();
        return $this->x($d, $this->{'c'.$v}/$this->c0, PHP_INT_MAX/($this->{'c'.$v}/$this->c0));
    }

    /**
     * Convert to micros
     *
     * @param int $d
     * @return float|int
     */
    public function toMicros($d)  
    {
        $v = $this->getValue();
        if ($v < self::MICROSECONDS) {
            return $d/($this->c1/$this->c0);
        } else {
            return $this->x($d, $this->{'c'.$v}/$this->c1, PHP_INT_MAX/($this->{'c'.$v})/$this->c1);
        }
    }

    /**
     * Convert to millies
     *
     * @param int $d
     * @return float|int
     */
    public function toMillis($d)
    {
        $v = $this->getValue();
        if ($v < self::MILLISECONDS) {
            return $d/($this->c2/$this->{'c'.$v});
        } else {
            return $this->x($d, $this->{'c'.$v}/$this->c2, PHP_INT_MAX/($this->{'c'.$v}/$this->c2));
        }
    }

    /**
     * Convert to seconds
     *
     * @param int $d
     * @return float|int
     */
    public function toSeconds($d)
    {
        $v = $this->getValue();
        if ($v < self::SECONDS) {
            return $d/($this->c3/$this->{'c'.$v});
        } else {
            return $this->x($d, $this->{'c'.$v}/$this->c3, PHP_INT_MAX/($this->{'c'.$v}/$this->c3));
        }
    }

    /**
     * Convert to minutes
     *
     * @param int $d
     * @return float|int
     */
    public function toMinutes($d)
    {
        $v = $this->getValue();
        if ($v < self::MINUTES) {
            return $d/($this->c4/$this->{'c'.$v});
        } else {
            return $this->x($d, $this->{'c'.$v}/$this->c4, PHP_INT_MAX/($this->{'c'.$v}/$this->c4));
        }
    }

    /**
     * Convert to hours
     *
     * @param int $d
     * @return float|int
     */
    public function toHours($d)
    {
        $v = $this->getValue();
        if ($v < self::HOURS) {
            return $d/($this->c5/$this->{'c'.$v});
        } else {
            return $this->x($d, $this->{'c'.$v}/$this->c5, PHP_INT_MAX/($this->{'c'.$v}/$this->c5));
        }
    }

    /**
     * Convert to days
     *
     * @param int $d
     * @return float
     */
    public function toDays($d)
    {
        $v = $this->getValue();
        return $d/($this->c6/$this->{'c'.$v});
    }

    /**
     * Convert the given time duration in the given unit to this
     * unit.  Conversions from finer to coarser granularities
     * truncate, so lose precision. For example converting
     * 999 milliseconds to seconds results in
     * 0. Conversions from coarser to finer granularities
     * with arguments that would numerically overflow saturate to
     * "-PHP_INT_MAX -1" if negative or "PHP_INT_MAX"
     * if positive.
     *
     * For example, to convert 10 minutes to milliseconds, use:
     * TimeUnit::MILLISECONDS()->convert(10, TimeUnit::MINUTES);
     *
     * @param int $sourceDuration the time duration in the given source unit
     * @param TimeUnit $sourceUnit the unit of the source duration argument
     * @return float|int
     */
    public function convert($sourceDuration, TimeUnit $sourceUnit)
    {
        switch ($this->getValue()) {
            case self::NANOSECONDS:
                return $sourceUnit->toNanos($sourceDuration);
            case self::MICROSECONDS:
                return $sourceUnit->toMicros($sourceDuration);
            case self::MILLISECONDS:
                return $sourceUnit->toMillis($sourceDuration);
            case self::SECONDS:
                return $sourceUnit->toSeconds($sourceDuration);
            case self::MINUTES;
                return $sourceUnit->toMinutes($sourceDuration);
            case self::HOURS;
                return $sourceUnit->toHours($sourceDuration);
            case self::DAYS:
                return $sourceUnit->toDays($sourceDuration);
        }
    }

    /**
     * Scale d by m, checking for overflow.
     * This has a short name to make code more readable.
     *
     * @param int $d
     * @param int $m
     * @param int $over
     * @return int
     */
    private function x($d, $m, $over)
    {
        if ($d > $over) {
            return PHP_INT_MAX;
        }
        if ($d < -$over) {
            return -PHP_INT_MAX -1;
        }
        return $d * $m;
    }
}
