<?php

namespace mafiascum\votecounter_extension\dataclasses;

require_once(__DIR__ . '/../dataclasses/post.php');
require_once(__DIR__ . '/../dataclasses/player.php');
require_once(__DIR__ . '/../dataclasses/vote.php');
require_once(__DIR__ . '/../dataclasses/day.php');
require_once(__DIR__ . '/../dataclasses/votecount.php');
require_once(__DIR__ . '/../dataclasses/wagon.php');
require_once(__DIR__ . '/../dataclasses/lyloOrMylo.php');
require_once(__DIR__ . '/../helper/static_functions.php');

use mafiascum\votecounter_extension\dataclasses\Wagon as Wagon;
use mafiascum\votecounter_extension\dataclasses\Post as Post;
use mafiascum\votecounter_extension\dataclasses\player as player;
use mafiascum\votecounter_extension\dataclasses\Vote as Vote;
use mafiascum\votecounter_extension\dataclasses\Day as Day;
use mafiascum\votecounter_extension\dataclasses\lyloOrMylo as lyloOrMylo;
use mafiascum\votecounter_extension\helper\static_functions as static_functions;

class VoteCount {


    protected $dayNumber;
    protected $players = array();
    protected $majorityCount;
    protected $hammered;
    protected $errors = array();
    protected $livingPlayersAtStart;
    protected $replacements = array();
    protected $wagons = array();
    protected $playersValidForVotecount = array();
    protected $isLyloOrMylo;
    protected $moderatorList;

    public function __construct($dayNumber,&$players,&$replacements,$moderatorList,$firstPostOfDay,$isLyloOrMylo)
    {

        $this->dayNumber = $dayNumber;
        $this->players = $players;
        $this->replacements = $replacements;
        $this->moderatorList = $moderatorList;
        $this->hammered = false;
        $this->determineFirstLivingPlayers();
        $this->playersValidForVotecount = $this->livingPlayersAtStart;
        $this->updateMajorityCount($firstPostOfDay,$isLyloOrMylo);



    }

    public function getIsLyloOrMylo()
    {
        return $this->isLyloOrMylo;
    }

    public function setIsLyloOrMylo($value)
    {
        $this->isLyloOrMylo = $value;
    }

    public function getPlayersValidForVotecount()
    {
        return $this->playersValidForVotecount;
    }

    public function addPlayerValidForVotecount($player,$postNumber, $isLyloOrMylo)
    {


        if (!in_array($player,$this->playersValidForVotecount))
        {
            array_push($this->playersValidForVotecount,$player);
            $this->determineLivingPlayers();
            $this->updateMajorityCount($postNumber,$isLyloOrMylo);
        }


    }

    public function removePlayerValidForVotecount($player,$postNumber,$isLyloOrMylo)
    {
      if (($key = array_search($player, $this->playersValidForVotecount)) !== false) {
          unset($this->playersValidForVotecount[$key]);
          $this->determineLivingPlayers();
          $this->updateMajorityCount($postNumber,$isLyloOrMylo);
      }
    }

    public function getDayNumber()
    {
      return $this->dayNumber;
    }

    public function IsHammered()
    {
      return $this->hammered;
    }

    public function getWagons()
    {
      return $this->wagons;
    }
    public function setSortedWagons($wagons)
    {
      $this->wagons = $wagons;
    }

    public function addWagon($wagon)
    {
      array_push($this->wagons,$wagon);
    }
    public function removeWagon($wagon)
    {
      if (($key = array_search($wagon, $this->wagons)) !== false) {
          unset($array[$key]);
      }
    }
    public function getErrors()
    {
      return $this->errors;
    }

    public function addError($error)
    {

      if ($error != null)
      {

        array_push($this->errors,$error);
      }
    }


