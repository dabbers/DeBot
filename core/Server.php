<?php

/**
 * DeBot Core - Server
 * Created by dab ??? ?? 2009
 * Last Edited: Aug 15 2010
 *
 * Our average socket is stored here along with some other things. We manage
 * the whole connection here so no one gets hurt and loses a socket!
 *
 * @author David (dab) <dabitp@gmail.com>
 * @version v1.0
*/
class Connection
{
	/**
	 * Is the connection established?
	 *
	 * @var boolean Connected or not connected. That is the question
	 */
	private $m_bConnected = false;

	/**
	 * The name of the connection.
	 *
	 * @var string The name of the connection (server)
	 */
	private $m_sName;

	/**
	 * The URL of the server
	 *
	 * @var string The Server's URL
	 */
	private $m_sServer;

	/**
	 * The port to connect to
	 *
	 * @var integer The port to establish the connection on.
	 */
	private $m_iPort;

	/**
	 * Should we use SSL?
	 *
	 * @var boolean Not every server suppors SSL so we can use/not use
	 */
	private $m_bSSL;

	/**
	 * The IP to bind on, if we have multiple IPs.
	 * @var string The IP to bind on.
	 */
	private $m_sBind;

	/**
	 * The socket itself.
	 *
	 * @var object The socket of our connection.
	 */
	private $m_oSock;

	/**
	 * The information of our bot (Ident, Nick, etc)
	 *
	 * @var array Ident=>value etc for all details
	 */
	private $m_aInfo;

	/**
	 * The message queue
	 *
	 * @var object SplPriorityQueue message queue
	 */
	private $m_oMessageQueue;

	/**
	 * The timer for message queues
	 * TODO: dab - how can I make this better? This seems wrong :/
	 *
	 * @var string iUID of the timer
	 */
	private $m_siUID;

	/**
	 * Last message tick (oh SO WRONG HELP ME PLEASE dab!)
	 */
	private $m_iMessageTick;

	/**
	 * When was the last message received?
	 */
	private $m_iLastMessage;

	/**
	 * Are we waiting for a response for the server?
	 */
	private $m_iTimeoutPing;

	/**
	 * Our construct. DOES NOT auto connect. You must call Server->Connect
	 *
	 * @param array $aInfo The info for the bot to use (ident, user, real name, nick )
	 * @param string The name of the bot iirc.
	 * @param string $sServer The URL/IP of the server to connect
	 * @param integer $iPort the port to connect upon.
	 * @param string $sBind Should we bind to an IP? For multiple IPs.
	 * @param boolean $bSSL Should we connect via SSL?
	 */
	public function __construct( $aInfo, $sName, $sServer, $iPort = 6667, $sBind = null, $bSSL = false )
	{

		$this->m_sName 		= $sName;
		$this->m_sServer 	= $sServer;
		$this->m_iPort 		= $iPort;
		$this->m_sBind 		= $sBind;
		$this->m_bSSL 		= $bSSL;

		$this->m_oMessageQueue	= new SplFIFOPriorityQueue( );
		$this->m_iMessageTick	= microtime( true );

		$this -> m_aInfo 	= $aInfo;


		$this -> m_bConnected 	= false;
	}

	/**
	 * Destruct method. Just make sure we properly disconnect before doing
	 * other destruct things handle in PHP's background.
	 */
	public function __destruct( )
	{
		$this->Disconnect( );
	}

