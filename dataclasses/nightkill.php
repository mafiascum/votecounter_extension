<?php

namespace mafiascum\votecounter_extension\dataclasses;

class nightkill {


        protected $playerToDie;
        protected $nightOfDeath;
        protected $originalInput;

        public function __construct($playerToDie,$nightOfDeath,$originalInput)
        {
            $this->playerToDie = $playerToDie;
            $this->nightOfDeath = $nightOfDeath;
            $this->originalInput = $originalInput;
        }

        public function getPlayerToDie()
        {
            return $this->playerToDie;
        }

        public function getNightOfDeath()
        {
            return $this->nightOfDeath;
        }

        public function getOriginalInput()
        {
            return $this->originalInput;
        }


}