    public function doVote($vote,$isLyloOrMylo)
    {

        $this->setIsLyloOrMylo($isLyloOrMylo);

        if($vote == null)
        {

          return;
        }

        try {

          $modList = $this->moderatorList;
          $playerVotingUserName = $vote->getPlayerVotingUserName();
          if ($modList != null)
          {
              if (is_array($modList))
              {
                  if (in_array($playerVotingUserName, $modList))
                  {
                      return;
                  }
              }
          }

          //This one is exact because it comes from database eventually. There should be no typos here.
          $playerVoting = static_functions::get_player_exact_reference($this->players,$playerVotingUserName);

          $playerTarget = null;
          $playerVotingIsTreestump = $playerVoting != null ?  $playerVoting->isTreestump($vote->getPostNumber(),$isLyloOrMylo) : true;

          if ($playerVoting == null)
          {
            //array_push($this->errors, "Post number: " . $vote->getPostNumber() . " is missing the voter. Double check settings.");
            $this->addError("Post number " . $vote->getPostNumber() . " is missing the voter. Double check settings.");

            return;
          }
          //Treestumps can't vote.
          else if (boolval($playerVotingIsTreestump) === boolval(TRUE))
          {

            $this->addError("Post number " . $vote->getPostNumber() . " has a treestump " . $playerVoting->getName() . " voting. If intentional, ignore this message.");
            return;
          }
          else {
            $vote->setPlayerVoting($playerVoting);
          }
          if(!$vote->IsUnvote())
          {

              $playerVoted = static_functions::get_player_reference_from_vote($this->players,$this->replacements,$vote->getOriginalInput(), $vote->getPostNumber());


              if ($playerVoted == null)
              {

                $this->addError("Post number " . $vote->getPostNumber() . " could not retrieve the player voted. Double check settings.");

                return;
              }
              else if (!is_array($playerVoted))
              {
                $playerTarget = $playerVoted;
                $vote->setPlayerBeingVoted($playerTarget);
              }
              else {
                $playerVotedObject = $playerVoted[0];
                $playerVotedErrorMessage = $playerVoted[1];
                $this->addError("Post number " . $vote->getPostNumber() . ' ' . $playerVotedErrorMessage);
                $playerTarget = $playerVotedObject;
                $vote->setPlayerBeingVoted($playerVotedObject);
              }


          }




          $currentWagon = null;
          $newWagon = null;

          $wherePlayerVotingVoteIsCurrently = $playerVoting->getPlayerCurrentlyVoting();
          $playerIsAlreadyVotingThere = $wherePlayerVotingVoteIsCurrently != null && $playerTarget != null && $wherePlayerVotingVoteIsCurrently->getExactName() == $playerTarget->getExactName();



          if ($playerIsAlreadyVotingThere)
          {

            return;
          }
          else
          {
              $currentWagon = null;
              $newWagon = null;
              //This is an unvote.
              if ($playerTarget == null && $vote->IsUnvote())
              {
                    //If they have an active vote remove it. They unvoted.
                    $currentWagon = $this->findWagonVoteAndRemoveIt($playerVoting,$wherePlayerVotingVoteIsCurrently,$vote);


                    return;

              }
              else {



                  if ($wherePlayerVotingVoteIsCurrently != null)
                  {
                      //This is a swap of a vote. Remove the vote.
                      $currentWagon = $this->findWagonVoteAndRemoveIt($playerVoting,$wherePlayerVotingVoteIsCurrently,$vote);
                  }

                  //Now that voting is clear add the vote to the wagon.
                  $newWagon = $this->getWagonByTarget($playerTarget);

                  if ($newWagon == null)
                  {

                    //A wagon on said player hasn't begun yet.
                      if ($playerTarget != null && $playerTarget->isAlive() && !$playerTarget->isTreestump($vote->getPostNumber(),$isLyloOrMylo))
                      {

                          $createdWagon = new Wagon($this->majorityCount);

                          $createdWagon->addVote($vote,$isLyloOrMylo);
                          $playerVoting->setPlayerCurrentlyVoting($playerTarget);
                          $playerVoting->setPostNumberOfVote($vote->getPostNumber());
                          $this->addWagon($createdWagon);
                          $newWagon = $createdWagon;
                      }
                      else if ($playerTarget == null)
                      {
                         return "There was an error processing this vote. Could not find player associated with post number " . $vote->getPostNumber() . ". Check settings or there is an error.";
                      }
                      else if (!$playerTarget->isAlive())
                      {
                        return "There was an error processing this vote. Player " . $playerVoting->getExactName() . " attempted to vote " . $playerTarget->getExactName() . " on ". $vote->getPostNumber() . " but that player is dead. Check settings or there is an error.";
                      }
                      else if ($playerTarget->isTreestump($vote->getPostNumber(),$isLyloOrMylo))
                      {
                        return "There was an error processing this vote. Player " . $playerVoting->getExactName() . " attempted to vote " . $playerTarget->getExactName() . " on ". $vote->getPostNumber() . " but that player is a treestump. Check settings or there is an error.";
                      }
                  }
                  else {

                    //A wagon on player already exists.
                    $newWagon->addVote($vote,$isLyloOrMylo);
                    $playerVoting->setPlayerCurrentlyVoting($playerTarget);
                    $playerVoting->setPostNumberOfVote($vote->getPostNumber());
                  }

                  $this->hammered = $newWagon->getIsHammered();

                  if ($this->hammered && !($playerTarget == Wagon::NO_LYNCH_PLAYER()))
                  {

                      $playerTarget->kill();
                  }

              }


          }




        } catch (\Exception $e) {
            //array_push($this->errors, $vote->getPostNumber() . " had an error/could not be processed. Double check votecount.");
            $this->addError($vote->getPostNumber() . " had an error/could not be processed. Double check votecount.");
        }
    }

