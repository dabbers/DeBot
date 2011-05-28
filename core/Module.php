<?php
/**
 * DeBot Core - Module
 * Created by dab Jul ?? 2010
 * Last Edited: Aug 15 2010
 *
 * This outlines all of the currently possible callbacks available to the bot.
 *
 * @author David (dab) <dabitp@gmail.com>
 * @version v1.0
 */
abstract class Module
{

	/**
	 * The nickname of the bot. Should ALWAYS exist for getting the bot again
	 * @var string The nickname of the owner bot
	 */
	protected $m_sNick;

	/**
	 * The Bot variable
	 */
	protected $m_oBot;

	/**
	 * I forget the intention of this module. I think for when I introduce System
	 * modules in later versions.
	 *
	 * @var integer I believe 0 is for bots, and 1 is for system. Or MODTYPE_BOT or MODTYPE_SYS
	 */
	private $m_iType;

	/**
	 * Array of timers that have been assigned
	 */
	private $m_aTimers;


	/**
	 * With the improved module interface, we set a few class variables here instead of
	 * getting the class to do it itself.
	 */
	final public function __construct( $oBot )
	{
		$this -> m_oBot = $oBot;
		$this -> m_sNick = $oBot->m_sNick;


	}

	/**
	 * Do our destruction commands here
	 */
	final public function __destruct( )
	{
		// Remove all the assigned timers
		$count = 0;
		if ( ! empty ( $this -> m_aTimers ) && is_array ( $this -> m_aTimers ) )
		{
			foreach ( $this -> m_aTimers as $id )
			{
				if ( Timers :: obj( ) -> isTimer( $id) )
				{
					Timers :: obj( ) -> delTimer( $id);
					$count++;
				}
			}
		}
		//echo "Module had $count timers still loaded";
	}

	/**
	 * This is called whenever a module is loaded *for the first time*
	 */
	public function init( )
	{

	}

	/**
	 * This is called when a module is unloaded
	 */
	public function unload( )
	{

	}




	/**
	 * Add a timer (these are removed on object destruction)
	 *
         * @param float   $fSeconds  Seconds between ticks.
         * @param integer $iRepeat   Times to repeat. Use -1 for forever
         * @param object  $oFunction The object function array(class, 'function' )
         * @param array   $aParams   The params to send to teh function/method call
	 *
	 * @return string The ID of the Timer
	 */
	protected function addTimer( $fSeconds, $iRepeat, $oFunction, $aParams = null)
	{
		$id  = Timers :: obj( ) -> addTimer( $this -> m_sNick, $fSeconds, $iRepeat, $oFunction, $aParams);
		$this -> m_aTimers[ ] = $id;
		return $id;
	}

	/**
	 * Remove a timer
	 *
	 * @param string $ID Timer to delete
	 *
	 * @return void
	 */
	protected function delTimer( $id )
	{
		if ( isset( $this -> m_aTimers[ $id] ) )
			Timers :: obj( ) -> delTimer( $id);
	}


	/**
	 * This is called when we receive a raw command. Usually one we don't have
	 * a callback for.
	 *
	 * @param integer $iCode The code of the message
	 * @param string $sLine The line message
	 *
	 */
	public function onRaw( )
	{

	}

	/**
	 * This is called when a user changes his/her nickname
	 *
	 * @param string $sOld The old nickname (the person performing the change)
	 * @param string $sNew The new nickname (what to refer them as now)
	 */
	public function onNick( )
	{

	}

	/**
	 * Most obvious. Called when a channel message is received.
	 *
	 * @param string $sFrom who the message is from
	 * @param string $sTo Where the message was sent
	 * @param string $sMsg The message itself
	 * @param string $sSpecial Optional. Was the message sent in a special way? %#channel @#channel
	 */
	public function onMsg( )
	{

	}

	/**
	 * Called when someone sends a message directly to the bot
	 *
	 * @param string $sFrom the user sending the message
	 * @param string $sMsg the message sent
	 */
	public function onPrivMsg( )
	{

	}

	/**
	 * When a channel notice is received. Not many people know you can
	 * notice a channel. :P
	 *
	 * @param string $sFrom the person who sent the notice
	 * @param string $sTo the channel the notice is sent to
	 * @param string $sMsg The message sent
	 * @param string $sSpecial Optional. The message sent via ops/halfops only? %#chan, etc
	 */
	public function onNotice( )
	{

	}

	/**
	 * Private notice (/notice DeBot ohi! )
	 *
	 * @param string $sFrom The user sending the notice
	 * @param string $sMsg the message sent
	 */
	public function onPrivNotice( )
	{

	}

	/**
	 * When a mode is changed on the channel
	 *
	 * @param string $sWho The User who changed the mode
	 * @param string $sMode the modes altered
	 * @param string $sParam Optional. The Parameters (+b ie)
	 */
	public function onMode( )
	{

	}

	/**
	 * When a user joins the channel
	 *
	 * @param string $sWho The user who joined
	 * @param string $sChan The channel that the user joined
	 *
	 */
	public function onJoin( )
	{

	}

	/**
	 * When a user parts
	 *
	 * @param string $sWho The user who parted
	 * @param string $sChan The channel parted
	 * @param string $sWhy Optional. The reason for parting (Part message)
	 */
	public function onPart( )
	{

	}

	/**
	 * When a user Quits the IRC network
	 *
	 * @param string $sWho The user who quit
	 * @param string $sWhy The quit message
	 */
	public function onQuit( )
	{

	}

	/**
	 * When  the topic is changed
	 *
	 * @param string $sChan the channel where the topic is changed
	 * @param string $sTopic The topic changed to
	 */
	public function onTopic( )
	{

	}

	/**
	 * onWhois. Due to the nature of a whois, more than 1 ID can be
	 * sent, so we allow the parameter for it.
	 *
	 * @param string $sWho The person we are whois-ing
	 * @package string $sId the ID of the whois message
	 * @param string $sLine the line sent by the WHOIS
	 */
	public function onWhois( )
	{

	}

	/**
	 * Once the MOTD is done, the bot is declared connected. We can get that
	 * callback here. :D
	 *
	 */
	public function onConnect( )
	{

	}

	/**
	 * This is called when a user is kickd.
	 *
	 * @param string $sWho The person doing the kicking
	 * @param string $sChan The channel the user was kicked from
	 * @param string $sLeft The user who was kicked from teh channel
	 * @param string $sWhy The reason for being kicked
	 */
	public function onKick(  )
	{

	}

	public function __toString( )
	{
		return 'Module Class';
	}


}

?>
