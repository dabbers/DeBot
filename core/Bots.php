<?php

/**
 * DeBot Core - Bots
 * Created by dab ??? ?? 2009
 * Last Edited: Jul 29 2010
 *
 * This is our Bot handler. It maintains all of our Bots' variables, and
 * the other small things. Not actually too complex.
 *
 * Jul 29 2010 - Added support for syncronizing the Module Memory copies.
 *
 * @author David (dab) <dabitp@gmail.com>
 * @version v1.0
*/


class Bots extends Singleton
{
	/**
	 * This contains all of our bots. private so no one can mess with them :(
	 * @var Bot[] The bot classes in an array
	 */
	private $m_oBots = array( );

	/**
	 * Not quite sure why this is here, but it COULD be useful.
	 * @var boolean has this bot loaded?
	 */
	private static $m_bLoaded = false;

	/**
	 * Tells the bots which one should be taking commands and which should be the
	 * children.
	 *
	 * @var string the nick of the bot
	 */
	private static $m_sDefBot;


	/**
	 * This function loads all of the bots from the Config.
	 * the addBot makes further calls
	 */
	public function load( )
	{
		// We want to loop through all of our bots and load them.
		foreach( Config::obj( )->getBots( ) as $sNick => $aInfo )
		{
			$this->addBot( $sNick, $aInfo );
		}

		self::$m_bLoaded = true;
	}

	/**
	 * This adds a new bot to the Bots class. It needs to be added
	 * this way so we can loop through them as it can be checked.
	 *
	 * @param string $sNick The nickanme of the bot to be added. Will be fetched from config, if not existant, pulls defBot
	 * @return Boolean
	 */
	public function addBot( $sNick )
	{
		$this->m_oBots[ $sNick ] = new Bot( $sNick );
		Logs::obj( )->addDeBug( $sNick . ' has been created' );
		return true;
	}

	/**
	 * This does the opposite of the addBot. Removes the bot from being
	 * checked and disconnects the bot from all servers.
	 * @param String $sNick The name of the bot (not current name, the name used to create the bot)
	 * @return Boolean
	 */
	public function delBot( $sNick )
	{
		if ( ! isset( $this->m_oBots[ $sNick ] ) )
			return false;

		unset( $this->m_oBots[ $sNick ] );
		Logs::obj( )->addDeBug( $sNick . ' has been destroyed' );

		return true;
	}

	/**
	 *	Fetches the object for the bot so we can use its methods
	 *
	 *	@param string $sNick The nickname of the bot
	 */
	public function getBot( $sNick )
	{
		// Check that we have loaded the bots first
		if ( ! self::$m_bLoaded )
			return -1;

		// Make sure the bot exist, then return it.
		if ( isset( $this->m_oBots[ $sNick ] ) )
			return $this->m_oBots[ $sNick ];

		// No bot exists, return.
		return false;
	}

	/**
	 * This is called in our main loop. This performs the checks on each
	 * bot.
	 *
	 */
	public function check( )
	{
		foreach( $this->m_oBots as $sNick => $oBot )
		{
			$oBot->check( );
		}
	}

	/**
	 * This returns the default bot.
	 *
	 * @return string the default bot
	 */
	public static function getDef( )
	{
		return self::$m_sDefBot;
	}

	/**
	 * This sets the default bot.
	 *
	 * @param string $sKey The bot to set as default
	 * @return boolean|string New nick on success,  false on failure
	 */
	public static function setDef( $sBotNick )
	{
		$b = Bots::obj( )->getBot( $sBotNick );

		if ( $b !== false )
		{
			return self::$m_sDefBot = $sBotNick;
		}

		return false;
	}

	/**
	 * Returns the bots that are created.
	 *
	 * @return Bot[] The array of bots created in the class
	 */
	public final function Bots( )
	{
		return $this->m_oBots;
	}

}

?>
