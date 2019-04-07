<?php

namespace mafiascum\votecounter_extension\dataclasses;

use mafiascum\votecounter_extension\dataclasses\prodtimer as prodtimer;
use mafiascum\votecounter_extension\dataclasses\lyloOrMylo as lyloOrMylo;
use mafiascum\votecounter_extension\dataclasses\player as player;
use mafiascum\votecounter_extension\dataclasses\replacement as replacement;
use mafiascum\votecounter_extension\dataclasses\playerModifier as playerModifier;
use mafiascum\votecounter_extension\dataclasses\nightkill as nightkill;
use mafiascum\votecounter_extension\dataclasses\resurrection as resurrection;
use mafiascum\votecounter_extension\dataclasses\dayvig as dayvig;
use mafiascum\votecounter_extension\dataclasses\modkill as modkill;
use mafiascum\votecounter_extension\helper\static_functions as static_functions;

class votecountSettings {

    protected $cache;
    protected $inputString;
    protected $errorArray;



    //Prompt constants
    private const PLAYER_TEXT_PROMPT = "playerList=";
    private const REPLACEMENTS_LIST_PROMPT = "replacementList=";
    private const MOD_LIST_PROMPT = "moderatorNames=";
    private const DAY_NUMBERS_PROMPT = "dayStartNumbers=";
    private const DEAD_LIST_PROMPT = "deadList=";
    private const DEADLINE_PROMPT = "deadline=";
    private const VOTE_NUMBER_INPUT = "priorVCNumber=";
    private const COLOR_HASH_CODE = "color=";
    private const PROD_TIMER = "prodTimer=";
    private const FONT_OVERRIDE = "fontOverride=";
    private const DAY_VIGGED_PLAYERS_PROMPT = "dayviggedPlayers=";
    private const RESURRECTED_PLAYERS_PROMPT = "resurrectedPlayers=";
    private const MOD_KILLED_PLAYERS_PROMPT = "modkilledPlayers=";
    private const LYLO_OR_MYLO_NUMBERS_PROMPT = "lyloOrMyloPostNumbers=";
    private const PLAYER_MODIFIER_ARRAY_PROMPT = "playerModifiers=";


    //Value constants
    private const VALUE_MISSING = "VALUE NOT FOUND AND REQUIRED";
    private const VALUE_EXISTS = "VALUE FOUND";
    private const VALUE_OPTIONAL_AND_DOESNT_EXIST = "VALUE NOT FOUND AND OPTIONAL";

    //Default Values and definitions;

    protected $defaultProdTimer;
    protected $wordDictionary = array();
    protected $players = array();
    protected $replacementList = array();
    protected $moderatorList = array();
    protected $dayStartNumbers = array();
    protected $deadList = array();
    protected $ressurectedList = array();
    protected $dayviggedList = array();
    protected $modkilledList = array();
    protected $lyloOrMyloArray = array();
    protected $playerModifierArray = array();
    protected $deadline;
    protected $color;
    protected $fontOverride;
    protected $prodTimer;



    //Getters and Setters
    public function getPlayers()
    {
      return $this->players;
    }
    public function getReplacements()
    {
      return $this->replacementList;
    }
    public function getModeratorList()
    {
      return $this->moderatorList;
    }
    public function getDayStartNumbers()
    {
      return $this->dayStartNumbers;
    }
    public function getDeadList()
    {
      return $this->deadList;
    }
    public function getRessurectedList()
    {
      return $this->ressurectedList;
    }
    public function getDeadline()
    {
      return $this->deadline;
    }
    public function getColor()
    {
      return $this->color;
    }
    public function getFontOverride()
    {
      return $this->fontOverride;
    }
    public function getProdTimer()
    {
      return $this->prodTimer;
    }
    public function getDayviggedList()
    {
      return $this->dayviggedList;
    }
    public function getModkilledList()
    {
      return $this->modkilledList;
    }
    public function getLyloOrMyloArray()
    {
      return $this->lyloOrMyloArray;
    }
    public function getPlayerModifierArray()
    {
      return $this->playerModifierArray;
    }

    //End getters and setters

    private static function get_default_prod_timer()
    {
      return new prodtimer(2,0,0,0);

    }

