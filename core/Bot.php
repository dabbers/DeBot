<?php

/**
 * DeBot Core - Bot
 * Created by dab ??? ?? 2009
 * Last Edited: Jul 29 2010
 * Last Edited: Aug 15 2010
 *
 * This file contains the structure for a SINGLE complete bot. It auto loads
 * everything from the config based on either the Default bot config or the
 * designated nick for this bot. It also processes every line sent to it here,
 * and designates the line to their appropiate callbacks on the modules.
 *
 *
 * @author David (dab) <dabitp@gmail.com>
 * @version v1.0
 */

class Bot implements ArrayAccess
{
	/** Stores the bot's nick
	 * @var String The bot's nick
	 *
	 */
	public $m_sNick;

	/** Stores the bot's ident
	 * @var String The bot's ident (Nick!Ident@host)
	 *
	 */
	private $m_sIdent;

	/** Stores the bot's real name
	 * @var String The bot's real name (appears in whois)
	 *
	 */
	private $m_sReal;

	/** Is this bot the default (main) bot?
	 * This is used in the RawEval and Cmds modules, to check that this bot
	 * should be executing these commands.
	 *
	 * @var Boolean True = Bot is main bot, false = bot is child
	 *
	 */
	private static $m_sDefBot = false;

	/** Stores the bot's connections to the servers
	 * @var Connection[] The Bot connections (Classes)
	 *
	 */
	private $m_aConnections;

	/** Stores the bot's modules
	 * @var Module[] The Bot module
	 *
	 */
	private $m_aModules;

	/** A temporary loaction for all Information for the bot
	 * @var stdclass An all around generic place holder for variables
	 *
	 */
	public $bufferIn;

	/** Messages to send to the server (Startup commands)
	 * @var stdclass[] nick
	 *
	 */
	public $bufferOut;

	/**
	 * A place to store possible channel labels
	 * +%@&~#
	 * @var array
	 */
	private $m_aChanLabels = array
	(
		'#',
		'%',
		'@',
		'+',
		'&',
		'~',
	);

	/**
	 * This function loads all settings from the config based on provided
	 * nick. If the nick is in the config, we pull from that, otherwise we
	 * get the default bot configuration and run with that.
	 *
	 * @param string $sNick The nick of the bot we are loading
	 */
	public function __construct( $sNick )
	{
		// Pull the config from our Config Class
		$aConfig = Config::obj()->getBot( $sNick );

		// Store data in our bot
		$this->m_sNick  = $sNick;
		$this->m_sIdent = $aConfig[ 'ident' ];
		$this->m_sReal  = $aConfig[ 'real' ];

		// Assign defBot if we are the default bot
		if ( $aConfig[ 'defBot' ] )
			Bots::obj()->setDef( $sNick );

		$this->bufferIn  = new stdClass;
		$this->bufferIn->src = new BufferInput( "This Line" );

		// Load our Per network OnConnect info
		foreach( $aConfig[ 'networks' ] as $sNetwork => $aConfigs )
		{
			foreach( $aConfigs[ 'onConnect' ] as $sLine )
			{
				$this->bufferOut[ $sNetwork ][] = $sLine;
			}
		}

		// Add to our bufferOut, the all around onConnect commands
		foreach ( $this->bufferOut as $sKey => $sVal )
		{
			foreach( $aConfig[ 'onConnect' ] as $sLine )
				$this->bufferOut[ $sKey ][] = $sLine;

			$this->bufferOut[ $sKey ][] = 'MODE ' . $sNick . ' +B';
		}

		// Get our list of servers object (For down below)
		$aServers = Servers::obj ( );

		// I believe this line is here for backwards compatibility from when
		// we had static Config array (no class)
		$aConfig[ 'nick' ] = $sNick;

		// Here, we get the networks and call a method to connect
		foreach( $aConfig[ 'networks' ] as $sName => $aOption )
		{
			$aS = $aServers->get( $sName );
			if ( ! $aS  )
				continue;
			$this->addServer( $sName, $aS[ 'server' ], $aS[ 'port' ], $aOption[ 'bind' ], $aS[ 'ssl' ] );
		} // End foreach of $config

		// Here we initiate our modules handler
		$this->m_aModules = new Modules( $this );

	} // End __construct

