<?php

namespace mafiascum\votecounter_extension\dataclasses;
use mafiascum\votecounter_extension\dataclasses\player as Player;

class Wagon {

    protected $threshold;
    protected $playerBeingVoted;
    protected $playersVoting = array();
    protected $postNumbers = array();
    protected $postBBCodeList = array();
    protected $isHammered;
    protected $votes = array();
    protected $unvoteList = array();
    protected $maxTimestamp;
    protected $l_level;

    public static $NO_LYNCH_STRING = "No Lynch";

    public static function NO_LYNCH_PLAYER()
    {
        return new player(Wagon::$NO_LYNCH_STRING,null,null,null,false,false,false);
    }

    public function getPostNumbers()
    {
      return $this->postNumbers;
    }

    public function getHammerDisplayString($postNumber,$isLyloOrMylo)
    {

        if ($isLyloOrMylo)
        {

            return '';
        }
        $player = $this->getPlayerBeingVoted();


        if ($player->IsLoved($postNumber,$isLyloOrMylo))
        {

            return ' - LOVED2';
        }
        if ($player->IsHated($postNumber,$isLyloOrMylo))
        {
            return ' - HATED';
        }

        return '';
    }



    private static function compareWagons(&$wagonA, &$wagonB)
    {
        if($wagonA == $wagonB)
        {
            return 0;
        }
        else
        {
            if ($wagonA->getIsHammered() && !$wagonB->getIsHammered())
            {
               return -1;
            }
            if (!$wagonA->getIsHammered() && $wagonB->getIsHammered())
            {
               return 1;
            }

            $wagonALLevel = $wagonA->getL_Level();
            $wagonBLLevel = $wagonB->getL_Level();
            if ($wagonALLevel - $wagonBLLevel != 0)
            {
                return ($wagonALLevel - $wagonBLLevel);
            }
            else {
              $wagonAPostNumber = max($wagonA->getPostNumbers());
              $wagonBPostNumber = max($wagonB->getPostNumbers());
              return ($wagonAPostNumber - $wagonBPostNumber);
            }

        }
    }

    public static function sortWagons(&$wagons)
    {

        usort($wagons,['mafiascum\votecounter_extension\dataclasses\Wagon', 'compareWagons']);

        return $wagons;
    }

    public function __construct($threshold)
    {
      $this->threshold = $threshold;
    }

    public function getPlayersVoting()
    {
      return $this->playersVoting;
    }

    public function addVote($vote,$isLyloOrMylo)
    {


        if ($this->playerBeingVoted == null)
        {
          $this->playerBeingVoted = $vote->getPlayerBeingVoted();
        }

        $playerVoting = $vote->getPlayerVoting();

        if (!in_array($playerVoting, $this->playersVoting))
        {
            array_push($this->playersVoting,$playerVoting);
        }

        $playerVoting->setPlayerCurrentlyVoting($this->playerBeingVoted);
        $postNumber = $vote->getPostNumber();

        if (!in_array($postNumber,$this->postNumbers))
        {
            $playerVoting->setPostNumberOfVote($postNumber);

            array_push($this->postNumbers,$postNumber);
            array_push($this->postBBCodeList,'[post]' . $postNumber . '[/post]');
            array_push($this->unvoteList,false);
            $voteTimestamp = $vote->getTimestamp();
            if ($voteTimestamp > $this->maxTimestamp)
            {
                $this->maxTimestamp = $voteTimestamp;
            }
            array_push($this->votes,$vote);


        }
        $this->updateL_Level();

        if ($this->l_level == 1 && $this->playerBeingVoted->isHated($postNumber,$isLyloOrMylo))
        {
          $this->isHammered = true;
        }
        else if ($this->l_level == 0 && !$this->playerBeingVoted->isLoved($postNumber,$isLyloOrMylo))
        {
          $this->isHammered = true;
        }
        else if ($this->l_level == -1 && $this->playerBeingVoted->isLoved($postNumber,$isLyloOrMylo))
        {
          $this->isHammered = true;
        }
        else {

          $this->isHammered = false;
        }


    }

    public function updateL_Level()
    {
        $this->l_level = $this->threshold - count($this->playersVoting);
    }

    public function getL_Level()
    {
      return $this->l_level;
    }
    public function removeVote($vote)
    {
        $playersNowVoting = array();
        $votePlayerVoting = $vote->getPlayerVoting();

        foreach($this->playersVoting as $player)
        {

          if (strcmp($votePlayerVoting->getExactName(), $player->getExactName()) == 0)
          {

              if (!in_array($vote->getPostNumber(), $this->postNumbers))
              {
                array_push($this->postNumbers,$postNumber);
                array_push($this->postBBCodeList,'[post]' . $postNumber . '[/post]');
                array_push($this->unvoteList,true);
                $voteTimestamp = $vote->getTimestamp();
                if ($voteTimestamp > $this->maxTimestamp)
                {
                    $this->maxTimestamp = $voteTimestamp;
                }
                array_push($this->votes,$vote);

              }
          }
          else {
            array_push($playersNowVoting, $player);
          }


        }

        $this->playersVoting = $playersNowVoting;
        $this->updateL_Level();

    }

    public function getPlayerBeingVoted()
    {
      return $this->playerBeingVoted;
    }

    public function getIsHammered()
    {
      return $this->isHammered;
    }



}