    private function findWagonVoteAndRemoveIt($playerVoting,$targetPlayer,$vote)
    {

      $currentWagon = $this->getWagonByTarget($targetPlayer);
      if ($currentWagon != null)
      {
          $currentWagon->removeVote($vote);
      }
      $playerVoting->setPlayerCurrentlyVoting(null);
      $playerVoting->setPostNumberOfVote($vote->getPostNumber());

      return $currentWagon;
    }

    private function getWagonByTarget($targetPlayer)
    {
        if ($targetPlayer == null)
        {
          return null;
        }
        else {
          foreach($this->wagons as $wagon)
          {
            if (strcmp($wagon->getPlayerBeingVoted()->getExactName(), $targetPlayer->getExactName()) == 0)
            {
              return $wagon;
            }
          }

          return null;
        }

    }

    public function updateMajorityCount($postNumber,$isLyloOrMylo)
    {
      $this->majorityCount = player::getMajorityCount($this->playersValidForVotecount, $postNumber,$isLyloOrMylo);
    }

    public function getMajorityCount()
    {
      return $this->majorityCount;
    }

    //This is based on the first constructor;
    private function determineFirstLivingPlayers()
    {
      $this->livingPlayersAtStart = player::getPlayersAlive($this->players);
    }

    private function determineLivingPlayers()
    {
      $this->livingPlayersAtStart = player::getPlayersAlive($this->playersValidForVotecount);
    }

    public function getLivingPlayersAtStart()
    {
      return $this->livingPlayersAtStart;
    }

    public function getPlayersNotVoting()
    {
      $playersNotVoting = array();
      foreach($this->playersValidForVotecount as $player)
      {
          if ($player->getPlayerCurrentlyVoting() == null)
          {
              array_push($playersNotVoting,$player);
          }

      }

      return $playersNotVoting;
    }