    private static function all_prompts()
    {
        $staticPromptArray = array();
        //Format is prompt name, then if it is required or not.
        array_push($staticPromptArray, array(votecountSettings::PLAYER_TEXT_PROMPT, true));
        array_push($staticPromptArray, array(votecountSettings::REPLACEMENTS_LIST_PROMPT, false));
        array_push($staticPromptArray, array(votecountSettings::MOD_LIST_PROMPT, true));
        array_push($staticPromptArray, array(votecountSettings::DAY_NUMBERS_PROMPT, false));
        array_push($staticPromptArray, array(votecountSettings::DEAD_LIST_PROMPT, false));
        array_push($staticPromptArray, array(votecountSettings::DEADLINE_PROMPT, true));
        array_push($staticPromptArray, array(votecountSettings::VOTE_NUMBER_INPUT, false));
        array_push($staticPromptArray, array(votecountSettings::COLOR_HASH_CODE, false));
        array_push($staticPromptArray, array(votecountSettings::PROD_TIMER, false));
        array_push($staticPromptArray, array(votecountSettings::FONT_OVERRIDE, false));
        array_push($staticPromptArray, array(votecountSettings::DAY_VIGGED_PLAYERS_PROMPT, false));
        array_push($staticPromptArray, array(votecountSettings::MOD_KILLED_PLAYERS_PROMPT, false));
        array_push($staticPromptArray, array(votecountSettings::RESURRECTED_PLAYERS_PROMPT, false));
        array_push($staticPromptArray, array(votecountSettings::LYLO_OR_MYLO_NUMBERS_PROMPT, false));
        array_push($staticPromptArray, array(votecountSettings::PLAYER_MODIFIER_ARRAY_PROMPT,false));

        return $staticPromptArray;
    }

    public function __construct($cache,$homeDir,$db,$inputString)
    {
      $this->inputString = $inputString;
      $this->cache = $cache;
      $this->errorArray = $this->build_settings($homeDir,$db);
    }

    public function getErrorArray()
    {
        return $this->errorArray;
    }

    public function getDictionary($homeDir)
    {

        $dictionaryToReturn = $this->wordDictionary;
        if ($dictionaryToReturn != null)
        {

            return $dictionaryToReturn;
        }
        else {
            $cachedDictionary = false;
            if ($this->cache != null)
            {
              $cachedDictionary = $this->cache->get('wordDictionary');
            }

            if ($cachedDictionary == false)
            {

                $builtDictionary = static_functions::build_word_dictionary($homeDir);
                if ($this->cache != null)
                {
                  $this->cache->put('wordDictionary', $builtDictionary);
                  $this->cache->save();
                }
                return $builtDictionary;
            }
            else {

                return $cachedDictionary;
            }
        }
    }

