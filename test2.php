    <?php

    class Sequence extends Stackable
    {
        protected $value = -1;

        public function run()
        {}

        public function get()
        {
            return $this->value;
        }

        public function set($value)
        {
            $this->value = $value;
        }
    }

    class SequenceGroup extends Sequence implements Countable
    {
        /**
         * @var Sequence[]
         */
        protected $sequences;

        public function run()
        {
        }

        public function get()
        {
            $minimum = PHP_INT_MAX;

            foreach ($this->sequences as $sequence) {
                $value = $sequence->get();
                $minimum = min($minimum, $value);
            }

            return $minimum;
        }

        public function set($value)
        {
            foreach ($this->sequences as $sequence) {
                $sequence->set($value);
            }
        }

        public function add(Sequence $sequence)
        {
            $oldSequences = $this->sequences;
            $newSequences = $oldSequences;
            $newSequences[] = $sequence;
            $this->sequences = $newSequences;
        }

        public function count()
        {
            return count($this->sequences);
        }

        public function getSequences()
        {
            return $this->sequences;
        }
    }

    $sequenceGroup = new SequenceGroup();

    $sequence1 = new Sequence();
    $sequence2 = new Sequence();

    $sequenceGroup->add($sequence1);
    $sequenceGroup->add($sequence2);

    var_dump($sequence1, $sequence2);
    var_dump($sequenceGroup->getSequences());


    object(Sequence)#2 (0) {
    }
    object(Sequence)#3 (0) {
    }
    array(2) {
      [0]=>
      object(Sequence)#4 (0) {
      }
      [1]=>
      object(Sequence)#5 (0) {
      }
    }