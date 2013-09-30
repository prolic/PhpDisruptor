<?php

namespace PhpDisruptor\Collections;

use PhpDisruptor\Exception;
use Stackable;

/**
 * Histogram for tracking the frequency of observations of values below interval upper bounds
 *
 * This class is useful for recording timings across a large number of observations
 * when high performance is required.
 *
 * The interval bounds are used to define the ranges of the histogram buckets. If provided bounds
 * are [10,20,30,40,50] then there will be five buckets, accessible by index 0-4. Any value
 * 0-10 will fall into the first interval bar, values 11-20 will fall into the
 * second bar, and so on.
 */
final class Histogram extends Stackable
{
    /**
     * @var array
     */
    public $upperBounds;

    /**
     * @var array
     */
    public $counts;

    /**
     * @var int
     */
    public $minValue;

    /**
     * @var int
     */
    public $maxValue;

    /**
     * Constructor
     *
     * @param int[] $upperBounds
     */
    public function __construct(array $upperBounds)
    {
        $this->counts = array();
        $this->minValue = PHP_INT_MAX;
        $this->maxValue = 0;
        $this->_validateUpperBounds($upperBounds);
        $this->upperBounds = $upperBounds;
    }

    public function run()
    {
    }

    /**
     * Validates the input bounds; used by constructor only
     *
     * @param array $upperBounds
     * @throws Exception\InvalidArgumentException
     */
    public function _validateUpperBounds(array $upperBounds) // public for pthreads reasons
    {
        $lastBound = -1;
        if (count($upperBounds) <= 0) {
            throw new Exception\InvalidArgumentException(
                'Must provide at least one interval'
            );
        }
        foreach ($upperBounds as $bound) {
            if ($bound <= 0) {
                throw new Exception\InvalidArgumentException(
                    'Bounds must be positive values'
                );
            }
            if ($bound <= $lastBound) {
                throw new Exception\InvalidArgumentException(
                    'Bound ' . $bound . ' is not great then last bound ' . $lastBound
                );
            }
            $lastBound = $bound;
        }
    }

    /**
     * Size of the list of interval bars (ie: count of interval bars)
     *
     * @return int
     */
    public function getSize()
    {
        return count($this->upperBounds);
    }

    /**
     * Get the upper bound of an interval for an index
     *
     * @param int $index of the upper bound
     * @return int the interval upper bound for the index
     */
    public function getUpperBoundAt($index)
    {
        return $this->upperBounds[$index];
    }

    /**
     * Get the count of observations at a given index.
     *
     * @param int $index of the observations counter.
     * @return int the count of observations at a given index.
     */
    public function getCountAt($index)
    {
        return $this->counts[$index];
    }

    /**
     * Add an observation to the histogram and increment the counter for the interval it matches
     *
     * @param int $value for the observation to be added
     * @return bool true if in the range of intervals and successfully added observation; otherwise false
     */
    public function addObservation($value)
    {
        $low = 0;
        $high = count($this->upperBounds) - 1;

        while ($low < $high) {
            $mid = $low + (($high - $low) >> 1);
            if ($this->upperBounds[$mid] < $value) {
                $low = $mid + 1;
            } else {
                $high = $mid;
            }
        }

        // if the binary search found an eligible bucket, increment
        if ($value <= $this->upperBounds[$high]) {
            $this->counts[$high]++;
            $this->_trackRange($value);

            return true;
        }


        // otherwise value was not found
        return false;
    }

    /**
     * Track minimum and maximum observations
     *
     * @param int $value
     * @return void
     */
    public function _trackRange($value) // public for pthreads reasons
    {
        if ($value < $this->minValue) {
            $this->minValue = $value;
        }

        if ($value > $this->maxValue) {
            $this->maxValue = $value;
        }
    }

    /**
     * Add observations from another Histogram into this one
     *
     * Histograms must have the same intervals
     *
     * @param Histogram $histogram from which to add the observation counts
     * @return void
     * @throws Exception\InvalidArgumentException if interval count or values do not match exactly
     */
    public function addObservations(self $histogram)
    {
        $size = count($this->upperBounds);
        if ($size != count($histogram->upperBounds)) {
            throw new Exception\InvalidArgumentException(
                'Histograms must have matching intervals'
            );
        }

        for ($i = 0; $i < $size; $i++) {
            if ($this->upperBounds[$i] != $histogram->upperBounds[$i]) {
                throw new Exception\InvalidArgumentException(
                    'Histograms must have matching intervals'
                );
            }
        }

        // increment all of the internal counts
        $size = count($this->counts);
        for ($i = 0; $i < $size; $i++) {
            $this->counts[$i] += $histogram->counts[$i];
        }

        // refresh the minimum and maximum observation ranges
        $this->_trackRange($histogram->minValue);
        $this->_trackRange($histogram->maxValue);
    }