    private function build_settings($homeDir,$db)
    {
        $errorArray = array();
        $dictionary = $this->getDictionary($homeDir);
        if (count($dictionary) ==1)
        {


            array_push($errorArray, $dictionary[0]);
            return $errorArray;
        }
        else if (count($dictionary) == 0)
        {

            array_push($errorArray, "No dictionary data received.");
            return $errorArray;
        }


        foreach(votecountSettings::all_prompts() as $setting)
        {
            $promptData = array();
            $promptData = $this->extract_settings_value($setting[0], $setting[1]);

            switch ($promptData[2]) {
                  case votecountSettings::VALUE_MISSING:
                      array_push($errorArray, array($promptData[0],votecountSettings::VALUE_MISSING));
                      break;
                  case votecountSettings::VALUE_OPTIONAL_AND_DOESNT_EXIST:
                      switch($promptData[0])
                      {
                        case votecountSettings::REPLACEMENTS_LIST_PROMPT:
                            $this->replacementList = array();
                            break;
                        case votecountSettings::DAY_NUMBERS_PROMPT:
                            $this->dayNumbers = array(0);
                            break;
                        case votecountSettings::DEAD_LIST_PROMPT:
                            $this->deadList = array();
                            break;
                        case votecountSettings::RESURRECTED_PLAYERS_PROMPT:
                            $this->resurrectedPlayers = array();
                            break;
                        case votecountSettings::LYLO_OR_MYLO_NUMBERS_PROMPT:
                            $this->lyloOrMyloArray = lyloOrMylo::build_initial_array();
                            break;
                        case votecountSettings::PLAYER_MODIFIER_ARRAY_PROMPT:
                            $this->playerModifierArray = array();
                        case votecountSettings::PROD_TIMER:
                            $this->prodTimer = votecountSettings::get_default_prod_timer();
                            break;
                        default:
                          break;
                      }
                      continue;
                      break;
                  case votecountSettings::VALUE_EXISTS:

                      switch($promptData[0])
                      {
                          case votecountSettings::PLAYER_TEXT_PROMPT:
                            $this->assign_value_and_handle_errors($this->players, $errorArray, player::instantiate_players($dictionary,$db,$promptData[1]), $promptData[0]);

                            break;
                          case votecountSettings::REPLACEMENTS_LIST_PROMPT:
                            $this->assign_value_and_handle_errors($this->replacementList, $errorArray, replacement::instantiate_replacements($dictionary,$db,$this->players,$promptData[1]), $promptData[0]);
                            break;
                          case votecountSettings::MOD_LIST_PROMPT:
                            $this->assign_value_and_handle_errors($this->moderatorList, $errorArray, $this->build_moderator_names($db,$promptData[1]), $promptData[0]);
                            break;
                          case votecountSettings::DAY_NUMBERS_PROMPT:
                              $this->assign_value_and_handle_errors($this->dayStartNumbers, $errorArray, $this->parse_number_list_string('dayNumbers', $promptData[1]),  $promptData[0]);
                              break;
                          case votecountSettings::DEAD_LIST_PROMPT:
                              $this->assign_value_and_handle_errors($this->deadList, $errorArray, $this->parse_dead_list_string($this->players,$promptData[1]), $promptData[0]);

                              break;
                          case votecountSettings::RESURRECTED_PLAYERS_PROMPT:
                              $this->assign_value_and_handle_errors($this->ressurectedList, $errorArray, $this->parse_resurrected_list_string($this->players,$promptData[1]), $promptData[0]);
                              break;
                          case votecountSettings::DAY_VIGGED_PLAYERS_PROMPT:
                              $this->assign_value_and_handle_errors($this->dayviggedList, $errorArray, $this->parse_dayvigged_list_string($this->players,$promptData[1]), $promptData[0]);
                              break;

                          case votecountSettings::MOD_KILLED_PLAYERS_PROMPT:
                              $this->assign_value_and_handle_errors($this->modkilledList, $errorArray, $this->parse_modkilled_list_string($this->players,$promptData[1]), $promptData[0]);
                              break;
                          case votecountSettings::DEADLINE_PROMPT:
                              $this->assign_value_and_handle_errors($this->deadline, $errorArray, $this->check_string_cleanliness_or_comma($promptData[1]), $promptData[0]);
                              break;
                          case votecountSettings::COLOR_HASH_CODE:
                              $this->assign_value_and_handle_errors($this->color, $errorArray, $this->check_string_cleanliness($promptData[1]), $promptData[0]);
                              break;
                          case votecountSettings::FONT_OVERRIDE:
                              $this->assign_value_and_handle_errors($this->fontOverride, $errorArray, $this->check_string_cleanliness($promptData[1]), $promptData[0]);
                              break;
                          case votecountSettings::PROD_TIMER:
                              $this->assign_value_and_handle_errors($this->prodTimer,$errorArray,$this->check_prod_timer($promptData[1]), $promptData[0]);
                              break;
                          case votecountSettings::VOTE_NUMBER_INPUT:
                              $this->assign_value_and_handle_errors($this->prodTimer,$errorArray,$this->check_if_setting_is_number($promptData[1]), $promptData[0]);
                              break;
                          case votecountSettings::LYLO_OR_MYLO_NUMBERS_PROMPT:
                              $this->assign_value_and_handle_errors($this->lyloOrMyloArray, $errorArray,$this->parse_lylo_numbers($promptData[1]), $promptData[0]);
                              break;
                          case votecountSettings::PLAYER_MODIFIER_ARRAY_PROMPT:
                              $this->assign_value_and_handle_errors($this->playerModifierArray, $errorArray,$this->parse_player_modifiers($this->players,$promptData[1]), $promptData[0]);
                              break;
                          default:

                            array_push($errorArray, $promptData[0] . ' - ' . $promptData[1] . ' has not been implemented yet. ');
                            break;
                      }
                      break;

            }

        }

        return $errorArray;
    }

