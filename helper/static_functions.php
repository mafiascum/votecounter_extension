<?php

namespace mafiascum\votecounter_extension\helper;

class static_functions {

    public const FILE_DOES_NOT_EXIST = 'file does not exist or cannot be opened';



    public static function get_player_exact_reference(&$players, $name)
    {

      foreach($players as $player)
      {
          if (strcmp($player->getExactName(),$name) === 0)
          {

              return $player;
          }


      }
      foreach($players as $player)
      {
          if (is_array($player->getNicknames()))
          {
            foreach($player->getNicknames() as $nickname)
            {

                if (strcmp($nickname,$name) === 0)
                {

                    return $player;
                }
            }
          }
      }


      return null;

    }


    public static function get_friendly_player_reference(&$players, $name)
    {
      $name = static_functions::make_friendly($name);
      foreach($players as $player)
      {
          if (strcmp($player->getFriendlyName(),$name) == 0)
          {

              return $player;
          }


      }
      foreach($players as $player)
      {
          if (is_array($player->getFriendlyNicknames()))
          {
            foreach($player->getFriendlyNicknames() as $nickname)
            {

                if (strcmp($nickname, $name) == 0)
                {
                    return $player;
                }
            }
          }
      }

      return null;

    }


    public static function make_friendly($unfriendlyName)
    {
        $lowercaseName = strtolower($unfriendlyName);
        $lowercaseNameNoSpaces = preg_replace('/\s+/', '', $lowercaseName);
        $lowercaseNameNoUnderscores = str_replace('_', '', $lowercaseNameNoSpaces);
        $lowercaseNameNoUnderscoresNoDashes = str_replace('-', '', $lowercaseNameNoUnderscores);

        return $lowercaseNameNoUnderscoresNoDashes;

    }
    public static function get_display_name(&$players,&$replacements,$name, $postNumber)
    {
        $replacement = static_functions::get_player_reference_replacements($replacements, $name, $postNumber);
        if ($replacement === null)
        {
            return $name;
        }
        else {
            return $replacement->getDisplayName();
        }

    }


    public static function get_original_player($replacement,&$players)
    {
        foreach($players as $player)
        {
            foreach($player->getLinkedReplacements() as $linkedReplacement)
            {

                if ($linkedReplacement->equals($replacement))
                {
                    return $player;
                }
            }
        }
        return null;
    }


    public static function get_replacement_by_post_number($replacements,$postNumber)
    {
      $replacementToUse = null;
      usort($replacements,['mafiascum\votecounter_extension\dataclasses\replacement', 'compareReplacementsByPostNumber']);

      foreach($replacements as $replacement)
      {

          if ($replacement->getPostNumber() < $postNumber)
          {

              $replacementToUse = $replacement;
          }
      }

      return $replacementToUse;
    }

    public function get_all_valid_replacements_for_post_number(&$players,$postNumber)
    {
        $validPlayerReplacements = array();
        foreach($players as $player)
        {
            $replacement = $player->getReplacementAtPostNumber($postNumber);
            if ($replacement != null)
            {
                array_push($validPlayerReplacements,array($player,$replacement));
            }
        }

        return $validPlayerReplacements;
    }



