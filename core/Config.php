<?php
/**
 * DeBot Core - Config
 * Created by dab ??? ?? 2009
 * Last Edited: Aug 14 2010
 * Last Edited: Apr 20 2013
 *
 * Handles the config, and allows for a global access through singleton (Static).
 *
 * @author David (dab) <dabitp@gmail.com>
 * @version v1.0
*/

/**
 * Defines the version of the bot. Please don't change this
 * DO NOT CHANGE THIS!
 * @var String The string of the bot
 */
define( 'VERSION', '1.5.1' ); // Current Framework version

/**
 * Defines the string version of the bot. Please don't change this.
 * DO NOT CHANGE THIS!
 * @var String The string of the bot
 */
define( 'VERSION_STR', 'DeBot Framework v' . VERSION ); // A string version of the version.

/**
 * A shortcut of the ACTION command. Just to keep things simple.
 * Remember to end the the line in chr(1) to keep with standards
 * @var string chr(1). 'ACTION'
 */
define( 'ACTION', chr(1).'ACTION' ); // A shortcut to ACTION commands

class Config extends Singleton implements ArrayAccess
{

	/**
	 * The Config is stored here.
	 *
	 * @var array The config
	 */
	private $m_aConfig = array( );

	/**
	 * Load the config and the instance of the config handler
	 *
	 * @param array $aConfig the config array.
	 */
	public function load( $aConfig )
	{
		$this->m_aConfig = $aConfig;
	}

	/**
	 * Fetches the bot's config
	 *
	 * @param string $sBot The nick of the bot to fetch
	 * @return boolean|array False if bot doesn't exist, array of the bot config
	 */
	public function getBotConfig( $sBot )
	{
		if ( isset( $this->m_aConfig[ 'Bots' ][ $sBot ] ) )
			return $this->m_aConfig[ 'Bots' ][ $sBot ];
		else // if ( $sBot == 'Default' )
			return $this->m_aConfig[ 'Default' ];
		//else
		 //   return false;
	}

	/**
	 * A re-route of the getBotConfig
	 *
	 * @param string $sBot The nick of the bot to fetch
	 * @return boolean|array False if bot doesn't exist, array of the bot config
	 */
	public function getBot( $sBot )
	{
		return $this->getBotConfig( $sBot );
	}

	/**
	 * Fetch all of our servers.
	 *
	 * @return array An array of our servers
	 */
	public function getServers( )
	{
		return $this->m_aConfig[ 'Servers' ];
	}

	/**
	 * Get the config for all the bots
	 *
	 * @return array The array of all of our bots
	 */
	public function getBots( )
	{
		return $this->m_aConfig[ 'Bots' ];
	}

	/**
	 * Returns the whole config
	 *
	 * @return array The config created in /config.php
	 */
	public function getConfig( )
	{
		return $this->m_aConfig;
	}

	// ArrayAccess properties
	public function offsetGet( $sKey )
	{
		if ( ! isset( $this->m_aConfig[ $sKey ] ) )
			return ;
		return $this->m_aConfig[ $sKey ];
	}

	public function offsetSet( $sKey, $mValue )
	{
		if (isset( $this->m_aConfig[ $sKey ] ) )
		{
			$this->m_aConfig[ $sKey ] = $mValue;
			return true;
		}
		return false;
	}

	public function offsetExists( $sKey )
	{
		return ( isset( $this->m_aConfig[ $sKey ] ) );
	}

	public function offsetUnset( $sKey )
	{
		unset( $this->m_aConfig[ $sKey ] );
	}
}
?>