	/**
	 * This method adds a server to the bot (or reconnects if the connection
	 * already exists).
	 *
	 * @param String $sName The Name of the server (Used to store for future)
	 * @param String $sServer The IP/Host of the server to connect to
	 * @param Integer $iPort The Port to connect to
	 * @param String $sBind The IP to bind to (for use with multiple IPs)
	 * @param Boolean $bSSL If we should attempt to connect via SSL?
	 *
	 * @return Boolean True on connect, false on failure (an error log is generated)
	 */
	public function addServer( $sName, $sServer = null, $iPort = null, $sBind = null, $bSSL = false )
	{
		// This means we are expected to have this server... if we don't,
		// we can't connect
		if ( $sServer == null )
		{
			$aServers = Servers::obj( );
			$aS = $aServers->get( $sName );
			if ( ! $aS  )
				return false;
			$sServer = $aS[ 'server' ];
			$iPort = $aS[ 'port' ];
			$bSSL = $aS[ 'ssl' ];
		}

		// Create an array to pass into our Connection Construct
		$aInfo = array
		(
			'nick' => $this->m_sNick,
			'ident' => $this->m_sIdent,
			'real' => $this->m_sReal
		);

		// This initiates the connection creation
		$this->m_aConnections[ $sName ] = new Connection
		(
			$aInfo,
			$sName,
			$sServer,
			$iPort,
			$sBind,
			$bSSL
		);

		// Read it and weep
		$this->m_aConnections[ $sName ]->Connect( );

		return true;
	} // End addServer

	/**
	 * This function removes a server from a bot. This used to cause crashes but
	 * has since been debugged and resolved.
	 * @param string $sServer The name of the server to remove
	 * @return boolean True on delete, false on issues
	 */
	public function delServer( $sServer )
	{
		if ( ! isset( $this->m_aConnections[ $sServer ] ) )
		{
			return false;
		}

		unset( $this->m_aConnections[ $sServer ] );
	}

	/**
	 * I'm kind of proud of this command. I needed a way of sending raw messages
	 * without the command being too difficult. I decided with an auto network
	 * setting, when messages are received. It isn't perfect, but it works
	 * wonders for making things simple. Especially when you are only using 1
	 * network.
	 *
	 * Anyway, this command sends a raw line of data to the server.
	 * If you don't select a network to send to, it will send to the last server
	 * to send the bot data.
	 *
	 * @param string $sMsg The line of data to send (No \r\n needed).
	 * @param String $sNet Optional parameter to tell the bot what network to send to
	 *
	 * @return boolean True if sent, otherwise false if cannot send (no network, not connectd, etc).
	 */
	public function raw( $sMsg, $sNet = null )
	{
		// Check to see if we've chosen a network
		if ( $sNet == null )
			$sNet = $this->bufferIn->Network; // Assign the current network

		// We don't want to try to send to a non-existant network
		if ( ! isset( $this->m_aConnections[ $sNet ] ) )
			return false;

		// Send data
		return $this->m_aConnections[ $sNet ]->raw( $sMsg );
	}

	/**
	 * I noticed some people want to use a shortcut instead of writing out
	 * PRIVMSG #CHANNEL :OHI THERE!
	 * so I decided to add a quick method to send a message.
	 * @param string $sTo The channel or user to send this message to
	 * @param string $sMsg The Message to send
	 * @param string $sNet Optional, the network to send a message to
	 *
	 * @return boolean True if sent, false if not.
	 */
	public function msg( $sTo, $sMsg, $sNet = null )
	{
		if ( $sNet == null )
			$sNet = $this->bufferIn->Network;

		if ( ! isset( $this->m_aConnections[ $sNet ] ) )
			return false;

		return $this->m_aConnections[ $sNet ]->msg( $sTo, $sMsg );
	}

	/**
	 * I noticed some people want to use a shortcut instead of writing out
	 * NOTICE USER :OHI THERE!
	 * so I decided to add a quick method to send a NOTICE.
	 * @param string $sTo The channel or user to send this message to
	 * @param string $sMsg The Message to send
	 * @param string $sNet Optional, the network to send a message to
	 *
	 * @return boolean True if sent, false if not.
	 */
	public function notice( $sTo, $sMsg, $sNet = null )
	{
		if ( $sNet == null )
			$sNet = $this->bufferIn->Network;

		if ( ! isset( $this->m_aConnections[ $sNet ] ) )
			return false;

		return $this->m_aConnections[ $sNet ]->notice( $sTo, $sMsg );
	}

