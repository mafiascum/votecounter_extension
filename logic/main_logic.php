<?php

namespace mafiascum\votecounter_extension\logic;

if (!defined('IN_PHPBB'))
{
	define('IN_PHPBB', true);
}

require_once(__DIR__ . '/../dataclasses/post.php');
require_once(__DIR__ . '/../dataclasses/votecountSettings.php');
require_once(__DIR__ . '/../dataclasses/day.php');
require_once(__DIR__ . '/../dataclasses/votecount.php');
require_once(__DIR__ . '/../dataclasses/wagon.php');
require_once(__DIR__ . '/../helper/static_functions.php');
use mafiascum\votecounter_extension\dataclasses\Post as Post;
use mafiascum\votecounter_extension\dataclasses\votecountSettings as votecountSettings;
use mafiascum\votecounter_extension\helper\static_functions as static_functions;
use mafiascum\votecounter_extension\dataclasses\Day as Day;
use mafiascum\votecounter_extension\dataclasses\VoteCount as VoteCount;
use mafiascum\votecounter_extension\dataclasses\Wagon as Wagon;

class MainLogic
{

	static function add_default_groups()
	{
  		global $db;
  		$default_groups = array(
    		'GUESTS'      => array('', 0, 0),
    		'REGISTERED'    => array('', 0, 0),
    		'REGISTERED_COPPA' => array('', 0, 0),
    		'GLOBAL_MODERATORS' => array('00AA00', 2, 0),
    		'ADMINISTRATORS'  => array('AA0000', 1, 1),
    		'BOTS'       => array('9E8DA7', 0, 0),
    		'NEWLY_REGISTERED'   => array('', 0, 0),
  		);
  		$sql = 'SELECT *
    	FROM ' . GROUPS_TABLE . '
    	WHERE ' . $db->sql_in_set('group_name', array_keys($default_groups));
  		$result = $db->sql_query($sql);
  		while ($row = $db->sql_fetchrow($result))
  		{
   		 unset($default_groups[strtoupper($row['group_name'])]);
  		}
  		$db->sql_freeresult($result);
  		$sql_ary = array();
  		foreach ($default_groups as $name => $data)
  		{
    		$sql_ary[] = array(
      		'group_name'      => (string) $name,
      		'group_desc'      => '',
      		'group_desc_uid'    => '',
      		'group_desc_bitfield'  => '',
      		'group_type'      => GROUP_SPECIAL,
      		'group_colour'     => (string) $data[0],
      		'group_legend'     => (int) $data[1],
      		'group_founder_manage' => (int) $data[2],
    		);
  		}
  		if (count($sql_ary))
  		{
    		$db->sql_multi_insert(GROUPS_TABLE, $sql_ary);
  		}
	}

	//Copied from function convert for now.
	static function get_group_id($group_name)
	{
    	global $db, $group_mapping;

    	if (empty($group_mapping))
    	{
        	$sql = 'SELECT group_name, group_id
            	FROM ' . GROUPS_TABLE;
        	$result = $db->sql_query($sql);

        	$group_mapping = array();
        	while ($row = $db->sql_fetchrow($result))
        	{
            	$group_mapping[strtoupper($row['group_name'])] = (int) $row['group_id'];
        	}
        	$db->sql_freeresult($result);
    	}

    	if (!count($group_mapping))
    	{
        	MainLogic::add_default_groups();
        	return MainLogic::get_group_id($group_name);
    	}

    	if (isset($group_mapping[strtoupper($group_name)]))
    	{
        	return $group_mapping[strtoupper($group_name)];
    	}

    	return $group_mapping['REGISTERED'];
	}

	static function get_by_group_name($group_name)
	{
		global $db;

		$sql = 'SELECT user_id FROM ' . USER_GROUP_TABLE . ' WHERE group_id = ' . MainLogic::get_group_id($group_name);
		$result = $db->sql_query($sql);

		$user_ids = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$user_ids[] = $row['user_id'];
		}
		$db->sql_freeresult($result);


