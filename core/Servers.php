<?php

/**
 * DeBot Core - Servers
 * Created by dab ??? ?? 2009
 * Last Edited: Aug 15 2010
 * Last Edited: Apr 20 2013
 *
 * This manages all of our known networks.  I think a future need is to store
 * newly added networks to a custom config. So you don't have to go and add
 * the network to the bot again. Though that sort of disposes the use of the
 * config. I'll make the propsition on the Codebase and see how others respond.
 *
 * @author David (dab) <dabitp@gmail.com>
 * @author Weidi Zhang <weidiz999@yahoo.com>
 * @version v1.0
*/

class Servers extends Singleton implements ArrayAccess
{
	/**
	 * Stores all of the servers
	 *
	 * @var Server[] The servers in an array
	 */
	private $m_aServers = array ( );

	/**
	 * Stores the channels per network.
	 * @var array[ network ]Channel[] An array holding a channel per network key
	 */
	private $m_aServerChannels = array( );

	/**
	 * Load function parses config for the servers and stores them for future
	 * connections.
	 */
	public function load( )
	{
		foreach ( Config::obj( )->getServers( ) as $sServer => $aInfo )
		{
			$this->add( $sServer, $aInfo[ 'server' ], $aInfo[ 'port' ], $aInfo[ 'ssl' ] );

		} // End foreach of $aConfig

		return true;

	}

	/**
	 * Adds a server to the array list.
	 *
	 * @param string $sName The name of the server (for user/index)
	 * @param string $sServer The URL/IP of the server to connect to
	 * @param integer $iPort the Port to connect via
	 * @param boolean $bSSL Connect via SSL?
	 */
	public function add( $sName, $sServer, $iPort, $bSSL )
	{
		$this -> m_aServers[ $sName ] [ 'server' ] = $sServer;
		$this -> m_aServers[ $sName ] [ 'port' ] = $iPort;
		$this -> m_aServers[ $sName ] [ 'ssl' ] = $bSSL;

		$this -> m_aServerChannels[ $sName ] = array( );
		return true;
	}

	/**
	 * Called on a /names call return.
	 *
	 * @param string $sServer The server to which this applies to.
	 * @param string $sName The channel to where this is going
	 * @param $aNames The users in the channel
	 */
	public function onNames( $sServer, $sName, $aNames )
	{
		if ( ! isset( $this -> m_aServerChannels[ $sServer ][ $sName ] ) )
			$this -> m_aServerChannels[ $sServer ][ $sName ] = new Channel( $sName, $aNames );
		else
			$this -> m_aServerChannels[ $sServer ][ $sName ] -> onNames( $aNames );

	}
	
	/**
	 * Called on a kick 
	 *
	 * @param string $sServer The server to which this applies to.
	 * @param string $sName The channel to where this is going.
	 * @param string $sUser The argument of the kicked user.
	 */
	public function onKick($sServer, $sName, $sUser) {
		$sName = strtolower($sName);
		$sUser = trim($sUser);
		
		if (isset($this->m_aServerChannels[$sServer][$sName])) {
			$this->m_aServerChannels[$sServer][$sName]->removeNick($sUser);
		}
	}
	
	/**
	 * Called on a part
	 *
	 * @param string $sServer The server to which this applies to.
	 * @param string $sName The channel to where this is going.
	 * @param string $sUser The argument of the parting user.
	 */
	public function onPart($sServer, $sName, $sUser) {
		$sName = ltrim(strtolower($sName), ":");
		$sUser = trim($sUser);
		
		if (isset($this->m_aServerChannels[$sServer][$sName])) {
			$this->m_aServerChannels[$sServer][$sName]->removeNick($sUser);
		}
	}
	
	/**
	 * Called on a join
	 *
	 * @param string $sServer The server to which this applies to.
	 * @param string $sName The channel to where this is going.
	 * @param string $sUser The argument of the joining user.
	 */
	public function onJoin($sServer, $sName, $sUser) {
		$sName = ltrim(strtolower($sName), ":");
		$sUser = trim($sUser);
		
		if (isset($this->m_aServerChannels[$sServer][$sName])) {
			$this->m_aServerChannels[$sServer][$sName]->addNick($sUser);
		}
	}
	
	/**
	 * Called on a quit
	 *
	 * @param string $sServer The server to which this applies to.
	 * @param string $sUser The argument of the quitting user.
	 */
	public function onQuit($sServer, $sUser) {
		$sUser = trim($sUser);
		foreach ($this->m_aServerChannels[$sServer] as $sChan => $sData) {
			if (isset($this->m_aServerChannels[$sServer][$sChan])) {
				$this->m_aServerChannels[$sServer][$sChan]->removeNick($sUser);
			}
		}
	}
	
	/**
	 * Called on a user nick change
	 *
	 * @param string $sServer The server to which this applies to.
	 * @param string $sUser The argument of the user changing his/her nick.
	 * @param string $sNew The new nick of the sUser.
	 */
	public function onNickChange($sServer, $sUser, $sNew) {
		$sUser = trim($sUser);
		$sNew = ltrim(trim($sNew), ":");
		foreach ($this->m_aServerChannels[$sServer] as $sChan => $sData) {
			if (isset($this->m_aServerChannels[$sServer][$sChan])) {
				$this->m_aServerChannels[$sServer][$sChan]->changeNick($sUser, $sNew);
			}
		}
	}
	 

	/**
	 * Called onmode changes/listing
	 *
	 * @param string $sServer The server being called on
	 * @param string $sName The channel
	 * @param string $sMode the modes to add
	 * @param string $sParams the params to the mode
	 */
	public function onMode( $sServer, $sName, $sMode, $sParams )
	{
		if ( isset( $this -> m_aServerChannels[ $sServer ][ $sName ] ) )
			$this -> m_aServerChannels[ $sServer ][ $sName ] -> onMode( $sMode, $sParams );
	}

	/**
	 * When a topic is changed
	 *
	 * @param string $sServer The server this applies to
	 * @param string $sName the channel
	 * @param string $sTopic the Topic to set
	 */
	public function onTopic( $sServer, $sName, $sTopic )
	{
		if ( isset( $this -> m_aServerChannels[ $sServer ][ $sName ] ) )
			$this -> m_aServerChannels[ $sServer ][ $sName ] -> onTopic( $sTopic );
		else
			$this -> m_aServerChannels[ $sServer ][ $sName ] = new Channel( $sName, $sTopic );
	}

	/**
	 * Used before I realized what ArrayAccess was capable of.
	 *
	 * @deprecated since v0.01
	 * @param string $sName The server to fetch
	 * @return string The server.
	 */
	public function get( $sName )
	{

		if ( isset( $this -> m_aServers[ $sName ] ) )
			return $this -> m_aServers[ $sName ];

		return false;

	}

	public function offsetExists( $sKey )
	{
		switch( strtolower( $sKey ) )
		{
			case 'server':
			case 'servers':
			case 'serv':
			case 'chan':
			case 'channel':
			case 'channels':
				return true;

			default:
				return false;
		}
	}

	public function offsetGet( $sKey )
	{
		switch( strtolower( $sKey ) )
		{
			case 'serv':
			case 'servs':
			case 'server':
			case 'servers':
				return $this -> m_aServers;
			break;

			case 'chan':
			case 'chans':
			case 'channel':
			case 'channels':
				return $this -> m_aServerChannels;
			break;
			default:
				return ;
		}

	}

	public function offsetSet( $sKey, $mValue )
	{
		return ;
	}

	public function offsetUnset( $sKey )
	{
		return ;
	}
}

?>

