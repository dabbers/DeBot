<?php

class m_Cmds extends Module
{
	private $m_aCmds;
	private $m_aKeys = array
	(
		'code',
		'chanbind',
		'serverbind',
		'level',
		'mode',
		'time',
		'used',
		'allowpm',
		'hide',
	);
	private $m_sKeys = 'code, chanbind, serverbind, level, mode, time, used, allowpm, hide';
	private $m_iCmdsTime = 0; // Used for our !commands, command.
	public $m_iCmdsAntiSpam = 15;  // Default antispam timer value.


	public function init( )
	{
		if ( file_exists( 'Modules/Commands/' . ( $this -> m_sNick ) . '.dabcmd' ) )
		{
			$sContents = download( 'Modules/Commands/' . $this -> m_sNick . '.dabcmd' );
			$this -> m_aCmds = unserialize( $sContents );
		}
		else
		{
			$sContents = download( 'Modules/Commands/intro.dabcmds' );
			$this -> m_aCmds = unserialize( $sContents );
		}
		foreach( $this->m_aCmds as $sKey => $aPiec )
		{
			if ( $aPiec[ 'used' ] == true )
			{
				$this->m_aCmds[ $sKey ]['used'] = false;
			}
		}
	}

	public function unload( )
	{
		$this->saveCmds( );
	}

	public function onPrivMsg( )
	{

		$oBot = $this->m_oBot;
		$sTo = $oBot['in']->From;
		
		$aMsg = explode( ' ', $oBot['in']->NedukaStr );

		$sCmd = strtolower( $aMsg[ 0 ] );
		
		if ( $sCmd == chr(1) . 'version' . chr(1) )
		{
			// Please don't change this.
			$this->m_oBot -> raw( 'NOTICE ' . $sFrom . ' :VERSION ' . VERSION_STR . '' );
		}
		else if ( substr( $sCmd, 0, 1 ) == CMD )
		{
			if ( ! isset( $this -> m_aCmds[ substr( $sCmd, 1 ) ] ) )
				return false;

			$sCmd = substr( $sCmd, 1 );
			if ( $this -> m_aCmds[ $sCmd ][ 'used' ] == true )
				return false;
			$oServers = Servers :: obj();
			$sNet = $this->m_oBot -> bufferIn -> Network;
			$aChanInfo = $oServers['chans'][ $sNet ][ $sTo ];

			if ( ! $this -> m_aCmds[ $sCmd ][ 'allowpm' ] )
				return false;

			if ( strpos( $this -> m_aCmds[ $sCmd ][ 'chanbind' ], $sTo ) === false && $this -> m_aCmds[ $sCmd ][ 'chanbind' ] != '*' )
				return false;
			if ( $oBot[ 'module' ][ 'RawEval' ] -> userLevel( $sFrom ) < $this -> m_aCmds[ $sCmd ][ 'level' ] && $this -> m_aCmds[ $sCmd ][ 'level' ] != '*' )
				return false;
			if ( strpos( $this -> m_aCmds[ $sCmd ][ 'serverbind' ], $this->m_oBot -> bufferIn -> Network ) === false && $this -> m_aCmds[ $sCmd ][ 'serverbind' ] != '*' )
				return false;

			if ( strpos( $this -> m_aCmds[ $sCmd ][ 'chanbind' ], $sTo ) !== false )
			{
				$t = explode( ',', $this -> m_aCmds[ $sCmd ][ 'chanbind' ] );
				foreach( $t as $c )
				{
					if ( $c == $sTo )
					{
						if (! isset( $oServers[ 'chans' ][ $sNet ][ $c ][ $sFrom ] ) ||
							(
								$this -> m_aCmds[ $sCmd ][ 'mode' ] != '*' &&
								strpos( $oServers[ 'chans' ][ $sNet ][ $c ][ $sFrom ], $this -> m_aCmds[ $sCmd ][ 'mode' ] ) !== false
							)
						)
							return false;
					}
				}
			}

			//$oServers[ 'chans' ][ $sNet ][ $sTo ][ $sFrom ]

			if ( $this -> m_aCmds[ $sCmd ][ 'time' ] > 0 )
			{
				$this -> m_aCmds[ $sCmd ][ 'used' ] = true;
				Timers :: obj() -> addTimer
					(
						$this -> m_sNick,
						$this -> m_aCmds[ $sCmd ][ 'time' ],
						1,
						array( $this->m_oBot['module']['Cmds'], $sCmd . '_NotUsed' )
					);
			}

			$oBot[ 'module' ][ 'RawEval' ] -> raw( $this -> m_aCmds[ $sCmd ][ 'code' ], $sTo, $this->m_oBot );
			return MOD_END;
		}
	}