    private static function process_post_specific_list(&$votecount,&$list,&$players,$actIfAlive,$postNumberForComparison,$isLyloOrMylo)
    {

      if (count($list) > 0)
      {
        $listCompleted = array();
        foreach($list as $entry)
        {
            $postNumberToActOn = $entry->getPostNumberToActOn();

            if ($postNumberToActOn <= $postNumberForComparison)
            {

                $playerToActOn = static_functions::get_player_reference($players,$entry->getPlayerToActOn()->getName());

                if (($actIfAlive && $playerToActOn->isAlive()) || (!$actIfAlive && !$playerToActOn->isAlive()))
                {
                  Vote::build_unvote_from_vig_or_resurrection($postNumberToActOn,$playerToActOn->getName());
                  $votecount->doVote($unvote,$isLyloOrMylo);

                  if ($actIfAlive)
                  {
                    $playerToActOn->kill();
                    //echo "Killing player: " . $playerToActOn->getName() . " on post number: " . $postNumberToActOn . "<br/>";
                    $votecount->removePlayerValidForVotecount($playerToActOn, $postNumberToActOn,$isLyloOrMylo);
                  }
                  else {
                    //echo "Reviving player: " . $playerToActOn->getName() . " on post number: " . $postNumberToActOn . "<br/>";
                    $playerToActOn->revive();
                    $votecount->addPlayerValidForVotecount($playerToActOn, $postNumberToActOn,$isLyloOrMylo);
                  }

                  array_push($listCompleted,$entry);
                }
            }
        }

        //Remove repetitive processing.
        if (count($listCompleted) > 0)
        {
            //$dayviggedList = array_diff($dayviggedList,$dayvigsCompleted);
            foreach($listCompleted as $entry)
            {
              if (($key = array_search($entry, $list)) !== false) {
                  unset($list[$key]);
              }
            }
        }
      }


    }

    private static function get_kills_to_sort(&$killsToSort,$playerName, $list, $postNumberForComparison, $actIfAlive)
    {

        if ($list != null && count($list) > 0)
        {
          $maximumEntry = null;
          $maximumEntryPost = -99;
          foreach($list as $entry)
          {

            if (strcmp($entry->getPlayerToActOn()->getName(),$playerName) == 0)
            {
              if (($entry->getPostNumberToActOn() > $maximumEntryPost) && ($entry->getPostNumberToActOn() <= $postNumberForComparison))
              {

                  $maximumEntry = $entry;
                  $maximumEntryPost = $entry->getPostNumberToActOn();
              }
            }

          }
          if ($maximumEntry != null)
          {
            array_push($killsToSort,array(array($entry),$maximumEntryPost,$actIfAlive));
          }


        }
    }

    public static function handle_dayvigs_resurrections_and_modkills(&$votecount,&$dayviggedList,&$resurrectedList,&$modkilledList,&$players,$postNumberForComparison,$isLyloOrMylo)
    {


      $maximumDayvigPost = -99;
      $maximumResurrectionPost = -99;
      $maximumModkillPost = -99;
      $hasDayvig = ($dayviggedList != null && count($dayviggedList) > 0);
      $hasResurrection = ($resurrectedList != null && count($resurrectedList) > 0);
      $hasModkill = ($modkilledList != null && count($modkilledList) > 0);

      $killsToSort = array();
      foreach($players as $player)
      {
        $playerName = $player->getExactName();

        VoteCount::get_kills_to_sort($killsToSort,$playerName,$dayviggedList,$postNumberForComparison,true);
        VoteCount::get_kills_to_sort($killsToSort,$playerName,$modkilledList,$postNumberForComparison,true);
        VoteCount::get_kills_to_sort($killsToSort,$playerName,$resurrectedList,$postNumberForComparison,false);




        if (count($killsToSort) > 0)
        {
          usort($killsToSort, ['VoteCount', 'killSorter']);

          foreach($killsToSort as $kill)
          {

              Votecount::process_post_specific_list($votecount,$kill[0], $players, $kill[2], $kill[1], $isLyloOrMylo );

          }
        }
      }

    }

    private static function killSorter($a, $b)
    {
        if ($a[1] == $b[1]) {
            return 0;
        }
        return ($a[1] < $b[1]) ? -1 : 1;
    }

