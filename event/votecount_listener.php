<?php

namespace mafiascum\votecounter_extension\event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
//$phpbb_root_path = '.';

//This is required because sometimes this gets called from root/adm and root and the PHPBB_ROOT_PATH for the board changes for both. Rather than modify how that Works
//I built a complicated if statement. Should only be required here as this is the only file that has to interact all over as it is a listener.
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
$pathToMainLogicFile = '/ext/mafiascum/votecounter_extension/logic/main_logic.';
$mainLogicPHPFile = $phpbb_root_path . $pathToMainLogicFile . $phpEx;
if (file_exists($mainLogicPHPFile))
{
  require_once($mainLogicPHPFile);
}
else {
  $phpbb_root_path = './..';
  $mainLogicPHPFile = $phpbb_root_path . $pathToMainLogicFile . $phpEx;
  if (file_exists($mainLogicPHPFile))
  {
    require_once($mainLogicPHPFile);
  }
  else {
    $phpbb_root_path = '.';
    $mainLogicPHPFile = $phpbb_root_path . $pathToMainLogicFile . $phpEx;
    if (file_exists($mainLogicPHPFile))
    {
      require_once($mainLogicPHPFile);
    }
  }
}

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
  //The cache -- Necessary to save dictionary to avoid rebuilding it repeatedly.
  protected $cache;


  public function __construct(\phpbb\config\config $config, \phpbb\language\language $language, \phpbb\request\request_interface $request,
                \phpbb\template\template $template, \phpbb\user $user,
                $phpbb_root_path, \phpbb\db\driver\driver_interface $db, $php_ext,\phpbb\cache\driver\driver_interface $cache)
  {
    $this->config = $config;
    $this->language = $language;
    $this->request = $request;
    $this->template = $template;
    $this->user = $user;
    $this->phpbb_root_path = $phpbb_root_path;
    $this->db = $db;
    $this->php_ext = $php_ext;
    $this->cache = $cache;
  }


  public static function getSubscribedEvents()
    {


        return array(

          'core.text_formatter_s9e_parse_before' => 'parse_before_votecount',
          'core.text_formatter_s9e_parse_after' => 'parse_after_votecount',
            'core.text_formatter_s9e_render_before' => 'render_before_votecount',
            'core.text_formatter_s9e_render_after' => 'render_after_votecount'
        );
    }




    public function parse_before_votecount($event)
    {


        $startText =  $event['text'];


        $finalString = $startText;
        $votecountOutput = \mafiascum\votecounter_extension\logic\MainLogic::get_votecount_string($this->cache,$this->phpbb_root_path,$this->db, $this->user, $this->request);
        $replacement = '[votecount]'. $votecountOutput . '[/votecount]';
        $finalString =  preg_replace('/\[votecount](.*)\[\/votecount]/', $replacement, $finalString);
        $replacement = '[votecountBBCode][code]'. $votecountOutput . '[/code][/votecountBBCode]';
        $finalString =  preg_replace('/\[votecountBBCode](.*)\[\/votecountBBCode]/', $replacement, $finalString);



        $event['text'] = $finalString;




    }

    public function parse_after_votecount($event)
    {


    }




    public function render_before_votecount($event)
    {


    }

    public function render_after_votecount($event)
    {




    }





}