    private static function checkIsBool($string){
        $string = strtolower($string);
        return (in_array($string, array("true", "false", "1", "0", "yes", "no"), true));
    }

    private function parse_player_modifiers(&$players,$string)
    {
      if (($string == null) || strlen(trim($string)) == 0)
      {
          return array('$lyloString', "lylo post number string was empty");
      }
      $list = explode(",", $string);
      if (count($list) == 0)
      {
          return array($string, '$playerModifierList has no entries');
      }
      $playerSettingsModifierArray = array();
      $validModifiers = playerModifier::all_valid_modifiers();
      foreach($list as $potentialModifierString)
      {
        $playerModifierArray = explode("-", trim($potentialModifierString));
        if (count($playerModifierArray) !=4 )
        {
            return array($potentialModifierString, $potentialModifierString . ' was not formatted correctly. It is playerName-ModifierName-postNumber-value . Ex: MathBlade-hated-314-true');
        }
        else {
          $nameString = $playerModifierArray[0];
          $modifierName = $playerModifierArray[1];
          $modifierPostNumber = $playerModifierArray[2];
          $modifierValue = $playerModifierArray[3];

          $playerReference = static_functions::get_player_exact_reference($players,$nameString);
          if ($playerReference == null)
          {
              $errorString = $errorString . $nameString . ' is not a valid player. Please check for typos.';
          }
          if (!is_numeric($modifierPostNumber))
          {
              $errorString = $errorString . $modifierPostNumber . ' is not a valid postNumber. Please check for typos.';
          }
          $thisModifier = null;
          foreach($validModifiers as $validModifier)
          {
              if (strcmp(strtolower($validModifier->getName()),strtolower($modifierName))==0)
              {
                $thisModifier = $validModifier;
                break;
              }
          }
          if ($thisModifier == null)
          {
                $errorString = $errorString . $modifierName . ' is not a valid modifier. Please check for typos.';
          }
          else {
              $thisModifierType = $thisModifier->getType();


              switch($thisModifierType)
              {
                  case playerModifier::BOOL_PLAYER_MODIFIER_TYPE_INT:
                    $validBool = votecountSettings::checkIsBool($modifierValue);
                    if (!$validBool)
                    {
                      $errorString = $errorString . $modifierValue . ' is not a valid bool (true or false). Please check for typos.';
                    }
                    break;
                  //This string has no validation yet.
                  case playerModifier::BOOL_PLAYER_MODIFIER_TYPE_INT:
                    break;
                  default:
                    $errorString = $errorString . $modifierName . ' does not have a valid modifier type. Was given: ' . $thisModifierType . ' Please check for typos.';
                    break;
              }
          }

          if ($errorString != '')
          {

              return array($potentialModifierString, $errorString);
          }
          else {

              array_push($playerSettingsModifierArray, array($playerReference,$modifierName,$modifierPostNumber,$modifierValue));
          }


        }
      }

      return array($playerSettingsModifierArray);
    }

    private function parse_lylo_numbers($string)
    {
      if (($string == null) || strlen(trim($string)) == 0)
      {
          return array('$lyloString', "lylo post number string was empty");
      }
      $list = explode(",", $string);
      if (count($list) == 0)
      {
          return array($string, '$listString has no entries');
      }
      $failedList = array();
      $cleanedList = array();
      $lyloPosts = lyloOrMylo::build_initial_array();

      foreach($list as $potentialLyloString)
      {
        $lyloArray = explode("-", trim($potentialLyloString));
        if (count($lyloArray) !=2 )
        {
            return array($potentialLyloString, $potentialLyloString . ' was not formatted correctly. It is postNumber-IsLylo . Ex: 314-true');
        }
        else {
          $postNumber = $lyloArray[0];
          $isLylo = $lyloArray[1];

          $errorString = '';
          if (!is_numeric($postNumber))
          {
              $errorString = $postNumber . ' is not a post. Please check for typos.';

          }
          else if (!votecountSettings::checkIsBool($isLylo))
          {
              $errorString = $isLylo . ' is not a bool value (true or false). Please check for typos.';
          }

          if ($errorString != '')
          {
              return array($potentialLyloString, $errorString);
          }
          else {
              array_push($lyloPosts, new lyloOrMylo($postNumber,$isLylo));
          }
        }
      }

      return array($lyloPosts);
    }

