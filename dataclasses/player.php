<?php

namespace mafiascum\votecounter_extension\dataclasses;


use \mafiascum\votecounter_extension\dataclasses\playerModifier as playerModifier;

class player {


    protected $creationError;

    //These will never change. If you're trying to change these except during replacement initialization something is wrong.
    protected $playerName;
    protected $nicknames = array();
    protected $abbreviations = array();
    protected $wordsInName = array();

    //This is something that if not set gets set once then forget about it.
    protected $friendlyPlayerName;
    protected $friendlyNicknames = array();

    //These will change often. Using setters or adders etc. Not modifiers.
    protected $isAlive;
    protected $playerCurrentlyVoting;
    protected $postNumberOfVote;
    protected $postNumbers = array();
    protected $timeOfLastPost;

    //These are the modifiers of statuses that can change midgame.
    protected $playerModifiers = array();
    protected $linkedReplacements = array();





    public function __construct($playerName, $nicknamesArray, $abbreviations, $wordsInName)
    {
        $this->playerName = $playerName;
        $this->updateFriendlyName();
        $this->nicknames = $nicknamesArray;
        $this->updateFriendlyNicknames();
        $this->abbreviations = $abbreviations;
        $this->wordsInName = $words;
        $this->playerModifiers = playerModifier::all_valid_modifiers();


        $this->isAlive = true;
        $this->playerCurrentlyVoting = null;
        $this->postNumbers = array();

        $this->timeOfLastPost = null;
        $this->linkedReplacements = array();
    }

    public function addLinkedReplacement($replacement)
    {
        array_push($this->linkedReplacements, $replacement);
    }


    public function getModifiers()
    {
        return $this->playerModifiers;
    }

    private function getModifier($modifierName)
    {
      foreach($this->getModifiers() as $modifier)
      {
          if (strcmp($modifierName, $modifier->getName()) === 0)
          {

            return $modifier;
          }
      }
      return null;
    }

    public function addModifier($modifierName, $value, $postNumberEffective)
    {
        $modifier = $this->getModifier($modifierName);
        if ($modifier != null)
        {

          $modifier->addModifierPost($value,$postNumberEffective);
          return null;
        }

        return array("Modifier Not found", "Add modifier " . $modifierName . " to valid modifier list. ");
    }

    public function isLoved($currentPostNumber,$isLyloOrMylo)
    {

      //echo $this->getName() . "IS LOVED: " . $this->getValueForModifier(playerModifier::LOVED_MODIFIER_NAME,$currentPostNumber,$isLyloOrMylo) . "<br/>";

        return filter_var($this->getValueForModifier(playerModifier::LOVED_MODIFIER_NAME,$currentPostNumber,$isLyloOrMylo), FILTER_VALIDATE_BOOLEAN);

    }

    public function isHated($currentPostNumber,$isLyloOrMylo)
    {
      return filter_var($this->getValueForModifier(playerModifier::HATED_MODIFIER_NAME,$currentPostNumber,$isLyloOrMylo),FILTER_VALIDATE_BOOLEAN);
    }

    public function isTreestump($currentPostNumber,$isLyloOrMylo)
    {

      return filter_var($this->getValueForModifier(playerModifier::TREESTUMP_MODIFIER_NAME,$currentPostNumber,$isLyloOrMylo),FILTER_VALIDATE_BOOLEAN);
    }


    private function getValueForModifier($modifierName,$currentPostNumber,$isLyloOrMylo)
    {

      $modifier = $this->getModifier($modifierName);

      if ($modifier != null)
      {

        if ($isLyloOrMylo === true)
        {

          return $modifier->getLyloMyloValue($currentPostNumber);
        }
        else {

          return $modifier->getValueAtPostNumber($currentPostNumber);
        }

      }

      return null;

    }

    //Getter and setter functions
    private function getName()
    {
      return $this->playerName;
    }

    public function getExactName()
    {
      return $this->playerName;
    }

    public function getFriendlyName()
    {
        if ($this->friendlyPlayerName != null)
        {
            return $this->friendlyPlayerName;
        }
        else {
          $this->updateFriendlyName();
        }

        return $this->friendlyPlayerName;

    }

    private function updateFriendlyName()
    {
        $this->friendlyPlayerName = \mafiascum\votecounter_extension\helper\static_functions::make_friendly($this->playerName);
    }

    public function getReplacementAtPostNumber($postNumber)
    {
        return \mafiascum\votecounter_extension\helper\static_functions::get_replacement_by_post_number($this->linkedReplacements,$postNumber);
    }

