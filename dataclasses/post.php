<?php

namespace mafiascum\votecounter_extension\dataclasses;

require_once(__DIR__ . '/../dataclasses/vote.php');
use mafiascum\votecounter_extension\dataclasses\Vote as Vote;

class Post {

  protected $id;
  protected $post_date;
  protected $post_text;
  protected $username;
  protected $vote;
  protected $matchedBoldArray = array();
  protected $matchedVoteTagArray = array();
  protected $matchedUnvoteTagArray = array();


  private $potentialVoteTexts = array();

  const NO_SETTINGS_ERROR = '[color=red]No settings found. Please add settings to the first post of the topic.[/color]';

  public function __construct($id, $post_date, $post_text,$username)
  {
    $this->id = $id;
    $this->post_date = $post_date;
    $this->post_text = $post_text;
    $this->username = $username;

    $this->build_vote();
  }
  private function build_vote()
  {



      $this->matchedBoldArray = $this->getUserNameBoldString();
      $this->matchedVoteTagArray = $this->getLatestStringForTag('vote', false);
      $this->matchedUnvoteTagArray = $this->getLatestStringForTag('unvote', true);

      $locOfCharWithString = -1;
      $match = array();

      $boldMatches = $this->getMatchesBold();
      if (count($boldMatches) > 0)
      {


        if ($boldMatches[1] > $locOfCharWithString)
        {
          $locOfCharWithString = $boldTagMatch[1];
          $match = $boldMatches;
        }


      }

      $voteTagMatches = $this->getVoteTagMatches();

      if (count($voteTagMatches) > 0)
      {


        if ($voteTagMatches[1] > $locOfCharWithString)
        {
          $locOfCharWithString = $voteTagMatches[1];
          $match = $voteTagMatches;
        }


      }


      $unvoteTagMatches = $this->getUnvoteTagMatches();

      if (count($unvoteTagMatches) > 0)
      {

        if ($unvoteTagMatches[1] > $locOfCharWithString)
        {
          $locOfCharWithString = $unvoteTagMatches[1];
          $match = $unvoteTagMatches;
        }
      }

      //This is intentional because the settings are in the first post. The first post cannot contain a valid vote.
      if ($match != null && $this->getId() > 0)
      {


        $vote = new Vote($this->getId(),$this->username,$match[0],$match[2],$this->post_date);
        $this->vote = $vote;
      }



  }


  public function getVoteCountSettingsString()
  {
      $matchArray = array();
      $matchArray = $this->getLatestStringForTags('spoiler=VoteCount Settings', 'spoiler',false);
      if (count($matchArray) >0)
      {
        return $matchArray[0];
      }
      else return post::NO_SETTINGS_ERROR;
  }

  private function getLatestStringForTag($tagName, $isUnvote)
  {
      return $this->getLatestStringForTags($tagName,$tagName,$isUnvote);
  }

  private function getLatestStringForTags($openTagName, $closedTagName, $isUnvote)
  {
    $matchArray = array();
    $matchedTagCount = array();

    preg_match_all('/\[' . $openTagName .  ']<\/s>(.*)<e>\[\/' . $closedTagName .']/s', $this->getText(), $matchedTagCount);



    foreach(array_reverse($matchedTagCount[1]) as $tagMatch)
    {



      if (count($tagMatch) > 0)
      {

        $tagElement = $tagMatch;
        $matchArray = array();
        array_push($matchArray,$tagElement);



        $position = strpos($this->getText(),'[' . $openTagName .']</s>' . $tagElement . '<e>[/'. $closedTagName . ']');

        array_push($matchArray,$position);
        array_push($matchArray,$isUnvote);

        return $matchArray;

      }
    }

  }

