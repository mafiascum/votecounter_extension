<?php

namespace MathBlade\votecount\event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
$phpbb_root_path = '.';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
require_once($phpbb_root_path . '/vendor/s9e/text-formatter/src/Bundles/Forum.' . $phpEx);
require_once($phpbb_root_path . '/ext/MathBlade/votecount/logic/main_logic.' . $phpEx);

use s9e\TextFormatter\Bundles;

class votecount_listener implements EventSubscriberInterface
{
  const VOTE_COUNT_LOCATION_STRING = 'PUT_VOTECOUNT_DATA_HERE';
  const VOTE_COUNT_NO_DEBUG_LOCATION_STRING = 'PUT_PARSED_VOTECOUNT_DATA_HERE';

  /** @var \phpbb\config\config $config Config object */
	protected $config;

	/** @var \phpbb\request\request_interface $request Request interface */
	protected $request;

	/** @var \phpbb\template\template $template Template object */
	protected $template;

	/** @var \phpbb\language\language $language Language object */
	protected $language;

	/** @var \phpbb\user $user User object */
	protected $user;

	/** @var string $phpbb_root_path phpBB root path */
	protected $phpbb_root_path;

	/** @var string $php_ext PHP file extension */
	protected $php_ext;
  /* @param \phpbb\db\driver\driver_interface*/
  protected $db;


  public function __construct(\phpbb\config\config $config, \phpbb\language\language $language, \phpbb\request\request_interface $request,
                \phpbb\template\template $template, \phpbb\user $user,
                $phpbb_root_path, \phpbb\db\driver\driver_interface $db, $php_ext)
  {
    $this->config = $config;
    $this->language = $language;
    $this->request = $request;
    $this->template = $template;
    $this->user = $user;
    $this->phpbb_root_path = $phpbb_root_path;
    $this->db = $db;
    $this->php_ext = $php_ext;
  }


  public static function getSubscribedEvents()
    {
      

        return array(

            'core.text_formatter_s9e_render_before' => 'render_before_votecount',
            'core.text_formatter_s9e_render_after' => 'render_after_votecount'
        );
    }





    public function render_before_votecount($event)
    {

      $xmlOld = $event['xml'];



      $hasVoteCountNoDebug = strpos($xmlOld, '[votecount]') > 0 ;
      $hasVoteCountDebug = strpos($xmlOld, '[votecountBBCode]') > 0;



      $finalString = $xmlOld;
      if ($hasVoteCountNoDebug)
      {
          $replacement = '[votecount]</s>'. votecount_listener::VOTE_COUNT_NO_DEBUG_LOCATION_STRING . '<e>[/votecount]';
          $finalString =  preg_replace('/\[votecount]\<\/s>(.*)<e>\[\/votecount]/', $replacement, $finalString) ;
      }

      if ($hasVoteCountDebug)
      {
          $replacement = '[votecountBBCode]</s>'. votecount_listener::VOTE_COUNT_LOCATION_STRING . '<e>[/votecountBBCode]';

          $finalString =  preg_replace('/\[votecountBBCode]\<\/s>(.*)<e>\[\/votecountBBCode]/', $replacement, $finalString) ;
      }




      $event['xml'] = $finalString;



    }

    public function render_after_votecount($event)
    {

      $htmlOld = $event['html'];


      $hasVoteCountNoDebug = strpos($htmlOld, '<b>Votecount:</b>') > 0 ;
      $hasVoteCountDebug = strpos($htmlOld, '<b>Votecount BBCode:</b>') > 0;



      if ($hasVoteCountDebug || $hasVoteCountNoDebug)
      {
        


        $votecountOutput = \MathBlade\votecount\logic\MainLogic::get_votecount_string($this->db, $this->user, $this->request);
        $finalString = $htmlOld;
        if ($hasVoteCountDebug)
        {
          $replacement = $votecountOutput;
          $finalString =  preg_replace('/' . votecount_listener::VOTE_COUNT_LOCATION_STRING . '/', $replacement, $finalString) ;
        }
        if($hasVoteCountNoDebug)
        {
          $replacement = $this->parse_string($votecountOutput);
          $finalString =  preg_replace('/' . votecount_listener::VOTE_COUNT_NO_DEBUG_LOCATION_STRING . '/', $replacement, $finalString) ;
        }





        $event['html'] = $finalString;

      }





    }


    /*private function get_string_between($string, $start, $end){
    	$string = " ".$string;
    	$ini = strpos($string,$start);
    	if ($ini == 0) return "";
    	$ini += strlen($start);
    	$len = strpos($string,$end,$ini) - $ini;
    	return substr($string,$ini,$len);
    }*/

    private function parse_string($stringToParse)
    {

        //$string = \s9e\TextFormatter\Bundles\Forum::parse($stringToParse);
        //$string = \s9e\TextFormatter\Bundles\Forum::render($string);


        return \s9e\TextFormatter\Bundles\Forum::render(\s9e\TextFormatter\Bundles\Forum::parse($stringToParse));
        //return $stringToParse . "PARSED";
    }

}
