<?php

namespace MathBlade\votecount\dataclasses;

class replacement {



    protected $oldPlayer;
    protected $newPlayer;
    protected $postNumber;


    public function __construct($oldPlayer, $newPlayer, $postNumberOfReplace)
    {

        $this->oldPlayer = $oldPlayer;
        $this->newPlayer = $newPlayer;
        $this->postNumber = $postNumberOfReplace;


    }

    public static function compareReplacementsByPostNumber(&$replacementA, &$replacementB)
    {
        return strcasecmp($replacementA->getPostNumber(), $replacementB->getPostNumber());
    }

    public function getPostNumber()
    {
      return $this->postNumber;
    }

    public function getOldPlayer()
    {
      return $this->oldPlayer;
    }
    public function getNewPlayer()
    {
      return $this->newPlayer;
    }

    public function equals($replacementToCompare)
    {
        if ($replacementToCompare == null)
        {
          return false;
        }
        $oldPlayerName = $this->oldPlayer->getExactName();
        $oldPlayerComparisonName = $replacementToCompare->getOldPlayer()->getExactName();

        $oldNameSame = strcmp($oldPlayerName,$oldPlayerComparisonName) === 0;

        if ($oldNameSame)
        {
          $newPlayerName = $this->newPlayer->getExactName();
          $newPlayerComparisonName = $replacementToCompare->getNewPlayer()->getExactName();

          $newPlayerSame = strcmp($newPlayerName,$newPlayerComparisonName) === 0;

          if($newPlayerSame)
          {
              $postNumberSame = ($this->postNumber - $replacementToCompare->getPostNumber()) === 0;

              return $postNumberSame;
          }
        }






        return false;
    }

    public static function instantiate_replacements($dictionary,$db,&$players,$replacementsSettingString)
    {
          //All players in the game.
          $allPlayersInGame = array();
          $errorArray = array();

          $replacements = null;
          $replacementStringArray = explode(",",$replacementsSettingString);
          foreach($replacementStringArray as $replacementString)
          {

              $replacementNameArray = explode(":", $replacementString);
              if (count($replacementNameArray) != 3)
              {
                  array_push($errorArray, 'Replacement String: ' . $replacementString  . ' not formatted correctly.');
                  continue;
              }
              else {
                $oldPlayerParsed = Player::parse_player_string($dictionary,$db,$replacementNameArray[0]);
                $newPlayerParsed = Player::parse_player_string($dictionary,$db,$replacementNameArray[1]);
                $postNumberOfReplace = $replacementNameArray[2];
                if (count($oldPlayerParsed) > 1 || count($newPlayerParsed) > 1)
                {

                    $errorString = '';
                    if (count($oldPlayerParsed) > 1)
                    {
                        $errorString .= 'OLD PLAYER: ' . $replacementArray[0] .  'NOT FOUND/PARSABLE ';
                    }
                    if (count($newPlayerParsed) > 1)
                    {
                        $errorString .= 'NEW PLAYER: ' . $replacementArray[1] .  'NOT FOUND/PARSABLE ';
                    }
                    array_push($errorArray, trim($errorString));
                    continue;
                }
                else {
                    $oldPlayerFromParse = $oldPlayerParsed[0];
                    $newPlayerFromParse = $newPlayerParsed[0];

                    $oldPlayerReference = \MathBlade\votecount\helper\static_functions::get_player_exact_reference($players,$oldPlayerFromParse->getExactName());

                    //If we don't have an old player reference then it's a replace of a replace.
                    if ($oldPlayerReference == null)
                    {

                      if ($replacements != null)
                      {
                        //$validReplacement = \MathBlade\votecount\helper\static_functions::get_player_exact_reference($replacements,$oldPlayerFromParse->getExactName(),$postNumberOfReplace);
                      //$validReplacement = \MathBlade\votecount\helper\static_functions::get_replacement_by_post_number($this->linkedReplacements,$currentPostNumber);

                        //Check to find the prior replacements.
                        $potentialReplacements = array();
                        foreach($replacements as $replacement)
                        {
                            if (strcmp($replacement->getOldPlayer()->getExactName(), $newPlayerFromParse->getExactName()) === 0)
                            {
                                if (strcmp($replacement->getNewPlayer()->getExactName(), $oldPlayerFromParse->getExactName()) === 0)
                                {
                                    array_push($potentialReplacements, $replacement);
                                    break;
                                }
                            }
                        }

                        $countReplacements = count($potentialReplacements);
                        if ($countReplacements > 0)
                        {
                            //This is intentional to ignore the others.
                            $oldPlayerReference = \MathBlade\votecount\helper\static_functions::get_original_player($potentialReplacements[0],$players);
                            if ($oldPlayerReference == null)
                            {
                              array_push($errorArray, trim('Could not find base player for ' . $replacementString . '. Cannot perform replacement. '));
                              continue;
                            }
                        }




                        if ($validReplacement != null)
                        {
                          //$oldPlayerReference = $validReplacement->getBasePlayer();
                          $oldPlayerReference = \MathBlade\votecount\helper\static_functions::get_player_exact_reference($players,$validReplacement->getOldPlayer()->getExactName());


                        }
                        if ($oldPlayerReference == null)
                        {

                          array_push($errorArray, trim('The first player in the string ' . $replacementString . ' was not in the playerlist or could not be built. '  . $newPlayerParsed[1].   '. Cannot perform replacement. '));
                          continue;
                        }
                        else {
                          $replacements = array();
                          //This cannot be the old player replacement. Need accurate record.
                          $replacement = new replacement($oldPlayerFromParse,$newPlayerFromParse,$postNumberOfReplace);
                          $oldPlayerReference->addLinkedReplacement($replacement);
                        }
                      }
                      else {
                        array_push($errorArray, trim('The first player in the string ' . $replacementString . ' was not in the playerlist or could not be built. '  . $newPlayerParsed[1].   '. Cannot perform replacement. '));
                        continue;
                      }
                    }
                    else if ($newPlayerFromParse == null)
                    {
                      array_push($errorArray, trim('The second player in the string ' . $replacementString . ' could not be read because ' . $newPlayerParsed[1]));
                      continue;
                    }
                    else {

                      $replacements = array();
                      $replacement = new replacement($oldPlayerReference,$newPlayerFromParse,$postNumberOfReplace);
                      $oldPlayerReference->addLinkedReplacement($replacement);
                      /*  //This is so replacements can keep a record.
                        $oldPlayerClone = clone $oldPlayerReference;
                        $newPlayerClone = clone $newPlayerFromParse;

                        //End cloning. The latter is to save some for loops for processing reasons.
                        $oldPlayerReference->addAbbreviations($newPlayerFromParse->getAbbreviations());
                        $oldPlayerReference->addNicknames($newPlayerFromParse->getNicknames());
                        $oldPlayerReference->addNickname($oldPlayerReference->getName());
                        echo "SETTING PLAYER NAME FROM : "  . $oldPlayerReference->getName() . " to " . $newPlayerFromParse->getName() . "<br/>";
                        $oldPlayerReference->setName($newPlayerFromParse->getName());
                        $oldPlayerReference->addWordsInName($newPlayerFromParse->getWordsInName());


                        if ($replacements == null)
                        {
                          $replacements = array();
                        }

                        //This clone is necessary to keep a record. However, above is a reference to the original "player" object.
                        array_push($replacements, new replacement($oldPlayerClone, $newPlayerClone));*/

                        array_push($replacements, $replacement);
                    }

                }


              }


          }


          return array($replacements, $errorArray);

    }




}
