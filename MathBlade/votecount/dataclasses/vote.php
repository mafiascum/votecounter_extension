<?php

namespace MathBlade\votecount\dataclasses;

class Vote {

    protected $postNumber;
    protected $originalInput;
    protected $isUnvote;
    protected $playerVotingUserName;
    protected $playerVoting;
    protected $playerBeingVoted;
    protected $timestamp;

    private const UNVOTESTRING = "Unvote";

    public function __construct($postNum, $playerVotingUserName,$voteString, $unvote, $timestamp)
    {

        $this->postNumber = $postNum;
        $this->originalInput = $voteString;
        $this->isUnvote = $unvote;
        $this->playerVotingUserName = $playerVotingUserName;
        $this->timestamp = $timestamp;

    }

    public static function build_unvote_from_vig_or_resurrection($postNum,$playerVotingUserName)
    {
        return new Vote($postNum,$playerVotingUserName,Vote::UNVOTESTRING,true,time());
    }

    public function getDisplayString()
    {

      return 'POST NUMBER: ' . $this->getPostNumber() . ' ORIGINAL INPUT: ' . $this->getOriginalInput() . ' UNVOTE: ' . $this->IsUnvote() ;
    }

    public function getPlayerVotingUserName()
    {
      return $this->playerVotingUserName;
    }


    public function getPostNumber()
    {

      return $this->postNumber;

    }
    public function getPlayerVoting()
    {
        return $this->playerVoting;
    }
    public function setPlayerVoting(&$player)
    {
        $this->playerVoting = $player;
    }

    public function getPlayerBeingVoted()
    {
        return $this->playerBeingVoted;
    }
    public function setPlayerBeingVoted(&$player)
    {
        $this->playerBeingVoted = $player;
    }

    public function getOriginalInput()
    {
      return $this->originalInput;
    }

    public function IsUnvote()
    {
      return $this->isUnvote;
    }

    public function getTimestamp()
    {
      return $this->timestamp;
    }

    private static function sortByPostNumber($aVote, $bVote)
    {
        return ($aVote->getPostNumber() - $bVote->getPostNumber());
    }

    public static function sortVotesByPostNumber(&$votes)
    {
      usort($votes, ['MathBlade\votecount\dataclasses\Vote', 'sortByPostNumber']);
    }






}