    /**
     * Clear the list of interval counters
     *
     * @return void
     */
    public function clear()
    {
        $this->maxValue = 0;
        $this->minValue = PHP_INT_MAX;

        $this->counts = array_fill(0, count($this->counts), 0);
    }

    /**
     * Count total number of recorded observations
     *
     * @return int
     */
    public function getCount()
    {
        $count = 0;

        foreach ($this->counts as $count) {
            $count += $count;
        }

        return $count;
    }

    /**
     * Get the minimum observed value
     *
     * @return int
     */
    public function getMin()
    {
        return $this->minValue;
    }

    /**
     * Get the maximum observed value
     *
     * @return int
     */
    public function getMax()
    {
        return $this->maxValue;
    }

    /**
     * Calculate the mean of all recorded observations
     *
     * The mean is calculated by summing the mid points of each interval multiplied by the count
     * for that interval, then dividing by the total count of observations.  The max and min are
     * considered for adjusting the top and bottom bin when calculating the mid point, this
     * minimises skew if the observed values are very far away from the possible histogram values.
     *
     * @return float
     */
    public function getMean()
    {
        // early exit to avoid division by zero later
        if (0 == $this->getCount()) {
            return 0;
        }

        // precalculate the initial lower bound; needed in the loop
        $lowerBound = $this->counts[0] > 0 ? $this->minValue : 0;
        $total = 0;

        // midpoint is calculated as the average between the lower and upper bound
        // (after taking into account the min & max values seen)
        // then, simply multiply midpoint by the count of values at the interval (intervalTotal)
        // and add to running total (total)
        foreach ($this->upperBounds as $key => $bound) {
            if (0 != $this->counts[$key]) {
                $upperBound = min($bound, $this->maxValue);
                $midPoint = $lowerBound + (($upperBound - $lowerBound) / 2);

                $intervalTotal = $midPoint * $this->counts[$key];
                $total += $intervalTotal;
            }

            // and recalculate the lower bound for the next time around the loop
            $lowerBound = max($bound +1, $this->minValue);
        }

        return round($total / $this->getCount(), 2, PHP_ROUND_HALF_UP);
    }

    /**
     * Calculate the upper bound within which 99% of observations fall
     *
     * @return int the upper bound for 99% of observations
     */
    public function getTwoNinesUpperBound()
    {
        return $this->getUpperBoundForFactor(0.99);
    }

    /**
     * Calculate the upper bound within which 99.99% of observations fall
     *
     * @return int the upper bound for 99.99% of observations
     */
    public function getFourNinesUpperBound()
    {
        return $this->getUpperBoundForFactor(0.9999);
    }

    /**
     * Get the interval upper bound for a given factor of the observation population
     *
     * Note this does not get the actual percentile measurement, it only gets the bucket
     *
     * @param double $factor  representing the size of the population
     * @return int the interval upper bound
     * @throws Exception\InvalidArgumentException if factor < 0.0 or factor > 1.0
     */
    public function getUpperBoundForFactor($factor)
    {
        if (0 >= $factor || $factor >= 1) {
            throw new Exception\InvalidArgumentException(
                'factor must be >= 0.0 and <= 1.0'
            );
        }

        $totalCount = $this->getCount();
        $tailTotal = $totalCount - round($totalCount * $factor);
        $tailCount = 0;

        // reverse search the intervals ('tailCount' from end)
        for ($i = count($this->counts) - 1; $i >= 0; $i--) {
            if (0 != $this->counts[$i]) {
                $tailCount += $this->counts[$i];
                if ($tailCount >= $tailTotal) {
                    return $this->upperBounds[$i];
                }
            }
        }

        return 0;
    }

    /**
     * Returns a string representation of the histogram
     *
     * @return string
     */
    public function __toString()
    {
        $string = "Histogram{min={$this->getMin()}, max={$this->getMax()}, mean={$this->getMean()}, "
            . "99%={$this->getTwoNinesUpperBound()}, 99,99%={$this->getFourNinesUpperBound()}, "
            . "[";
        foreach ($this->counts as $key => $count) {
            $string .= $this->upperBounds[$key] . '=' . $count[$key] .', ';
        }

        if (count($this->counts) > 0) {
            $string = substr($string, 0, -2);
        }

        $string .= ']}';

        return $string;
    }
}