    private function check_if_setting_is_number($string)
    {
        if (is_numeric(trim($string)))
        {
            return array(trim($string));
        }
        else {
            return array($string, $string . " was not a number");
        }
    }

    private function check_prod_timer($prodTimerString)
    {
        $prodTimerArray = explode(',', $prodTimerString);
        if (count($prodTimerArray) != 4)
        {
            return array($prodTimerString, $prodTimerString . ' was not formatted correctly. It is #,#,#,# formatted for days,hours,minutes,seconds and # is a number.');
        }

        $formattedCorrectly = true;
        foreach($prodTimerArray as $timeframe)
        {
            if(!is_numeric(trim($timeframe)))
            {
              $formattedCorrectly = false;
            }
        }

        if ($formattedCorrectly == false)
        {
          return array($prodTimerString, $prodTimerString . ' was not formatted correctly. It is #,#,#,# formatted for days,hours,minutes,seconds and # is a number.');
        }
        else {
          return array(new prodtimer($prodTimerArray[0],$prodTImerArray[1],$prodTImerArray[2],$prodTimerArray[3]));
        }
    }


    private function check_string_cleanliness_or_comma($string)
    {
      if (static_functions::string_is_clean_or_comma(trim($string)))
      {
          return array($string);
      }
      else {
          return array($string,$string . ' has improper characters. Please revise.');
      }
    }

    private function check_string_cleanliness($string)
    {
      if (static_functions::string_is_clean(trim($string)))
      {
          return array($string);
      }
      else {
          return array($string,$string . ' has improper characters. Please revise.');
      }
    }

    private function parse_dead_list_string(&$players, $deadListString)
    {
        if (($deadListString == null) || strlen(trim($deadListString)) == 0)
        {
            return array('$deadListString', "dead list string was empty");
        }
        else {
          $nightKills = array();
          $listString = trim($deadListString);


          $list = explode(",", $deadListString);
          if (count($list) == 0)
          {
              return array($deadListString, '$listString has no entries');
          }
          else {
              $failedList = array();
              $cleanedList = array();
              foreach($list as $potentialNKString)
              {

                  $NKarray = explode("-", trim($potentialNKString));
                  if (count($NKarray) !=2 )
                  {
                      return array($potentialNKString, $potentialNKString . ' was not formatted correctly. It is playername-Night died. Ex: MathBlade-3');
                  }
                  else {
                      $nameString = $NKarray[0];
                      $nightDied = $NKarray[1];

                      $errorString = '';

                      $playerReference = static_functions::get_player_reference($players,$nameString);
                      if ($playerReference == null)
                      {
                          $errorString = $nameString . ' is not a valid player. Please check for typos.';

                      }
                      else if (!is_numeric($nightDied))
                      {
                          $errorString = $nightDied . ' is not a number. Please check for typos.';
                      }

                      if ($errorString != '')
                      {
                          return array($potentialNKString, $errorString);
                      }
                      else {

                          array_push($nightKills, new nightkill($playerReference,$nightDied,trim($potentialNKString)));
                      }

                  }

              }


              return array($nightKills);

          }

        }
    }


    private function parse_player_post_list_string(&$players,$classReference,$stringInput)
    {
        if ($stringInput == null || strlen(trim($stringInput)) == 0)
        {
            return null;
        }
        else {
            $returnArray = array();
            $string = trim($stringInput);
            $list = explode(",",$stringInput);
            if (count($list) == 0)
            {
              return array($stringInput, "given string has no entries");
            }
            else {
              $failedList = array();
              $cleanedList = array();
              foreach($list as $potentialEntry)
              {
                  $stringArray = explode("-", trim($potentialEntry));
                  if (count($stringArray) != 2)
                  {
                    return array($stringInput, $potentialEntry . ' was not formatted correctly. It is playername-postnumber of event. Ex: MathBlade-314');
                  }
                  else {
                    $nameString = $stringArray[0];
                    $postNumber = $stringArray[1];
                    $errorString = '';

                    $playerReference = static_functions::get_player_reference($players,$nameString);
                    if ($playerReference == null)
                    {
                        $errorString = $nameString . ' is not a valid player. Please check for typos.';

                    }
                    if (!is_numeric($postNumber))
                    {
                        $errorString = $errorString . "/r/n" . $postNumber . ' is not a number. Please check for typos.';
                    }

                    if ($errorString != '')
                    {
                        return array($potentialEntry, $errorString);
                    }
                    else {
                        array_push($returnArray, new $classReference($playerReference,$postNumber));
                    }

                  }

              }

              return array($returnArray);
            }
        }
    }