    public static function build_all_vote_counts($lastPostNumber,$votes,&$players,&$replacements,&$moderatorList,&$days,&$isLyloOrMyloArray,$deadList,$resurrectedList,$deadline,$color,$fontOverride,$prodTimer,$dayviggedList,$modkilledList)
    {



      Vote::sortVotesByPostNumber($votes);

      $errorsInDayAssign = Day::assign_votes_to_day($days,$votes,$lastPostNumber);
      if (count($errorsInDayAssign) > 0)
        return array('Error', $errorsInDayAssign);


      Day::assign_deaths_to_day($days,$deadList);
      $votecounts = array();

      $index = 0;
      foreach($days as $day)
      {
        if (($index + 1) < count($days))
        {
            $lastPostOfDay = $days[$index+1]->getFirstPostOfDay() -1;
        }
        else {
            $lastPostOfDay = $lastPostNumber;
        }
        //Reset the votes for the day.
        foreach($players as $player)
        {

          if ($player->isAlive() && !$player->isTreestump($lastPostOfDay, lyloOrMylo::IsLyloOrMylo($isLyloOrMyloArray,$lastPostOfDay)))
          {
              $player->setPlayerCurrentlyVoting(null);
              $player->setPostNumberOfVote(null);

          }

        }
        //vc = new VoteCount(days[i].Number, allPlayers, (((int)Math.Floor((double)(allPlayers.numAlive() - treestumpedPlayers - gunnerPlayers) / 2)) + 1), days, isRestCall);

          $votecount = new VoteCount($day->getDayNumber(),$players,$replacements,$moderatorList,$day->getFirstPostOfDay(),lyloOrMylo::IsLyloOrMylo($isLyloOrMyloArray,$day->getFirstPostOfDay()));
          //$votecount->updateMajorityCount($day->getFirstPostOfDay());




          foreach($day->getVotes() as $vote)
          {
            //Handle midday vigs or resurrections.
            VoteCount::handle_dayvigs_resurrections_and_modkills($votecount,$dayviggedList,$resurrectedList,$modkilledList,$players,$vote->getPostNumber(), lyloOrMylo::IsLyloOrMylo($isLyloOrMyloArray,$lastPostOfDay));



              $votecount->doVote($vote,lyloOrMylo::IsLyloOrMylo($isLyloOrMyloArray,$vote->getPostNumber()));





              //If someone is hammered don't process any further votes.
              if ($votecount->IsHammered())
              {

                  break;
              }
          }

          if ($day->getDayNumber() < count($days))
          {
            $nextDay = $days[$day->getDayNumber()];
            //Get vigs that occurred before any votes have happened that day.
            VoteCount::handle_dayvigs_resurrections_and_modkills($votecount,$dayviggedList,$resurrectedList,$modkilledList,$players,$nextDay->getFirstPostOfDay(),lyloOrMylo::IsLyloOrMylo($isLyloOrMyloArray,$nextDay->getFirstPostOfDay() -1));

          }
          else {
            VoteCount::handle_dayvigs_resurrections_and_modkills($votecount,$dayviggedList,$resurrectedList,$modkilledList,$players,$lastPostNumber,lyloOrMylo::IsLyloOrMylo($isLyloOrMyloArray,$lastPostNumber));
          }


          foreach($deadList as $nightKilledDeath)
          {

            if ($nightKilledDeath->getNightOfDeath() == $day->getDayNumber())
            {

              $playerToDie = static_functions::get_player_reference($players,$nightKilledDeath->getPlayerToDie()->getName());
              if ($playerToDie == null)
              {
                 $votecount->addError("Could not find player to night kill. Verify settings and check if " . $nightKilledDeath->getOriginalInput() . " exists.");
              }
              else if (!$playerToDie->isAlive())
              {
                $votecount->addError("Could not kill already dead player. Verify settings and check if " . $playerToDie->getName() . " should be alive.");
              }
              else {
                $playerToDie->kill();
              }
            }
          }



          array_push($votecounts,$votecount);

      }


      return array($votecounts);
    }



}
