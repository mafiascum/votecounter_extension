<?php

namespace mafiascum\votecounter_extension\dataclasses;

class resurrection extends playerPostNumberList {


        protected $playerToRevive;
        protected $postNumberOfResurrection;

        public function __construct($playerToRevive,$postNumberOfResurrection)
        {
            $this->playerToRevive = $playerToRevive;
            $this->postNumberOfResurrection = $postNumberOfResurrection;
        }

        public function getPlayerToActOn()
        {
            return $this->playerToRevive;
        }

        public function getPostNumberToActOn()
        {
            return $this->postNumberOfResurrection;
        }


}
