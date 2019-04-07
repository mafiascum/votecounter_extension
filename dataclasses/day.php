<?php

namespace mafiascum\votecounter_extension\dataclasses;

class Day {

    protected $dayNumber;
    protected $votes;
    protected $dayStartsOn;
    protected $dayEndsOn;
    protected $nightDeaths;
    protected $resurrections;



    public function __construct($dayNumber, $dayStartsOn, $dayEndsOn)
    {
        $this->dayNumber = $dayNumber;
        $this->votes = array();
        $this->dayStartsOn = $dayStartsOn;
        $this->dayEndsOn = $dayEndsOn;
        $this->nightDeaths = array();
        $this->resurrections = array();

    }




    public function getDayNumber()
    {
      return $this->dayNumber;
    }

    public function getVotes()
    {
      return $this->votes;
    }
    public function getNightDeaths()
    {
      return $this->nightDeaths;
    }
    public function getResurrections()
    {
      return $this->resurrections;
    }

    public function getFirstPostOfDay()
    {
      return $this->dayStartsOn;
    }
    public function getLastPostOfDay()
    {
      return $this->dayEndsOn;
    }



    public function addVote($vote)
    {
      $votesSoFar = $this->votes;

      if ($vote != null)
      {
        array_push($votesSoFar,$vote);
        $this->votes = $votesSoFar;

      }

    }

    public function addNightDeath($nightDeath)
    {
      $nightDeathsSoFar = $this->nightDeaths;

      if ($nightDeath != null)
      {
        array_push($nightDeathsSoFar,$nightDeath);
        $this->nightDeaths = $nightDeathsSoFar;

      }
    }

    public function addResurrection($resurrection)
    {
      $resurrectionsSoFar = $this->$resurrections;

      if ($resurrection != null)
      {
        array_push($resurrectionsSoFar,$resurrection);
        $this->resurrections = $resurrectionsSoFar;

      }
    }

    public static function assign_votes_to_day(&$days,$votes,$lastPostNumber)
    {
      $errors = array();
      $dayInt = 0;
      $isLastDay = true;
      $currentDayPostNumber = 0;
      $nextDayPostNumber = $lastPostNumber; //Failsafe.
      if (count($days) > 0)
      {
        $currentDay = $days[0];
        $currentDayPostNumber = $currentDay->getFirstPostOfDay();
        if (count($days) > 1)
        {
          $isLastDay = false;
          $nextDay = $days[1];
          $nextDayPostNumber = $nextDay->getFirstPostOfDay();
        }
      }
      else
      {
        $currentDay = new \mafiascum\votecounter_extension\dataclasses\Day(1,0,$lastPostNumber);
        $currentDayPostNumber = 0;
        $isLastDay = true;
      }



      foreach($votes as $vote)
      {
          $stillProcessingDay = true;
          $votePostNumber = $vote->getPostNumber();
          while($stillProcessingDay)
          {
            if ($votePostNumber >= $currentDayPostNumber)
            {

                if($isLastDay == true)
                {

                    $currentDay->addVote($vote);
                    $stillProcessingDay = false;

                }
                else if ($votePostNumber < $nextDayPostNumber)
                {

                   $currentDay->addVote($vote);
                   $stillProcessingDay = false;
                }
                else {
                    if (count($days) > $dayInt + 1)
                    {
                        $dayInt = $dayInt + 1;
                        $currentDay = $nextDay;
                        $currentDayPostNumber = $currentDay->getFirstPostOfDay();
                        if (count($days) > $dayInt + 1)
                        {
                          $nextDay = $days[$dayInt +1];
                          $nextDayPostNumber = $nextDay->getFirstPostOfDay();
                        }
                        else {
                          $isLastDay = true;
                          $nextDay = null;
                          $nextDayPostNumber = $lastPostNumber;
                        }

                        //continue;
                    }
                    else
                    {
                      //Unsure what to do with these votes throw error.
                      array_push($errors, array($vote->getPostNumber(), "Could not assign day to vote on post number" . $vote->getPostNumber()));
                      $stillProcessingDay = false;
                    }

                }
            }
          }
      }


      return $errors;
    }

    public static function assign_deaths_to_day(&$days,$nightKills)
    {
        foreach($nightKills as $nightKill)
        {
            $nightPlayerDied = $nightKill->getNightOfDeath();

            if (count($days) >= $nightPlayerDied)
            {
              $days[$nightPlayerDied-1]->addNightDeath($nightKill);
            }
        }

    }


}