	/**
	 * Connect to the server. Due to socket_ nature, I could not use it for
	 * creating SSL connections so a fsock_open and fwrite and such was required.
	 * so I had to change this file a bit to support SSL. Buuut it's sexy.
	 * Not only can you bind on an IP, but you can connect SSL. It's a sexy thing.
	 *
	 * Anyway I do a bunch of magic to make my connection socket easy to connect
	 * with. I combine stuff based on settings and such.
	 *
	 * @return boolean Connected or not?
	 */
	public function Connect( )
	{
		// If we're already connected, we shouldn't ruin a good thing.
		if ( $this -> m_bConnected == true )
			return false;


		// Do our timer dirty work
		$this -> clearMessages( );
		if ( ! Timers::obj( ) -> isTimer( $this -> m_siUID ) )
		{
			// Add the timer (every 100 ms this runs)
			Timers::obj( ) -> addTimer( '', .1, -1, array( $this, 'tickMessages') );
		}

		// We form our connect string, which shows if we use SSL or not.
		$sConnect = $this->m_bSSL ? 'ssl://' . $this -> m_sServer : $this->m_sServer;

		// If we are to bind to an IP, we will manage our socket settings.
		if ( $this->m_sBind != null )
		{

			// An array of options.
			$aOpts = array
			(
				'socket' => array
				(
					'bindto' => $this->m_sBind . ':0'
				),
			);

			$oContext = stream_context_create( $aOpts );

			$this->m_oSock = stream_socket_client
			(
				$sConnect . ':' . $this->m_iPort,
				$errno,
				$errstr,
				30,
				STREAM_CLIENT_CONNECT,
				$oContext
			);

		}
		else
		{
			// Not binding. This is simple. Just connect.
			$this->m_oSock = fsockopen( $sConnect, $this->m_iPort );
		}

		if ( ! $this->m_oSock )
		{
			// Oops our socket had a boo boo :( so now we will try connecting
			// in 20 seconds. <3 Timers.
			$sSockError = socket_strerror( socket_last_error( $this->m_oSock ) );
			Logs::obj()->addLog( 'Socket error #1 for ' . $this->m_sName . ': ' . $sSockError, true );
			Timers::obj()->addTimer( '', 20, 1, array( $this, 'Connect' ) );
			return false;
		}

		// We don't want to wait for data to come, especially if we have
		// other connections or bots. So just returning false on no data
		// will be great for us. Thanks.
		$res = stream_set_blocking( $this->m_oSock, 0 );

		if ( ! $res )
		{
			// Er.... we couldn't set no-blocking. So let's try this again.
			$sSockError = socket_strerror( socket_last_error( $this->m_oSock ) );
			Logs::obj()->addLog( 'Socket error #2 for ' . $this->m_sName . ': ' . $sSockError, true );
			Timers::obj()->addTimer( '', 20, 1, array( $this, 'Connect' ) );
			return false;
		}

		// Setup to send our client info. This tells the IRC Server who we are!
		$this -> m_bConnected = true;
		$sNick 	= $this->m_aInfo[ 'nick' ];
		$sIdent = $this->m_aInfo[ 'ident' ];
		$sReal 	= $this->m_aInfo[ 'real' ];

		$this->raw( 'USER ' . $sIdent . ' 8 * :' . $sReal );
		$this->raw( 'NICK ' . $sNick );

		return true;
	}

	/**
	 * Um, the server doesn't like us. This is called when that happens (timeout, gline, etc)
	 *
	 */
	public function Killed( )
	{
		$this->m_bConnected = false;
		fclose( $this->m_oSock );
	}

	/**
	 * We must disconnect from this server. The default message is the VERSION.
	 * I'd appreciate it if you left this as it is. It's like this for a reason.
	 * :(
	 *
	 * @param string $sQuitMsg The quit message to send upon disconnecting.
	 * @return boolean Work or not?
	 */
	public function Disconnect( $sQuitMsg = VERSION_STR )
	{
		if ( ! $this->m_bConnected )
			return false;

		$this->raw( 'QUIT ' . $sQuitMsg );
		fclose( $this->m_oSock );
		$this->m_oSock = @ socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
		$this->m_bConnected = false;
	}

	/**
	 * This performs the check for new info. As said in Bot.php, IRC has a tendancy
	 * to group lines, so we split them up and send as individual.
	 *
	 * @return boolean Parse success?
	 */
	public function Check( )
	{

		$sRead = trim( @ fread( $this->m_oSock, 65000 ) );
		if ( $sRead === false )
		{
			$sSockError = socket_strerror( socket_last_error( $this->m_oSock ) );
			Logs::obj()->addLog( 'Socket error #3 for ' . $this->m_sName . ': ' . $sSockError, true );
			Timers::obj()->addTimer( '', 20, 1, array( $this, 'Connect' ) );
			return false;
		}

		if ( strlen( $sRead ) == 0 )
		{
	                // As part of checking for a timeout, check to see if we've received any data
	                // from the server in the last 60 seconds.
	                if ( ! empty( $this -> m_iTimeoutPing ) && ( $this -> m_iTimeoutPing < time() - 5 ) )
	                {
	                        // Looks like we *are* disconnected :<
	                        Logs::obj()->addLog( 'Socket timeout for ' . $this->m_sName, true );
	                        Timers::obj()->addTimer( '', 20, 1, array( $this, 'Connect' ) );
	                        return false;
	                }
	                else
	                {
	                        if ( $this -> m_iLastMessage < time() - 60 )
	                        {
	                                // Send a ping to see if we're still connected
					//$this -> raw( 'PING ' . sever_name?); // Fill in Server Name
					$this -> m_iTimeoutPing = time();
	                        }
	                }
			return false;
		}
		$this -> m_iTimeoutPing = false;
		$this -> m_iLastMessage = time();

		$aLines = explode( "\n", $sRead );

		foreach( $aLines as $sLine )
		{
			$sLine = trim( $sLine );
			if ( strlen( $sLine ) == 0 )
				continue;
			
			$oBot = Bots :: obj( )->getBot( $this -> m_aInfo[ 'nick' ] );

			if ( ! method_exists( $oBot, 'process' ) )
				return false;
			$oBot -> bufferIn->Network = $this->m_sName;
			$oBot -> process( $sLine );
		}

	}