	/**
	 * I noticed some people want to use a shortcut instead of writing out
	 * PRIVMSG #CHANNEL :OHI THERE!
	 * so I decided to add a quick method to send a message.
	 * @param string $sTo The channel or user to send this message to
	 * @param string $sMsg The Message to send
	 * @param string $sNet Optional, the network to send a message to
	 *
	 * @return boolean True if sent, false if not.
	 */
	public function me( $sTo, $sMsg, $sNet = null )
	{
		if ( $sNet == null )
			$sNet = $this->bufferIn->Network;

		if ( ! isset( $this->m_aConnections[ $sNet ] ) )
			return false;

		// According to "standards" followed by a few clients, an action must end
		// with a chr(1), as it starts with.
		return $this->m_aConnections[ $sNet ]->msg( $sTo, ACTION . ' ' . $sMsg . chr(1) );
	}

	/**
	 * Just like the msg method but for joining channels.
	 *
	 * @param string $sChan The channel to join (with # in front)
	 * @param string $sPass The password for the channel
	 * @param string $sNet Optional, the network to send to
	 * @return boolean True on success, false on failure
	 */
	public function join( $sChan, $sPass = '', $sNet = null )
	{
		if ( $sNet == null )
			$sNet = $this->bufferIn->Network;

		if ( ! isset( $this->m_aConnections[ $sNet ] ) )
			return false;

		return $this->m_aConnections[ $sNet ]->raw( 'JOIN ' . $sChan . ' ' . $sPass );
	}

	/**
	 * Just like the join method but for joining channels.
	 *
	 * @param string $sChan The channel to join (with # in front)
	 * @param string $sNet Optional, the network to send to
	 * @return boolean True on success, false on failure
	 */
	public function part( $sChan, $sNet = null )
	{
		if ( $sNet == null )
			$sNet = $this->bufferIn->Network;

		if ( ! isset( $this->m_aConnections[ $sNet ] ) )
			return false;

		return $this->m_aConnections[ $sNet ]->raw( 'PART ' . $sChan );
	}

	/**
	 * This returns the default bot... this is here for legacy support.
	 * Deprecated, do not use! Use Bots::obj()->getDef() instead
	 * This will be removed after v1.1 (Within the next release)
	 *
	 * @deprecated since v0.9
	 *
	 * @return string the default bot
	 */
	public function getDef( )
	{
		return Bots::obj()->getDef( );
	}

	/**
	 * This sets the default bot... this is here for legacy support.
	 * Deprecated, do not use! Use Bots::obj()::setDef( $sKey ) instead
	 * This will be removed after v1.1 (Within the next release)
	 *
	 * @deprecated since v0.9
	 *
	 * @param string $sKey The bot to set as default
	 * @return boolean|string New nick on success,  false on failure
	 */
	public function setDef( $sKey )
	{
		// We keep this method in for legacy purposes, but make the call to
		// the apporpiate place
		$b = Bots::obj( )->getBot( $sKey );
		if ( $b !== false )
		{
			return Bots::obj()->setDef( $sKey );
		}

		return false;
	}

	/**
	 * This command performs a check for incoming messages. Really the receiving
	 * part of this bot. Vital for anything to happen (It would pingout without
	 * it).
	 *
	 * @return Boolean
	 */
	public function check( )
	{
		// For some random reason, our connections array can screw up so
		// we try to check against it. I believe it screwed up when I was
		// editing the config layout.
		if ( ! is_array( $this->m_aConnections) )
		{
			Logs::obj()->addDebug( 'Connections array is not array!' );
			return false;
		}

		// Loop through our Connections (servers) and check for new data
		foreach( $this->m_aConnections as $Connection )
			$Connection->Check( );
	}