    public function getLinkedReplacements()
    {
        return $this->linkedReplacements;
    }
    public function getDisplayName($currentPostNumber)
    {

      if ($this->linkedReplacements != null)
      {
        $replacementToUse = \mafiascum\votecounter_extension\helper\static_functions::get_replacement_by_post_number($this->linkedReplacements,$currentPostNumber);

        if ($replacementToUse != null)
        {

            return $replacementToUse->getNewPlayer()->getName();
        }
        else {

          return $this->getName();
        }
      }
      else {
        return $this->getName();
      }

    }
    public function getLatestDisplayName()
    {
        return $this->getDisplayName(314159);
    }

    public function setName($name)
    {
        $this->playerName = $name;
        $this->updateFriendlyName();
    }

    public function getNicknames()
    {
      return $this->nicknames;
    }

    public function getFriendlyNicknames()
    {
      $this->updateFriendlyNicknames();
      return $this->friendlyNicknames;

    }

    public function getPostNumberOfVote()
    {
      return $this->postNumberOfVote;
    }

    public function setPostNumberOfVote($postNumber)
    {
      $this->postNumberOfVote = $postNumber;
    }

    public function getNumberOfPostsInDay($firstPost,$lastPost)
    {
      $numberOfPosts = 0;

      foreach($this->postNumbers as $postNumber)
      {

          if (($postNumber >= $firstPost) && ($postNumber <= $lastPost))
          {

              $numberOfPosts = $numberOfPosts + 1;
          }
      }

      return $numberOfPosts;
    }

    public function getAbbreviations()
    {
        return $this->abbreviations;
    }
    public function addAbbreviations($abbreviations)
    {
        \mafiascum\votecounter_extension\helper\static_functions::add_all_array_elements_to_array($this->abbreviations, $abbreviations);
    }

    public function addNicknames($nicknames)
    {
        if (!is_array($this->nicknames))
        {
          $this->nicknames = array();
        }
        \mafiascum\votecounter_extension\helper\static_functions::add_all_array_elements_to_array($this->nicknames, $nicknames);
        $this->updateFriendlyNicknames();
    }

    private function updateFriendlyNicknames()
    {
        $cleanedNicknames = array();
        if (!is_array($this->friendlyNicknames) || count($this->friendlyNicknames) != count($this->nicknames))
        {
          foreach($this->nicknames as $uncleanNickname)
          {
              array_push($cleanedNicknames, \mafiascum\votecounter_extension\helper\static_functions::make_friendly($uncleanNickname));
          }
        }
        $this->friendlyNicknames = $cleanedNicknames;
    }

    public function addNickname($nickname)
    {
        if (!is_array($this->nicknames))
        {
          $this->nicknames = array();
        }
        array_push($this->nicknames, $nickname);
        $this->updateFriendlyNicknames();
    }

    public function getWordsInName()
    {
        return $this->wordsInName;
    }
    public function addWordsInName($words)
    {
        \mafiascum\votecounter_extension\helper\static_functions::add_all_array_elements_to_array($this->wordsInName, $words);
    }



    public function isAlive()
    {
        return $this->isAlive;
    }

    //Uses different function in case have to do weird reset logic.
    public function kill()
    {
        $this->setAliveStatus(false);
    }

    public function revive()
    {
        $this->setAliveStatus(true);
    }

    private function setAliveStatus($bool)
    {
        $this->isAlive = $bool;
    }

    public function getPlayerCurrentlyVoting()
    {
        return $this->playerCurrentlyVoting;
    }
    public function setPlayerCurrentlyVoting($player)
    {
        $this->playerCurrentlyVoting = $player;
    }

    public function getPostNumbers()
    {
        return $this->postNumbers;
    }

    public function addPostNumber($postNumber)
    {
        array_push($this->postNumbers, $postNumber);
    }

    public function getTimeOfLastPost()
    {
        return $this->timeOfLastPost;
    }

    public function setTimeOfLastPost($time)
    {
        return $this->timeOfLastPost = $time;
    }


    public static function getMajorityCount($players,$postNumber,$isLyloOrMylo)
    {
        $numAlive = player::getNumberOfPlayersAlive($players);
        $numTreestumped = player::getNumberOfPlayersTreestumped($players,$postNumber,$isLyloOrMylo);
        return (floor(($numAlive - $numTreestumped) / 2) + 1);
    }


    public static function getPlayersAlive($players)
    {
      $playersAlive =array();

      foreach($players as $player)
      {
          if ($player->isAlive())
          {
              array_push($playersAlive,$player);
          }
      }

      return $playersAlive;
    }
    public static function getNumberOfPlayersAlive($players)
    {
        return count(player::getPlayersAlive($players));
    }

    public static function getNumberOfPlayersTreestumped($players,$postNumber,$isLyloOrMylo)
    {

          $numTreestumped = 0;
          foreach($players as $player)
          {
              if ($player->isTreestump($postNumber,$isLyloOrMylo))
              {
                 $numTreestumped = $numTreestumped + 1;
              }
          }

          return $numTreestumped;
    }