    private function parse_resurrected_list_string(&$players, $resurrectedListString)
    {

      return $this->parse_player_post_list_string($players,resurrection::class,$resurrectedListString);

    }


    private function parse_dayvigged_list_string(&$players, $dayviggedListString)
    {

        return $this->parse_player_post_list_string($players,dayvig::class,$dayviggedListString);

    }

    private function parse_modkilled_list_string(&$players, $modkilledListString)
    {

        return $this->parse_player_post_list_string($players,modkill::class,$modkilledListString);

    }


    private function parse_number_list_string($identifier,$listString)
    {
        if ($listString == null || strlen(trim($listString)) == 0)
        {
            return array('$listString', $identifier . ' was null');
        }
        $listString = trim($listString);


        $list = explode(",", $listString);
        if (count($list) == 0)
        {
            return array($listString, '$listString has no entries');
        }
        else {
            $failedList = array();
            $cleanedList = array();
            foreach($list as $potentialNumber)
            {
                $cleanedString = trim($potentialNumber);
                if (!is_numeric($cleanedString))
                {
                  array_push($failedList, $cleanedString);
                }
                else {
                  array_push($cleanedList,$cleanedString);
                }

            }

            if (count($failedList) > 0)
            {
                return array($listString, $identifier . ' has invalid entries: ' . implode(",", $failedList));
            }
            else {
                return array($cleanedList);
            }

        }
    }

    private function build_moderator_names($db,$modString)
    {
        if ($modString == null || strlen(trim($modString)) == 0)
        {
            return array('modString was null', 'Need a mod name string to parse');
        }
        else {
            $modArray = explode(',', $modString);
            foreach($modArray as $mod)
            {
                if (static_functions::string_is_clean($mod) && static_functions::is_valid_user($db,$mod))
                {
                    continue;
                }
                else {
                    return array($modString, 'modString has invalid values.');
                }
            }

            return array($modArray);
        }

    }

    private function assign_value_and_handle_errors(&$valueToAssign, &$mainErrorList, $results, $identifier)
    {
        if (count($results) == 0)
        {

            array_push($mainErrorList, 'No results for identifier: ' . $identifier . " Check settings.");

        }
        else if (count($results[1]) == 0)
        {

          $valueToAssign = $results[0];
        }
        else
        {
          if (is_array($results[1]))
          {
            foreach($results[1] as $error)
            {
              array_push($mainErrorList,$error);
            }
          }
          else {
            array_push($mainErrorList,$results[1]);
          }


        }



    }

    private function extract_settings_value($prompt, $isRequired)
    {
        $matchedTagCount = array();
        preg_match('/' . $prompt . '(.*)' . '/', $this->inputString, $matchedTagCount);

        $value = $matchedTagCount[1];
        if (strlen($value) == 0 && $isRequired)
        {
            $errorArray = array();
            array_push($errorArray,$prompt);
            array_push($errorArray, '');
            array_push($errorArray, votecountSettings::VALUE_MISSING);

            return $errorArray;
        }
        else if (strlen($value) == 0)
        {
          $missingButOKArray = array();
          array_push($missingButOKArray,$prompt);
          array_push($missingButOKArray, '');
          array_push($missingButOKArray, votecountSettings::VALUE_OPTIONAL_AND_DOESNT_EXIST);

          return $missingButOKArray;
        }
        else {
          $presentArray = array();
          array_push($presentArray,$prompt);
          array_push($presentArray, str_replace('<br/>','', $value));
          array_push($presentArray, votecountSettings::VALUE_EXISTS);

          return $presentArray;
        }



        //echo "PROMPT: " . $prompt . ' - ' . $value;

    }


}