	public function onPrivNotice( )
	{
		// echo $sFrom . '<=N ' . $sMsg . chr( 10 );
	}

	public function onMsg( )
	{
		$oBot = $this->m_oBot;
		$sTo = $oBot['in']->Channel;
		$sFrom = $oBot['in']->From;
		$aMsg = explode( ' ', $oBot['in']->NedukaStr );

		//$sLine = implode( ' ', array_slice( $aMsg, 1 ) );

		$sCmd = strtolower( $aMsg[ 0 ] );

		if ( Bots::obj()->getDef( ) == $oBot->m_sNick )
		{
			if ( $sCmd == CMD . 'addcmd' )
			{
				
				if ( $oBot[ 'module' ][ 'RawEval' ]->userLevel( $sFrom ) < 5 )
					return false;
				
				$aMsg[1] = strtolower( $aMsg[ 1 ] );
				
				if ( ! isset( $aMsg[ 2 ] ) )
					return $oBot -> msg( $sTo, CMD_ERROR . ' missing params: '.CMD.'addcmd command Code To Execute' );

				if ( isset( $this -> m_aCmds[ $aMsg[ 1 ] ] ) )
					return $oBot -> msg( $sTo, CMD_ERROR . ' ' . CMD .  $aMsg[ 1 ] . ' already exists!' );
				
				$aMsg[1] = strtolower( $aMsg[ 1 ] );
				
				$this -> m_aCmds[ $aMsg[ 1 ] ][ 'used' ] = false;
				$this -> m_aCmds[ $aMsg[ 1 ] ][ 'time' ] = 15;
				$this -> m_aCmds[ $aMsg[ 1 ] ][ 'code' ] = implode( ' ', array_slice( $aMsg, 2 ) );
				$this -> m_aCmds[ $aMsg[ 1 ] ][ 'level' ] = '*';
				$this -> m_aCmds[ $aMsg[ 1 ] ][ 'chanbind' ] = '*';
				$this -> m_aCmds[ $aMsg[ 1 ] ][ 'serverbind' ] = '*';
				$this -> m_aCmds[ $aMsg[ 1 ] ][ 'mode' ] = '*';
				$this -> m_aCmds[ $aMsg[ 1 ] ][ 'allowpm' ] = 'false';

				$this -> saveCmds( );
				return $oBot -> msg( $sTo, CMD_SUCCESS . ' ' . CMD . $aMsg[ 1 ] . ' added!' );
			}
			else if ( $sCmd == CMD . 'setcmd' )
			{
				if ( $oBot[ 'module' ][ 'RawEval' ]->userLevel( $sFrom ) < 5 )
					return false;
				if ( ! isset( $aMsg[ 3 ] ) )
					return $oBot -> msg( $sTo, CMD_ERROR . ' missing params: '.CMD.'setcmd command Key Value' );

				if ( ! isset( $this -> m_aCmds[ $aMsg[ 1 ] ] ) )
					return $oBot -> msg( $sTo, CMD_ERROR . ' ' . CMD .  $aMsg[ 1 ] . ' doesn\'t exist!' );

				if ( ! in_array( $aMsg[ 2 ], $this -> m_aKeys ) )
					return $oBot -> msg( $sTo, CMD_ERROR . ' invalid key. Possible keys: ' . $this -> m_sKeys );

				$this -> m_aCmds[ $aMsg[ 1 ] ][  $aMsg[ 2 ] ] = implode( ' ', array_slice( $aMsg, 3 ) );

				$this -> saveCmds( );
				return $oBot -> msg( $sTo, CMD_SUCCESS . ' ' . CMD . $aMsg[ 1 ] . ' updated.' );
			}
			else if ( $sCmd == CMD . 'getcmd' )
			{
				if ( $aMsg[ 1 ] == 'all' )
				{
					$sCommands = '';
					foreach( $this->m_aCmds as $sKey => $aBlah )
					{
						if ( isset( $aBlah[ 'hide' ] ) && $aBlah[ 'hide' ] )
							continue;

						if ( $aBlah[ 'level' ] <= $oBot[ 'module' ][ 'RawEval' ]->userLevel( $sFrom ) )
						{
							$sCommands .= CMD . $sKey . " ";
						}
					}
					return $oBot -> msg
						(
							$sTo,
							'Commands: ' . $sCommands
						);
				}

				if ( $oBot[ 'module' ][ 'RawEval' ]->userLevel( $sFrom ) < 5 )
					return false;

				if ( ! isset( $aMsg[ 2 ] ) )
					return $oBot -> msg( $sTo, CMD_ERROR . ' missing params: '.CMD.'setcmd command Key' );

				if ( ! isset( $this -> m_aCmds[ $aMsg[ 1 ] ] ) )
					return $oBot -> msg( $sTo, CMD_ERROR . ' ' . CMD .  $aMsg[ 1 ] . ' doesn\'t exist!' );

				if ( ! in_array( $aMsg[ 2 ], $this -> m_aKeys ) )
					return $oBot -> msg( $sTo, CMD_ERROR . ' invalid key. Possible keys: ' . $this -> m_sKeys );

				return $oBot -> msg
					(
						$sTo,
						'Value of: ' . CMD . $aMsg[ 1 ] . ' - ' . $this -> m_aCmds[ $aMsg[ 1 ] ][  $aMsg[ 2 ] ]
					);
			}
			else if ( $sCmd == CMD . 'delcmd' )
			{
				if ( $oBot[ 'module' ][ 'RawEval' ] -> isAuthed( $sFrom ) == false )
					return false;
				if ( ! isset( $aMsg[ 1 ] ) )
					return $oBot -> msg( $sTo, CMD_ERROR . ' missing params: '.CMD.'delcmd command' );

				if ( ! isset( $this -> m_aCmds[ $aMsg[ 1 ] ] ) )
					return $oBot -> msg( $sTo, CMD_ERROR . ' ' . CMD .  $aMsg[ 1 ] . ' doesn\'t exist!' );

				unset( $this -> m_aCmds[ $aMsg[ 1 ] ] );

				$this -> saveCmds( );
				return $oBot -> msg( $sTo, CMD_SUCCESS . ' ' . CMD . $aMsg[ 1 ] . ' removed.' );
			}
			else if ( $sCmd == CMD . 'cmdkeys' )
			{
				if ( $oBot[ 'module' ][ 'RawEval' ] -> isAuthed( $sFrom ) == false )
					return false;

				return $oBot -> msg( $sTo, 'Possible keys: ' . $this -> m_sKeys );
			}
			else if ( $sCmd == CMD . 'commands' )
			{
				if ( time( ) - $this->m_iCmdsTime < $this->m_iCmdsAntiSpam )
					return ;

				$this->m_iCmdsTime = time( );
				$sCommands = '';
				foreach( $this->m_aCmds as $sKey => $aBlah )
				{
					if ( isset( $aBlah[ 'hide' ] ) && $aBlah[ 'hide' ] )
						continue;

					if ( $aBlah[ 'level' ] <= $oBot[ 'module' ][ 'RawEval' ]->userLevel( $sFrom ) )
					{
						if ( $aBlah[ 'chanbind' ] != '*' && strpos( $aBlah[ 'chanbind' ], $sTo . ',' ) === false )
							continue;

						$sCommands .= CMD . $sKey . " ";
					}
				}
				return $oBot -> msg
					(
						$sTo,
						'Commands: ' . $sCommands
					);
			}
		}
		
		if ( $sCmd[0] == CMD )
		{
			if ( ! isset( $this -> m_aCmds[ substr( $sCmd, 1 ) ] ) )
				return false;

			$sCmd = substr( $sCmd, 1 );
			
			if ( $this->m_aCmds[ $sCmd ][ 'used' ] == true )
			{
				return false;
			}
	
			$oServers = Servers :: obj();
			$sNet = $oBot -> bufferIn -> Network;
			$aChanInfo = $oServers['chans'][ $sNet ][ $sTo ];

			if ( $this -> m_aCmds[ $sCmd ][ 'chanbind' ] != '*' && strpos( $this -> m_aCmds[ $sCmd ][ 'chanbind' ], $sTo ) === false )
			{
				return false;
			}

			if ( $this->m_aCmds[ $sCmd ][ 'level' ] != '*' && $oBot[ 'module' ][ 'RawEval' ]->userLevel( $sFrom ) < $this->m_aCmds[ $sCmd ][ 'level' ] )
			{
				return false;
			}

			if ( $this->m_aCmds[ $sCmd ][ 'serverbind' ] != '*' && strpos( $this->m_aCmds[ $sCmd ][ 'serverbind' ], $oBot -> bufferIn -> Network ) === false )
			{
				return false;
			}


			if ( strpos( $this -> m_aCmds[ $sCmd ][ 'chanbind' ], $sTo ) !== false )
			{
				$t = explode( ',', $this -> m_aCmds[ $sCmd ][ 'chanbind' ] );

				foreach( $t as $c )
				{
					if ( $c == $sTo )
					{
						$bSecond = $this->m_aCmds[ $sCmd ][ 'mode' ] != '*';
						$bThird = strpos
						(
							$oServers[ 'chans' ][ $sNet ][ $c ][ $sFrom ],
							$this -> m_aCmds[ $sCmd ][ 'mode' ]
						) !== false;

						if ( $bSecond && $bThird )
							return false;

						break;
					}
				}
			}

			//$oServers[ 'chans' ][ $sNet ][ $sTo ][ $sFrom ]

			$oBot[ 'module' ][ 'RawEval' ]->raw( $this -> m_aCmds[ $sCmd ][ 'code' ], $sTo, $oBot );

			$this->m_aCmds[ $sCmd ][ 'used' ] = true;

			Timers :: obj() -> addTimer
			(
				$this -> m_sNick,
				$this -> m_aCmds[ $sCmd ][ 'time' ],
				1,
				array( $oBot['module']['Cmds'], $sCmd . '_NotUsed' )
			);
			return MOD_END;
		}
	}

