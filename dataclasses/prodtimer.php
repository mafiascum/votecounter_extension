<?php

namespace mafiascum\votecounter_extension\dataclasses;

class prodtimer {


        protected $days;
        protected $hours;
        protected $minutes;
        protected $seconds;

        public function __construct($days,$hours,$minutes,$seconds)
        {
            $this->days = $days;
            $this->hours = $hours;
            $this->minutes = $minutes;
            $this->seconds = $seconds;
        }

        public function getDays()
        {
            return $this->days;
        }

        public function getHours()
        {
            return $this->hours;
        }
        public function getMinutes()
        {
            return $this->minutes;
        }

        public function getSeconds()
        {
            return $this->seconds;
        }


}