    //Work functions
    public static function instantiate_players($dictionary,$db,$playersSettingString)
    {
          //All players in the game.
          $allPlayersInGame = array();
          $errorArray = array();


          $namePlusNicksArray = explode(",",$playersSettingString);
          foreach($namePlusNicksArray as $namePlusNicknamesString)
          {
              $result = Player::parse_player_string($dictionary,$db,$namePlusNicknamesString);
              if (count($result) > 1)
              {
                  if (strlen($result[1]))
                  {
                    array_push($errorArray, $result[1]);
                  }
                  else {
                    array_push($allPlayersInGame, $result[0]);
                  }

              }
              else {
                  array_push($allPlayersInGame, $result[0]);
              }
          }

          return array($allPlayersInGame, $errorArray);

    }

    public static function parse_player_string($dictionary,$db,$namePlusNicknamesString)
    {
      $nicknameResult = array();
      $remainingPlayerString = '';

      //Handle nickname arrays
      preg_match('/' . '{' . '(.*)' . '}/', trim($namePlusNicknamesString), $nicknameResult);
      if (count($nicknameResult) > 0)
      {
          $remainingPlayerString = trim(substr($namePlusNicknamesString, 0, strpos( $namePlusNicknamesString, '{')) . substr($namePlusNicknamesString, strpos( $namePlusNicknamesString, '}') + 1)) ;
          $nicknamesArray = explode("+", $nicknameResult[1]);
          foreach($nicknamesArray as $nickname)
          {
              if (!\mafiascum\votecounter_extension\helper\static_functions::string_is_clean($nickname))
              {
                echo $remainingPlayerString . ' has an invalid nickname. ' . $nickname . '<br/>';
                return array($nickname, $remainingPlayerString . ' has an invalid nickname. ' . $nickname);
              }
          }


      }
      else {
          $remainingPlayerString = $namePlusNicknamesString;
          $nicknamesArray = null;
      }



      if (!\mafiascum\votecounter_extension\helper\static_functions::string_is_clean($remainingPlayerString))
      {
        return array($remainingPlayerString, $remainingPlayerString . ' has an invalid name. This should match the username exactly. Please check it. ');
      }
      else if ((strlen($remainingPlayerString) > 25) || !(\mafiascum\votecounter_extension\helper\static_functions::is_valid_user($db,$remainingPlayerString)))
      {
        return array($remainingPlayerString, $remainingPlayerString . ' is not a valid user. Please double check your entry.');
      }
      else {

        $abbreviations = array();
        //Now check nickname and words in name.
        $abbreviation = null;
        $remainingPlayerString = trim($remainingPlayerString);

        if (!strpos($remainingPlayerString, " ") == false)
        {
            $abbreviation = player::build_abbreviation_from_name($remainingPlayerString, ' ');
        }
        else if (!strpos($remainingPlayerString, " ") == false)
        {
          $abbreviation = player::build_abbreviation_from_name($remainingPlayerString, '_');
        }
        else {
          $abbreviation = player::build_abbreviation_from_capitalization($remainingPlayerString);
        }

        if ($abbreviation != null && strlen($abbreviation) > 0)
        {
            array_push($abbreviations, $abbreviation);
        }

        if (count($abbreviations) == 0)
        {
          $abbreviations = null;
        }


        $wordsInName = \mafiascum\votecounter_extension\helper\static_functions::get_words_in_string($dictionary,$remainingPlayerString);






        return array(new player($remainingPlayerString,$nicknamesArray,$wordsInName,$abbreviations));
      }





    }

    private static function build_abbreviation_from_capitalization($name)
    {

          $indexOfFirstLetter = preg_match('/^\A-Za-z/', $string);

          $abbreviation = substr($name, 0, $indexOfFirstLetter + 1);

          $isUpper = ctype_upper(substr($name,$indexOfFirstLetter,1));


          for ($n = $indexOfFirstLetter + 1; $n < strlen($name); $n++) {
              $char = substr($name,$n,1);
              $charIsLetter = ctype_alpha($char);
              $charIsDigit = is_numeric($char);

              if ($charIsLetter)
              {
                  $charIsUpper = ctype_upper($char);
                  if ($isUpper == $charIsUpper)
                  {
                      $abbreviation .= $char;

                  }
              }
              else if ($charIsDigit)
              {
                  $abbreviation .= $char;
              }
          }

          if (strlen($abbreviation ) < 2)
          {
              return null;
          }
          else {
              return $abbreviation;
          }



    }


    private static function build_abbreviation_from_name($name, $separator)
    {
        $abbreviation = '';
        $words=explode($separator,$name);
        foreach($words as $word)
        {
            $abbreviation .= substr($word,0,1);
        }

        return $abbreviation;

    }



}