	/**
	 * Uses the new Priority Message Queue to send messages to the server *without* being instant.
	 * This prevents flooding the server and getting us killed.
	 * TODO: Modify / hack the timers to be able to trigger in .1 second increments or better.
	 * This is of course a hack because we can't do fully asyncronous event driven PHP.
	 *
	 * @param string $msg The message to send to the server
	 *
	 * @return void There's no way to find out what we actually can send like this.
	 */
	public function message( $msg, $priority = 100 ) {

		$this -> m_oMessageQueue -> insert( $msg, $priority );
	}

	public function tickMessages ( ) {
		while ( $this -> m_iMessageTick <= ( microtime( true ) - .5 ) && $this -> m_oMessageQueue -> valid( ) )
		{
			$this -> raw( $this -> m_oMessageQueue -> extract( ) );
			$this -> m_iMessageTick = microtime( true );
		}
	}

	/**
	 * Clear the message queue
	 *
	 * @return void
	 */
	public function clearMessages( )
	{
		while ( $this -> m_oMessageQueue -> valid( ) )
			$this -> m_oMessageQueue -> extract( );
	}

	/**
	 * As I knew would happen, we don't want to do fwrite() everywhere. If we
	 * wanted to change the socket type, or even changed from sockets to say,
	 * MySQL, we would have to update the script EVERYWHERE. This allows us to
	 * only edit this 1 method, and the whole bot uses this new setup. Very
	 * useful for say, making the bot Use fsocket instead of socket_.
	 *
	 * @param string $msg The message to send to the server
	 *
	 * @return boolean Sent?
	 */
	public function raw( $msg )
	{
		if ( ! $this -> m_bConnected )
			return false;

		$res = @ fwrite( $this -> m_oSock, $msg . "\r\n" );

		if ( ! $res )
		{
			$sSockError = socket_strerror( socket_last_error( $this->m_oSock ) );
			Logs::obj()->addLog( 'Socket error #4 for ' . $this->m_sName . ': ' . $sSockError, true );
			Timers::obj()->addTimer( '', 20, 1, array( $this, 'Connect' ) );
			return false;
		}
		return true;
	}

	/**
	 * A shortcut method to sending a privmsg to the server through Raw
	 *
	 * @param string $sTo The location to send the message to.
	 * @param string $sMsg The message to send
	 */
	public function msg( $sTo, $sMsg )
	{
		$this -> message( 'PRIVMSG ' . $sTo . ' :' . $sMsg );
	}

	/**
	 * A shortcut method to sending a NOTICE to the server through Raw
	 *
	 * @param string $sTo The location to send the message to.
	 * @param string $sMsg The message to send
	 */
	public function notice( $sTo, $sMsg )
	{
		$this -> message( 'NOTICE ' . $sTo . ' :' . $sMsg );
	}

	/**
	 * A shortcut method to sending a mode change to the server through Raw
	 *
	 * @param string $sWhere The location to send the mode to.
	 * @param string $sMode The modes to send
	 * @param string $sParam The params to send
	 */
	public function mode( $sWhere, $sMode, $sParam )
	{
		$this -> mesage ( 'MODE ' . $sWhere . ' ' . $sMode . ' ' . $sMsg );
	}

	public function __toString( )
	{
		return 'Connection class';
	}

	public function getSock( )
	{
		return $this -> m_oSock;
	}

}

?>