    public static function get_player_reference_from_vote(&$players,&$replacements,$name,$postNumber)
    {
        $name = static_functions::make_friendly($name);

        $player = static_functions::get_friendly_player_reference($players,$name);
        if ($player != null)
        {

          $playerMatch = $player;
          $replacementMatch = $playerMatch->getReplacementAtPostNumber($postNumber);
          if ($replacementMatch != null)
          {
              return static_functions::get_original_player($replacementMatch,$players);
          }
          else {
              return $playerMatch;
          }

        }
        else {

          $validPlayerReplacements = static_functions::get_all_valid_replacements_for_post_number($players,$postNumber);

          foreach($validPlayerReplacements as $validPlayerReplacement)
          {
              $playerSlot = $validPlayerReplacement[0];
              $validReplacement = $validPlayerReplacement[1];

              if (strcmp($validReplacement->getNewPlayer()->getFriendlyName(),$name) === 0)
              {
                return $playerSlot;
              }
          }

          //Check nicknames
          foreach($players as $player)
          {
              $friendlyNicknames = $player->getFriendlyNicknames();
              if ($friendlyNicknames != null)
              {
                foreach($friendlyNicknames as $nickname)
                {
                  if (strcmp($nickname,$name) === 0)
                  {
                    $playerMatch = $player;
                    $replacementMatch = $playerMatch->getReplacementAtPostNumber($postNumber);
                    if ($replacementMatch != null)
                    {
                        return static_functions::get_original_player($replacementMatch,$players);
                    }
                    else {
                        return $playerMatch;
                    }
                  }
                }
              }
          }

          //If we get here none of the make friendlies worked.

          //Try the old names of the valid replacements
          foreach($validPlayerReplacements as $validPlayerReplacement)
          {
              $playerSlot = $validPlayerReplacement[0];
              $validReplacement = $validPlayerReplacement[1];

              if (strcmp($validReplacement->getOldPlayer()->getFriendlyName(),$name) === 0)
              {

                return array($playerSlot, 'This vote had to use an old replacement in the list. Please have the player confirm this is intended by voting the active player.');
              }
          }



          //Try misspelling detection

          //Check abbreviations
          $playersWithAbbreviation = array();
          foreach($players as $player)
          {
              if ($player->getAbbreviations() != null)
              {
                  foreach($player->getAbbreviations() as $abbreviation)
                  {
                      if (strcmp(static_functions::make_friendly($abbreviation), $name) === 0)
                      {
                          array_push($playersWithAbbreviation, $player);
                      }
                  }
              }
          }

          if (count($playersWithAbbreviation) === 1)
          {
            $player = $playersWithAbbreviation[0];
            $playerMatch = $player;
            $replacementMatch = $playerMatch->getReplacementAtPostNumber($postNumber);
            if ($replacementMatch != null)
            {
                return static_functions::get_original_player($replacementMatch,$players);
            }
            else {
                return $playerMatch;
            }
          }
          else if (count($playersWithAbbreviation) === 0)
          {
            foreach($replacements as $replacement)
            {
                $player = $replacement->getNewPlayer();
                if ($player->getAbbreviations() != null)
                {
                    foreach($player->getAbbreviations() as $abbreviation)
                    {
                        if (strcmp(static_functions::make_friendly($abbreviation), $name) === 0)
                        {
                            array_push($playersWithAbbreviation, $player);
                        }
                    }
                }

            }

            if (count($playersWithAbbreviation) === 1)
            {
              $player = $playersWithAbbreviation[0];
              $playerMatch = $player;
              $replacementMatch = $playerMatch->getReplacementAtPostNumber($postNumber);
              if ($replacementMatch != null)
              {
                  return static_functions::get_original_player($replacementMatch,$players);
              }
              else {
                  return $playerMatch;
              }
            }
          }



          //Check words in name

          $playersWithWord = array();
          foreach($players as $player)
          {
              if ($player->getWordsInName() != null)
              {
                  foreach($player->getWordsInName() as $word)
                  {
                      if (strcmp(static_functions::make_friendly($word), $name) === 0)
                      {
                          array_push($playersWithWord, $player);
                      }
                  }
              }
          }

          if (count($playersWithWord) === 1)
          {
            $player = $playersWithWord[0];
            $playerMatch = $player;
            $replacementMatch = $playerMatch->getReplacementAtPostNumber($postNumber);
            if ($replacementMatch != null)
            {
                return static_functions::get_original_player($replacementMatch,$players);
            }
            else {
                return $playerMatch;
            }
          }
          else if (count($playersWithWord) === 0)
          {
            foreach($replacements as $replacement)
            {
                $player = $replacement->getNewPlayer();
                if ($player->getWordsInName() != null)
                {
                    foreach($player->getWordsInName() as $word)
                    {
                        if (strcmp(static_functions::make_friendly($word), $name) === 0)
                        {
                            array_push($playersWithWord, $player);
                        }
                    }
                }

            }

            if (count($playersWithWord) === 1)
            {
              $player = $playersWithWord[0];
              $playerMatch = $player;
              $replacementMatch = $playerMatch->getReplacementAtPostNumber($postNumber);
              if ($replacementMatch != null)
              {
                  return static_functions::get_original_player($replacementMatch,$players);
              }
              else {
                  return $playerMatch;
              }
            }
          }


          //Check levenshtein distance
          $names = array();
          foreach($players as $player)
          {
            array_push($names, $player->getFriendlyName());
            $friendlyNicknames = $player->getFriendlyNicknames();
            if ($friendlyNicknames != null)
            {
              foreach($friendlyNicknames as $nickname)
              {
                if (!in_array($nickname, $names))
                {
                  array_push($names,$nickname);
                }
              }

            }
          }

          foreach($replacements as $replacement)
          {
              $nameInList = $replacement->getNewPlayer()->getFriendlyName();
              if (!in_array($nameInList, $names))
              {
                array_push($names,$nameInList);
              }

          }

          $closestName = null;
          $closestDist = -1;
          $closestArray = array();

          $shortest = -1;

        // loop through words to find the closest
        foreach ($names as $nameInList) {

            // calculate the distance between the input word,
            // and the current word
            $lev = levenshtein($name, $nameInList);

            // check for an exact match
            if ($lev == 0) {

                // closest word is this one (exact match)
                $closestName = $nameInList;
                $closestArray = null;
                $shortest = 0;

                // break out of the loop; we've found an exact match
                break;
            }

            // if this distance is less than the next found shortest
            // distance, OR if a next shortest word has not yet been found
            if ($lev < $shortest || $shortest < 0) {
                // set the closest match, and shortest distance
                $closestName  = $nameInList;
                $closestArray = null;
                $shortest = $lev;
            }
            else if ($lev === $shortest)
            {
                if ($closestArray == null)
                {
                    $closestArray = array();
                }
                if (!in_array($nameInList,$closestArray))
                {
                  array_push($closestArray, $nameInList);
                }
                if (!in_array($closestName, $closestArray))
                {
                  array_push($closestArray, $closestName);
                }

            }
        }

        //Required in case nickname is the closest string.
        if ($shortest == 0) {

            return static_functions::get_player_reference_from_vote($players,$replacements,$closestName,$postNumber);
        } else if ($closestArray == null){

            return static_functions::get_player_reference_from_vote($players,$replacements,$closestName,$postNumber);
        } else if (is_array($closestArray) && count($closestArray) == 1)
        {

          return static_functions::get_player_reference_from_vote($players,$replacements,$closestArray[0],$postNumber);
        }
        else {
          // Blank for now til come up with any other formatting.
        }





        }

        //echo "Nothing was found for name: " . $name . " <br/>";
        return null;
    }




