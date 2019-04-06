<?php

namespace MathBlade\votecount\dataclasses;

class dayvig extends playerPostNumberList {

    protected $player;
    protected $postNumber;

    public function __construct($player, $postNumber)
    {
        $this->player = $player;
        $this->postNumber = $postNumber;
    }


    public function getPlayerToActOn()
    {
        return $this->player;
    }

    public function getPostNumberToActOn()
    {
        return $this->postNumber;
    }


}