  private function getUserNameBoldString()
  {


    $voteColonElement='';
    $voteNoColonElement='';



    preg_match_all('/\[b]<\/s>(.*)<e>\[\/b]/', $this->getText(), $matchedBold);
    //$this->matchedBoldArray = $matchedBold;

    //$boldTextArray = $post->getMatchesBold();
    $boldMatchDebugString = '';

    $boldMatchedText;
    $hasColon;
    $isUnvote = false;


    foreach(array_reverse($matchedBold[1]) as $boldMatch)
    {
        $VoteColonElement ='';
        $UnvoteColonElement ='';
        $VoteNoColonElement ='';
        $UnvoteNoColonElement ='';
        $isUnvote = false;

        preg_match_all('/^VOTE:(.*)/i', $boldMatch, $hasVoteColon);
        if (count($hasVoteColon[1]) > 0)
        {
          $VoteColonElement = array_values(array_slice($hasVoteColon[1], -1))[0];
          $isUnvote = false;

        }


        preg_match_all('/^UNVOTE:(.*)/i', $boldMatch, $hasUnvoteColon);
        if (count($hasUnvoteColon[1]) > 0)
        {
          $UnvoteColonElement = array_values(array_slice($hasUnvoteColon[1], -1))[0];
          $isUnvote = true;

        }

        preg_match_all('/^VOTE(.*)/i', $boldMatch, $hasVoteNoColon);

        if (count($hasVoteNoColon[1]) > 0 && strlen($VoteColonElement) == 0)
        {
          $VoteNoColonElement = array_values(array_slice($hasVoteNoColon[1], -1))[0];
          $isUnvote = false;

        }

        preg_match_all('/^UNVOTE(.*)/i', $boldMatch, $hasUnvoteNoColon);
        if (count($hasUnvoteNoColon[1]) > 0)
        {
          $UnvoteNoColonElement = array_values(array_slice($hasUnvoteNoColon[1], -1))[0];
          $isUnvote = true;

        }


        if ($isUnvote == false)
        {

          if (strlen($VoteColonElement > 0) && !(strlen($VoteNoColonElement) > 0))
          {
              $hasColon = true;
              $boldMatchedText = $VoteColonElement;
          }
          else if(!strlen($VoteColonElement > 0) && (strlen($VoteNoColonElement) > 0))
          {
              $hasColon = false;
              $boldMatchedText = $VoteNoColonElement;
          }
          else if (strlen($VoteColonElement > 0) && (strlen($VoteNoColonElement) > 0))
          {
              $colonLoc = strpos($boldMatch, $VoteColonElement);
              $noColonLoc = strpos($boldMatch,$VoteNoColonElement);
              if ($colonLoc > $noColonLoc)
              {
                $hasColon = true;
                  $boldMatchedText = $VoteColonElement;
              }
              else {
                $hasColon = false;
                $boldMatchedText = $VoteNoColonElement;
              }
          }

        }
        else {

          if (strlen($UnvoteColonElement > 0) && !(strlen($UnvoteNoColonElement) > 0))
          {
              $hasColon = true;
              $boldMatchedText = $UnvoteColonElement;
          }
          else if(!strlen($UnvoteColonElement > 0) && (strlen($UnvoteNoColonElement) > 0))
          {
              $hasColon = false;
              $boldMatchedText = $UnvoteNoColonElement;
          }
          else if (strlen($UnvoteColonElement > 0) && (strlen($UnvoteNoColonElement) > 0))
          {
              $colonLoc = strpos($boldMatch, $UnvoteColonElement);
              $noColonLoc = strpos($boldMatch,$UnvoteNoColonElement);
              if ($colonLoc > $noColonLoc)
              {
                $hasColon = true;
                  $boldMatchedText = $UnvoteColonElement;
              }
              else {
                $hasColon = false;
                $boldMatchedText = $UnvoteNoColonElement;
              }
          }
        }


        if (strlen($boldMatchedText) > 0)
        {

          $boldMatchedText = trim($boldMatchedText);


          $matchArray = array();
          array_push($matchArray,$boldMatchedText);
          $voteString = 'VOTE' . ($hasColon ? ':' : '' . ' ' );
          //echo '[b]</s>' . $voteString . $boldMatchedText . '<e>[/b]';


          $position = strpos($this->getText(),'[b]</s>' . $voteString . $boldMatchedText . '<e>[/b]');

          array_push($matchArray,$position);
          array_push($matchArray,$isUnvote);

        }


    }

    return $matchArray;


  }

  private function getMatchesBold()
  {
    return $this->matchedBoldArray;
  }

  private function getVoteTagMatches()
  {
    return $this->matchedVoteTagArray;
  }

  private function getUnvoteTagMatches()
  {
    return $this->matchedUnvoteTagArray;
  }

  //Helper function its more readable for postnumber but Id makes more sense as forum doesn't seem to have numbers.
  public function getPostNumber()
  {
    return $this->getId();
  }

  public function getId()
  {
    return $this->id;
  }

  public function getDate()
  {
    return $this->post_date;
  }

  public function getText()
  {
    return $this->post_text;
  }

  public function getAuthor()
  {
    return $this->username;
  }
  public function getVote()
  {
    return $this->vote;
  }



}