    public static function add_all_array_elements_to_array(&$arrayToAddTo,$elementsToAdd)
    {
      if ($elementsToAdd != null && count($elementsToAdd) > 0)
      {
          foreach($elementsToAdd as $newElement)
          {
            array_push($arrayToAddTo, $newElement);
          }
      }
    }

    public static function build_word_dictionary($homeDir)
    {
      $words = array();
      if (strcmp($homeDir,'') === 0)
      {
        $homeDir = '.';
      }
      $filePathAndName = $homeDir . '/ext/mafiascum/votecounter_extension/helper/' . "words.txt";
      $handle = fopen($filePathAndName, "r");

      if ($handle) {
          while (($line = fgets($handle)) !== false) {
              // process the line read.
              $line = trim($line);
              if (strlen($line) > 1)
              {
                array_push($words, $line);
              }
          }

          fclose($handle);
          return $words;
      } else {
          // error opening the file.
          return array(static_functions::FILE_DOES_NOT_EXIST . ' ' . $filePathAndName);
      }

    }

    public static function get_words_in_string($dictionary,$string)
    {

      $wordsInString = array();

      foreach($dictionary as $word)
      {
        if (stripos($string, $word) !== false) {

          array_push($wordsInString,$word);
        }
      }



        return $wordsInString;
    }

    public static function is_valid_user($db, $username)
    {
      //TODO write sql query here after putting in users.
      return true;
    }

    public static function string_is_clean($string)
    {
      return !preg_match('/[^A-Za-z0-9_.# \\-$]/', $string);
    }

    public static function string_is_clean_or_comma($string)
    {
      return !preg_match('/[^A-Za-z0-9_.,# \\-$]/', $string);
    }

    public static function display_bool_value($boolValue)
    {
       //var_dump($boolValue);
       if ($boolValue == false)
       {
          return 'false';
        }
        else if ($boolValue == true)
         {
          return 'true';
        }
        else {
          return '???' . $boolValue . '???';
        }
    }

}