	public function __call( $sName, $aParam )
	{
		list( $sCmd, $sDuh ) = @ explode( '_', $sName );

		if ( $sDuh == 'NotUsed' )
		{
			if ( isset( $this -> m_aCmds[ $sCmd ] ) )
				$this -> m_aCmds[ $sCmd ][ 'used' ] = false;
		}
	}

	public function saveCmds( )
	{
		$h = fopen( 'Modules/Commands/' . $this -> m_sNick . '.dabcmd', 'w' );
		fwrite( $h, serialize( $this -> m_aCmds ) );
		fclose( $h );
	}
	
	/**
	 * Link a command here to a method in another module.
	 * Useful for wanting antispam and channel binding of here, without
	 * recoding that anywhere else
	 *
	 * @param string $sCmd The command that will call this method
	 * @param Object $oCommand The command location ( array( object, 'method' ) )
	 * @return bool
	 */
	public function addCommandLink( $sCmd, $sModule, $sMethod, $aSettings = array( 'used'=>false,'time'=>15,'level'=>'*','chanbind'=>'*','serverbind'=>'*','mode'=>'*','allowpm'=>false ) )
	{
		if ( isset( $oBot["modules"][ "TestModule"] ) )
			call_user_func( array( $oBot["modules"][ "TestModule"],"testmod") );
			
		if ( isset( $this -> m_aCmds[ $sCmd ] ) )
			return false;

		$this -> m_aCmds[ $sCmd ][ 'used' ] = $aSettings['used'];
		$this -> m_aCmds[ $sCmd ][ 'time' ] = $aSettings['time'];
		$this -> m_aCmds[ $sCmd ][ 'code' ] = 'if ( isset( $oBot["modules"][ "'.$sModule.'"] ) ) call_user_func( array($oBot["modules"][ "'.$sModule.'"],"' . $sMethod . '") ); else echo CMD_ERROR." Module is not loaded";';
		$this -> m_aCmds[ $sCmd ][ 'level' ] = $aSettings['level'];
		$this -> m_aCmds[ $sCmd ][ 'chanbind' ] = $aSettings['chanbind'];
		$this -> m_aCmds[ $sCmd ][ 'serverbind' ] = $aSettings['serverbind'];
		$this -> m_aCmds[ $sCmd ][ 'mode' ] = $aSettings['mode'];
		$this -> m_aCmds[ $sCmd ][ 'allowpm' ] = $aSettings['allowpm'];

		$this -> saveCmds( );
		return true;
	}

	public function __toString( )
	{
		return 'Cmds Module';
	}
}

?>