	/**
	 * This is our motherload of methods. This is where we do the parsing for
	 * our incoming messages. All PINGs, PRIVMSG, Numerical Values (whois,
	 * welcome messages, etc) are done here. As with the check(), without this
	 * method, the bot will not function.
	 *
	 * Note to self, other devs. If any optimization is to be done, it will
	 * be done here. I have a feeling that the regex might be slowing it down
	 * a bit. As well as some other not needed things like Neduka. :/ It was a
	 * good idea at time of dev.
	 *
	 * After more looking, Neduka is a problem, but it is still needed. I will
	 * need to find ways of making it faster.
	 *
	 * @param String $sLine The line to parse. IRC has a tendancy to pool lines, so we split and send line by line
	 * @return null
	 */
	public function process( $sLine )
	{

		if ( isset( $this->bufferIn->Neduka ) )
		{
			unset( $this->bufferIn->Neduka );
			unset( $this->bufferIn->NedukaStr  );
		}

		// Prepare the line for parsing
		//$aPieces = explode( ' ', $sLine );
		
		$this->bufferIn->src->set( $sLine );

		// 1 word lines? Go away!
		if ( ! isset( $this->bufferIn->src[0] ) ) return ;

		// Is our message a ping?
		if ( $this->bufferIn->src[0] == 'PING' )
		{
			return $this->m_aConnections[ $this->bufferIn->Network ]->
			    raw( 'PONG ' . substr( $this->bufferIn->src[ 1 ], 1 ) );
		}

		// Oops. We have some error. :/
		if ( $this->bufferIn->src[ 0 ] == 'ERROR' )
		{
			// Tell the connections handler we are disconnected so we
			// don't try to send any more data
			$this->m_aConnections[ $this->bufferIn->Network ]->
				Killed( );

			// Make the bot reconnect after 35 seconds. Should this be configurable?
			Timers::obj( )->addTimer
			(
				$this->m_sNick,
				35,
				1,
				array
				(
					$this->m_aConnections[ $this->bufferIn->Network ],
					'Connect',
				)
			);

			// Log
			Logs::obj()->addLog( 'Lost connection: ' . $this->m_sNick . ' to ' . $this->bufferIn->Network. true );
			return;
		}

		// This piece stores who the message is from.
		if ( preg_match( '/:([^!]*)!([^@]*)@(.*)/', $this->bufferIn->src[ 0 ], $aMatches ) )
		{
			$this->bufferIn->From = $aMatches[ 1 ];
			$this->bufferIn->Ident = $aMatches[ 2 ];
			$this->bufferIn->Host = $aMatches[ 3 ];
		}
		else
		{
			// Message from the server
			$this->bufferIn->From = $this->bufferIn->From = substr( $this->bufferIn->src[ 0 ], 1 );
			$this->bufferIn->Host = $this->bufferIn->Ident = '';
		}



		// We place the count outside of the for loop, because PHP calls the
		// count function each time it begins the next iteration.
		$iMax = $this->bufferIn->src->count( );

		// Now we loop. We already know we don't want the first one, since
		// that starts with the : by default.
		for( $iID = 1; $iID < $iMax; $iID++ )
		{
			// I don't see why we would have an empty thing.
			// So for now we'll comment it out. If we do find some error
			// with it and have empty IDs, we'll uncomment this.
			//if ( isset( $this->bufferIn->ex[ $iID ][ 0 ] ) )
			$sPiece = $this->bufferIn->src[ $iID ][ 0 ];
			//else
			//		continue;

			if ( $sPiece == ':' )
			{
				$this->bufferIn->Neduka = substr( $this->bufferIn->src[ $iID ], 1 );
				$this->bufferIn->NedukaStr = $this->bufferIn->Neduka . ' ';
				$this->bufferIn->NedukaStr .= $this->bufferIn->src->_( $iID+1 );

				break;
			}
		}

		// Switch through other messages, for our callbacks.
		$sCommand = strtolower( $this->bufferIn->src[ 1 ] );
		switch( $sCommand )
		{
			case 'privmsg': // Private message. Channel or user
				$this->bufferIn->Channel = $this->bufferIn->src[ 2 ];

				// This is a channel message
				$sChan = $this->bufferIn->src[ 2 ];
				$aMasks = $this->m_aChanLabels;

				if ( in_array( $sChan[ 0 ], $aMasks ) )
				{
					$sUser = $this->bufferIn->From;
					$sSubChan = substr( $this->bufferIn->src[ 2 ], 1 );

					$sMsg = $this->bufferIn->NedukaStr;
					if ( $sChan[ 0 ] == '#' )
					{
						$this->m_aModules->onMsg( );
						GlobalModules::obj()->onMsg(  $this, $this->bufferIn->From, $sChan, $sMsg );
					}
					else
					{
						GlobalModules::obj()->onMsg($this, $this->bufferIn->From, $sChan, $sMsg, $aMatches[ 0 ]);
						$this->m_aModules->onMsg( );
					}
				}
				else
				{
					// Private message (TO debot). Aha here is my Neduka again.
					GlobalModules::obj()->onPrivMsg( $this, $this->bufferIn->From, $this->bufferIn->NedukaStr );
					$this->m_aModules->onPrivMsg( );
				}
				break;

			case 'join': // Someone (or debot) has joined the channel
				if ( $this->bufferIn->From == $this->m_sNick ) // Was it DeBot who joined the chan?
				{
					// Perform a query on the channel to get statistics
					$this->m_aConnections[ $this->bufferIn->Network ]->
						raw( 'MODE ' . $this->bufferIn->src[ 2 ] );
				}
				else
				{
					$this->bufferIn->Channel = $this->bufferIn->src[ 2 ];
					// Perform a callback on the modules to indicate a join has been made
					// Aha, here is the neduka. Dang. Wait.... what params are sent onjoin? Oh right... Channel
					$this->m_aModules->onJoin( );
					GlobalModules::obj()->onJoin( );
				}
				break;

			case '376': // End of MOTD. Meaning most spam is done. We can begin our adventure
			case '422': // No MOTD, but still, no more spam.
				// onConnect()
				foreach( $this->bufferOut[ $this->bufferIn->Network ] as $sRaw )
				{
					$this->m_aConnections[ $this->bufferIn->Network ]->
						raw( $sRaw );
				}
				GlobalModules::obj()->onConnect( $this );
				$this->m_aModules->onConnect( );
				break;

			case '353': // Our information for the channel
				// Something here is going a bit dodgy, perhaps on an incorrect configuration or something
				$sChan = strtolower( $this->bufferIn->src[ 4 ] );
				$aUsers = $this->bufferIn->src->_a( 5 );
				
				GlobalModules::obj()->onNames( $this, $this->bufferIn->Network, $sChan, $aUsers );
				Servers::obj( )->onNames( $this->bufferIn->Network, $sChan, $aUsers );
				break;

			// :badbuh.nj.us.dab-media.com 324 dab #dab +QSfjnrt [9j,85m,5n]:15 3:5
			case '324': // List of modes on the channel
				$sChan = strtolower( $this->bufferIn->src[ 3 ] );
				$sMode = $this->bufferIn->src[ 4 ];
				if ( isset( $this->bufferIn->src[ 5 ] ) )
					$sParams = $this->bufferIn->src->_(5);//implode( ' ', array_slice( $this->bufferIn->ex, 5 ) );
				else
					$sParams = '';

				GlobalModules::obj()->onMode( $this, $this->bufferIn->Network, $sChan, $sMode, $sParams);
				Servers::obj( )->onMode( $this->bufferIn->Network, $sChan, $sMode, $sParams);
				break;

			// :dabtop!dabitp@netadmin.dab-Media.com MODE #dab +v DaBit
			case 'mode': // A mode was made on a channel
				$this->bufferIn->Channel = $this->bufferIn->src[ 2 ];
				$sChan = strtolower( $this->bufferIn->src[ 2 ] );
				if ( ! isset( $this->bufferIn->src[ 3 ] ) )
					return ;
				$sMode = $this->bufferIn->src[ 3 ];
				if ( isset( $this->bufferIn->src[ 4 ] ) )
					$sParams = $this->bufferIn->src->_(4);//implode( ' ', array_slice( $this->bufferIn->ex, 4 ) );
				else
					$sParams = '';

				GlobalModules::obj()->onMode( $this, $this->bufferIn->Network, $sChan, $sMode, $sParams);
				Servers::obj( )->onMode( $this->bufferIn->Network, $sChan, $sMode, $sParams);
				break;

			// :badbuh.nj.us.dab-media.com 332 dabbers #dab :3Welcome to 10dab's 5channel!!!! 1///
			case '332': // a topic upon joining
				if ( isset( $this->bufferIn->src[ 4 ] ) )
					$sParams = $this->bufferIn->NedukaStr;
				else
					$sParams = '';

				$sChan = $this->bufferIn->src[ 3 ];
				GlobalModules::obj()->onTopic( $this, $this->bufferIn->Network, $sChan, $sParams);
				Servers::obj( )->onTopic( $this->bufferIn->Network, $sChan, $sParams);
				break;

			// :Suspira!me@suspira.suspira TOPIC #dab :3Welcome to 10dab's
			case 'topic': // A topic set by user
				if ( isset( $this->bufferIn->src[ 3 ] ) )
					$sParams = $this->bufferIn->NedukaStr;
				else
					$sParams = '';

				$this->bufferIn->Channel = $sChan = $this->bufferIn->src[ 2 ];
				GlobalModules::obj()->onTopic( $this, $this->bufferIn->Network, $sChan, $sParams );
				Servers::obj( )->onTopic( $this->bufferIn->Network, $sChan, $sParams );
				break;

			// :dab_test!dabitp@dab-media.com QUIT :Quit: http://www.mibbit.com ajax IRC Client
			case 'quit': // a user quit the irc
				GlobalModules::obj()->onQuit( $this, $this->bufferIn->From, $this->bufferIn->src->_(2) );
				$this->m_aModules->onQuit( );
				break;

			case 'part': // a user parts the channel
				$this->bufferIn->Channel = $this->bufferIn->src[ 2 ];
				GlobalModules::obj()->onPart( $this, $this->bufferIn->From, $this->bufferIn->src[ 2 ],$this->bufferIn->src->_(2) );
				$this->m_aModules->onPart( );
				break;

			case 'nick': // A person changes their nick
				GlobalModules::obj()->onNick( $this, $this->bufferIn->From, trim( $this->bufferIn->NedukaStr ) );
				$this->m_aModules->onNick( );
				break;
			case '311': // All of these are WHOIS results.
			case '378':
			case '379':
			case '307':
			case '319':
			case '312':
			case '313':
			case '310':
			case '671':
			case '317':
			case '318':
			case '401':
			case '318': // End of WHOIS results
				/*
				<- :irc.botsites.net 401 dab sdfasdfasdf :No such nick/channel
				<- :irc.botsites.net 318 dab sdfasdfasdf :End of /WHOIS list.
				*/
				$this->bufferIn->Whois = stdClass;
				$this->bufferIn->Whois->Who = $this->bufferIn->src[ 3 ];
				$this->bufferIn->Whois->Id = $this->bufferIn->src[ 1 ];
				$this->bufferIn->Whois->Id = $this->bufferIn->src->_(3);
				
				GlobalModules::obj()->onWhois( $this, $this->bufferIn->src[ 3 ], $this->bufferIn->src[ 1 ], $this->bufferIn->Whois->Id );
				$this->m_aModules->onWhois( );
				break;

			case 'notice': // On notice
				// Wait... There are more than private notices.... wtf?
				GlobalModules::obj()->onPrivNotice( $this, $this->bufferIn->From, trim( $this->bufferIn->NedukaStr ) );
				$this->m_aModules->onPrivNotice( );
				break;

			case 'kick':
				$this->bufferIn->Channel = $this->bufferIn->src[ 2 ];
				GlobalModules::obj()->onKick( $this, $this->bufferIn->From, $this->bufferIn->src[ 2 ], $this->bufferIn->src[ 3 ], $this->bufferIn->NedukaStr );
				$this->m_aModules->onKick( );
				break;

			default: // We don't have a callback for this one.. :x
				//echo 'Bot.php-> ' . $sLine ."\n";
				break;
		}
	}