		return $user_ids;
	}

	static function get_administrator_user_ids()
	{
			return MainLogic::get_by_group_name('administrators');
	}

	static function get_moderator_user_ids()
	{
		return MainLogic::get_by_group_name('global_moderators');

	}

	static function get_admin_and_moderator_user_ids()
	{
			$adminUsers = MainLogic::get_administrator_user_ids();
			$moderators = MainLogic::get_moderator_user_ids();
			return array_merge($adminUsers,$moderators);
	}

    public static function get_votecount_string($cache,$homeDir,$db, $user, $request)
    {

        $posts = MainLogic::get_posts($db, $user, $request);
        $lastPostNumber = 0;
        $votes = array();
        if (count($posts) == 0)
        {
          return "Can't perform votecount on a non-existent thread.";
        }
        foreach($posts as $post)
        {

          if ($post->getId() == 0)
          {

              $settingString = $post->getVoteCountSettingsString();

              if ($settingString == Post::NO_SETTINGS_ERROR)
              {
                return Post::NO_SETTINGS_ERROR;
              }
              else {
                $settings = new votecountSettings($cache,$homeDir,$db,$settingString);
                $settingsErrorArray = $settings->getErrorArray();

                if (count($settingsErrorArray) > 0)
                {
                   $errorString = "\r\n";
                   foreach($settingsErrorArray as $error)
                   {
                      $errorString .=   $error . '' . "\r\n";
                   }

                   $errorString = "The following votecount errors in the settings must be resolved before a vote count can occur: " . $errorString;
                   return $errorString;
                }
              }

          }
          else
          {
            if ($post->getVote() != null)
            {
                array_push($votes,$post->getVote());

            }
          }

          $postNumberOfPost = $post->getPostNumber;



        }

        $lastPostNumber = $posts[count($posts) -1]->getPostNumber();

        $players = $settings->getPlayers();
        $replacements = $settings->getReplacements();
        $moderatorList = $settings->getModeratorList();

        $playersInGame = array();
        foreach($players as $settingPlayer)
    		{
    			array_push($playersInGame,$settingPlayer->getExactName());
    		}
    		foreach($replacements as $settingReplacement)
    		{
    			$newPlayerName = $settingReplacement->getNewPlayer()->getExactName();
    			if (!in_array($newPlayerName,$playersInGame))
    			{
    				array_push($playersInGame,$newPlayerName);
    			}
    		}
        foreach($moderatorList as $moderator)
        {
          $newPlayerName = $moderator;
          if (!in_array($newPlayerName,$playersInGame))
          {
            array_push($playersInGame,$newPlayerName);
          }
        }

        $username = $user->data['username'];

        if (!in_array($username,$playersInGame))
        {
            $userId = $user->data['user_id'];

            if (!in_array($userId,MainLogic::get_admin_and_moderator_user_ids()))
            {
                return "[color=red]You are not a valid player in this game and therefore cannot request a votecount. If you are and see this in error, please get the game mod.[/color]";
            }
        }

        //This is a separate loop in order to be able to handle replacements.
        foreach($posts as $post)
        {
            $author = $post->getAuthor();
            $postNumber = $post->getPostNumber();
            $skipThisPost = false;
            foreach($settings->getModeratorList() as $moderatorName)
            {

                if (strcmp($author,$moderatorName) === 0)
                {
                  if (($key = array_search($post, $posts)) !== false) {
                      unset($posts[$key]);
                  }

                  $skipThisPost = true;
                  break;
                }
            }
            if ($skipThisPost)
            {
              continue;
            }

            $directPlayer = static_functions::get_player_exact_reference($players,$author);
            if ($directPlayer != null)
            {
                $directPlayer->addPostNumber($postNumber);
                $directPlayer->setTimeOfLastPost($post->getDate());

            }
            else {
							//Get player by postNumber
              $playerReplacementsLoop = static_functions::get_player_reference_from_vote($players,$replacements,$author,$postNumber);
							if ($playerReplacementsLoop != null)
							{
								$playerReplacementsLoop->addPostNumber($postNumber);
                $playerReplacementsLoop->setTimeOfLastPost($post->getDate());
							}
            }
        }

        $dayStartNumbers = $settings->getDayStartNumbers();
        $deadList = $settings->getDeadList();
        $resurrectedList = $settings->getRessurectedList();
        $deadline = $settings->getDeadline();
        $color = $settings->getColor();
        $fontOverride = $settings->getFontOverride();
        $prodTimer = $settings->getProdTimer();
        $dayviggedList = $settings->getDayviggedList();
        $modkilledList = $settings->getModkilledList();
        $isLyloOrMyloArray = $settings->getLyloOrMyloArray();
        $playerModifierArray = $settings->getPlayerModifierArray();


        //Build playerModifiers
        foreach($playerModifierArray as $playerModifierEntry)
        {

              $player = $playerModifierEntry[0];
              $modifierName = $playerModifierEntry[1];
              $modifierPostNumber = $playerModifierEntry[2];
              $modifierValue = $playerModifierEntry[3];
              //echo "ADDING PLAYERNAME: " . $player->getName() . " MODIFIER VALUE: " . $modifierName . " MODIFIER VALUE: " . $modifierValue . " POST NUMBER: " . $postNumber . "<br/>";
              $error = $player->addModifier($modifierName,$modifierValue === 'true',$modifierPostNumber);
              if ($error != null)
              {
                return $error;
              }


        }


        $dayNumber = 1;
        $days = array();
        $index = 0;
        foreach($dayStartNumbers as $dayStartNumber)
        {
            if (($index + 1) < count($dayStartNumbers))
            {
                $dayEndsOn = $dayStartNumbers[$index + 1];
            }
            else {
                $dayEndsOn = $lastPostNumber;
            }

            if ($dayStartNumber <= $lastPostNumber)
            {

                array_push($days, new Day($dayNumber,$dayStartNumber,$dayEndsOn));
                $dayNumber = $dayNumber + 1;
            }
        }

        $votecounts = VoteCount::build_all_vote_counts($lastPostNumber,$votes,$players,$replacements,$moderatorList,$days,$isLyloOrMyloArray,$deadList,$resurrectedList,$deadline,$color,$fontOverride,$prodTimer,$dayviggedList,$modkilledList);
        //If this happened there was an error. $votecounts should be just an array with 1 element. Which is an array of the votecounts.
        if (count($votecounts) > 1)
        {
             $errorArray = $votecounts[1];
             $errorString = "\r\n";
             foreach($errorArray as $error)
             {
                $errorString .=   $error[1] . '' . "\r\n";
             }

             $errorString = "The following errors must be resolved before a vote count can occur: " . $errorString;
             return $errorString;




        }
        else if (count($votecounts) == 1)
        {
          $voteCountArray = $votecounts[0];
          /// Build final string.
          $voteErrorArray = array();
          $voteErrorString = "\r\n";
          $aVoteErrorExists = false;
          foreach($voteCountArray as $votecount)
          {

            if ($votecount->getErrors() != null)
            {
              if (count($votecount->getErrors()) > 0)
              {
                $aVoteErrorExists = true;
                foreach($votecount->getErrors() as $voteError)
                {
                   $voteErrorString .=   $voteError . '' . "\r\n";
                }

              }
            }

          }
          if (!$aVoteErrorExists)
          {
            $voteErrorString='';
          }
          //User is the user logged in.
          return MainLogic::build_final_string($user,$days,$voteCountArray[count($voteCountArray) -1],$fontOverride,$color,$deadline,$prodTimer,$voteErrorString);
        }
        else {
          $errorString = "Could not build votecount. Try again later or check data settings.";
          return $errorString;
        }


      return "Try your request again in a few moments. ";
    }

    public static function build_final_string($user,$days,$lastVotecount,$fontOverride,$color,$deadline,$prodTimer,$compiledVoteErrorString)
    {
      $newLine="\r\n";
      $dayNumber = $lastVotecount->getDayNumber();
      $day = $days[$dayNumber -1];

      $firstPostOfDay = $day->getFirstPostOfDay();
      if (count($days) > $dayNumber)
      {
        $lastPostOfDay = $days[$dayNumber]->getFirstPostOfDay() -1;
      }
      else {
        //There is no last post in this case. So just a super high number that should never be hit.
        $lastPostOfDay = 99999999999;
      }
      $finalString = $newLine;


      if ($fontOverride != null)
      {
          $finalString = $finalString . "[font=" . $fontOverride . "]";
      }
      $finalString = $finalString . "[area=Results]". $newLine . "[b][u][size=150]";
      if ($color != null)
      {
          $finalString = $finalString . "[color=" . $color . "]";
      }

      $finalString = $finalString . "VoteCount " . $dayNumber . "." . ($priorVoteCount + 1);

      if ($color != null)
      {
          $finalString = $finalString. "[/color]";
      }

      $finalString = $finalString . "[/size][/u][/b]" . $newLine;

      //Add wagon data here.
      $isFirstWagon = true;

      $sortedWagons = Wagon::sortWagons($lastVotecount->getWagons());

      $lastVotecount->setSortedWagons($sortedWagons);
      for($i=0;$i < count($sortedWagons); $i++)
      {
          $wagon = $sortedWagons[$i];

          if (count($wagon->getPlayersVoting()) > 0)
          {

              if ($isFirstWagon)
              {
                $finalString = $finalString . "[i]";

              }

              $finalString = $finalString . "[b]" . $wagon->getPlayerBeingVoted()->getLatestDisplayName() . " (" . count($wagon->getPlayersVoting()) . ")[/b] ~ " . MainLogic::getPlayerDelimitedList($wagon->getPlayersVoting(), $firstPostOfDay,$lastPostOfDay);

              if ($isFirstWagon)
              {
                $finalString = $finalString . "[/i]" . ($wagon->getIsHammered() ? " -- HAMMER" . $wagon->getHammerDisplayString($lastPostOfDay,$lastVotecount->getIsLyloOrMylo()) :

                " (" . "L - ". $wagon->getL_Level() . ")" .  '' . ($wagon->getL_Level() < 1 ? ' - LOVED' : ''))

                . $newLine;
              }
              else {
                $finalString = $finalString . "[/i]" .  " (" . "L - " . $wagon->getL_Level() . ")" . $newLine;
              }
              $isFirstWagon = false;
          }

      }


      $playersNotVoting = $lastVotecount->getPlayersNotVoting();
      $finalString = $finalString . "Not Voting: " . MainLogic::getPlayerDelimitedList($playersNotVoting, $firstPostOfDay, $lastPostOfDay) . $newLine;

      //End wagon data

      $finalString = $finalString . "With " . count($lastVotecount->getLivingPlayersAtStart()) . " alive it takes " . $lastVotecount->getMajorityCount() . " to lynch. " . $newLine;
      $finalString = $finalString . "Day " . $dayNumber . " deadline is in " . $deadline . ".". $newLine;

      $finalString = $finalString . "[/area]" . $newLine;

      $finalString = $finalString . "[area=Mod Reminders]";

      //Start mod reminders here.
      $prodString='';
      foreach($lastVotecount->getPlayersValidForVotecount() as $player)
      {
          if($player->isAlive())
          {
              $timeOfLastPost = $player->getTimeOfLastPost();

              if ($timeOfLastPost != null)
              {


                //$date = strtotime($timeOfLastPost);
                $dateAddedProdTime = $timeOfLastPost;
                $dateAddedProdTime = strtotime('+' . $prodTimer->getDays() . ' days',$dateAddedProdTime);
                $dateAddedProdTime = strtotime('+' . $prodTimer->getHours() . ' hours',$dateAddedProdTime);
                $dateAddedProdTime = strtotime('+' . $prodTimer->getMinutes() . ' minutes',$dateAddedProdTime);
                $dateAddedProdTime = strtotime('+' . $prodTimer->getSeconds() . ' seconds',$dateAddedProdTime);


                if ($dateAddedProdTime <= time())
                {
                  $prodString = $prodString . $player->getLatestDisplayName() . " Needs a Prod. Last Post Was At: " . $user->format_date($timeOfLastPost) . " Should have posted by: " . $user->format_date($dateAddedProdTime) . $newLine;
                }

              }
              else {
                $prodString = $prodString . $player->getLatestDisplayName() . " has never posted. Verify interest. " . $newLine;
              }

          }
      }
      $finalString = $finalString . $newLine;
      if (strcmp($prodString,'') === 0)
      {
          $finalString = $finalString . "NONE";
      }
      else {
          $finalString = $finalString . $prodString;
      }
      //End mod reminders.

      $finalString = $finalString . $newLine . "[/area]" . $newLine;



      if ($compiledVoteErrorString != '')
      {
          $finalString = $finalString . "[area=Vote Errors]" . $compiledVoteErrorString . "[/area]" . $newLine;
      }



      if ($fontOverride != null)
      {
          $finalString = $finalString. "[/font]";
      }


      return $finalString;

    }

    private static function getPlayerDelimitedList($players,$firstPostOfDay,$lastPostOfDay)
    {
        $playerStrings=array();
        foreach($players as $player)
        {
            if ($player->getPostNumberOfVote() != null)
            {
              array_push($playerStrings,"[post=" . $player->getPostNumberOfVote() . "]" . $player->getLatestDisplayName() . "[/post]" . "(" . $player->getNumberOfPostsInDay($firstPostOfDay, $lastPostOfDay) . ")");
            }
            else {
              array_push($playerStrings,$player->getLatestDisplayName() .  "(" . $player->getNumberOfPostsInDay($firstPostOfDay, $lastPostOfDay) . ")");
            }
        }

        return implode("," ,$playerStrings);
    }
    public static function get_posts($db,$user,$request)
    {
        $forum_id	= $request->variable('f', 0);
        $topic_id	= $request->variable('t', 0);

        $posts = array();
        $i = -1;

        $sql = 'SELECT  p.post_time, p.post_text, u.username
        from ' . POSTS_TABLE . ' p LEFT JOIN ( '
        . USERS_TABLE . ' u ) ON (' .
        'p.poster_id=u.user_id) where p.topic_id='. $topic_id .
        ' and p.forum_id=' . $forum_id .
        ' order by p.post_time asc;' ;



          $result = $db->sql_query($sql);
          //$post_data = $db->sql_fetchrow($result);

          if($result->num_rows > 0){
              while($r = $result->fetch_array()){
                  $i = $i + 1;
                  $post_date = $r[0];//$user->format_date($r[0]);
                  $post_text = $r[1];
                  $username = $r[2];
                  //$newPost = new \mafiascum\votecounter_extension\dataclasses\Post($i,$post_date,$post_text,$username);
                  $newPost = new Post($i,$post_date,$post_text,$username);
                  array_push($posts, $newPost);
              }
              $db->sql_freeresult($r);
          }
          else {
              $db->sql_freeresult($result);
          }

          return $posts;

    }



}