	public function offsetExists( $sKey )
	{
		switch ( strtolower( $sKey ) )
		{
			case 'nick':
			case 'ident':
			case 'real':
			case 'realname':
			case 'connections':
			case 'connection':
			case 'modules':
			case 'module':
			case 'bufferin':
			case 'in':
			case 'bufferout':
			case 'out':
				return true;
			default:
				return false;
		} // end switch of $sKey
	}

	public function offsetGet( $sKey )
	{

		switch ( strtolower( $sKey ) )
		{
			case 'nick':
				return $this->m_sNick;
			case 'ident':
				return $this->m_sIdent;
			case 'real':
			case 'realname':
				return $this->m_sReal;
			case 'connections':
			case 'connection':
				return $this->m_aConnections;
			case 'modules':
			case 'module':
				return $this->m_aModules;
			case 'bufferin':
			case 'in':
				return $this->bufferIn;
			case 'bufferout':
			case 'out':
				return $this->bufferOut;
			default:
				return new stdClass;
		} // end switch of $sKey

	} // End offsetGet Function

	public function offsetSet( $sKey, $mValue )
	{
		switch ( strtolower( $sKey ) )
		{
			case 'nick':
				$this->m_sNick = $mValue;
			break;

			case 'bufferin':
			case 'in':
				$this->bufferIn  = $mValue;
			break;

			case 'bufferout':
			case 'out':
				$this->bufferOut = $mValue;
			break;

			default:
				return ;
		} // end switch of $sKey

	} // End offsetSet function

	public function offsetUnset( $sKey )
	{
		return ;
	}
	
	public function __call( $sName, $mArg )
	{
		Logs::obj()->addDebug( "__call on Bot object. Saved from a crashed!" );
	}
	
	public function load( $sModule )
	{
		return $this->m_aModules->load( $sModule );
	}
	public function unload( $sModule )
	{
		return $this->m_aModules->unload( $sModule );
	}
	public function reload( $sModule )
	{
		return $this->m_aModules->reload( $sModule );
	}



	public function __toString( )
	{
		return $this->m_sNick . ' Bot Class';
	}
} // End bot class

?>
