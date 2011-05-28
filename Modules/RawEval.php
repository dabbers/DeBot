<?php

/**
 * DeBot Module - Raw Eval
 * Created by dab ??? ?? 2009
 * Last Edited: Jul 29 2010
 * This module allows you to perform raw php commands. This module uses the Users
 * array in the config so make sure you fill it in properly
 *
 * @author David (dab) <dabitp@gmail.com>
 * @version v1.0
 */

class m_RawEval extends Module
{

	public $m_aUsers;
	private $m_aUsersLists;

	public function init ( )
	{
		$oBots =  Bots :: obj( );

                // Get our bot ojbect
                $oBot = Bots::obj( )->getBot( $this->m_sNick );

                // Get the Config
                $aUsers = Config::obj( )->getConfig( ); // $oBots -> getConfig( );

                // We loop through to get our user accounts in the config.
                foreach ( $aUsers['Users'] as $sNick => $aUser )
                {
                        list( $sNickname, $sUsername, $sHostname ) = preg_split( '/!|@/s', $sNick );
                        $this ->  m_aUsersLists[ ] = array
                        (
                                'Hostname' => $sHostname,
                                'Nick' => $sNickname,
                                'Ident' => $sUsername,
                                'Level' => $aUser[ 'Level' ],
                                'Pass'  => $aUser[ 'Pass' ],
                                'Authed' => false
                        );
                }
	}

	public function onConnect( )
	{

	}

	public function onPrivMsg( )
	{

		$oBot = $this->m_oBot;

		$sFrom = $oBot['in']->From;

		$aMsg = explode( ' ', $oBot['in']->NedukaStr );

		$sNet = $oBot -> bufferIn -> Network;


		if ( Bots::obj()->getDef( ) != $oBot->m_sNick )
			return false;

		if ( strtolower( $aMsg[ 0 ] ) == 'login' )
		{

			//$oBot -> bufferIn -> From
			//$this -> bufferIn -> Host
			$sHost = $oBot -> bufferIn -> Host;
			$sIdent = $oBot -> bufferIn -> Ident;

			foreach( $this -> m_aUsersLists as $aUser )
			{
				$iNeeds = 0;
				if ( $sHost == $aUser[ 'Hostname' ] || $aUser[ 'Hostname' ] == '*' )
					$iNeeds++;
				if ( $sFrom == $aUser[ 'Nick' ] || $aUser[ 'Nick' ] == '*' )
					$iNeeds++;
				if ( $sIdent == $aUser[ 'Ident' ] || $aUser[ 'Ident' ] == '*' )
					$iNeeds++;
				if ( isset( $aMsg[ 1 ] ) && md5( $aMsg[ 1 ] ) == $aUser[ 'Pass' ] )
					$iNeeds++;

				if ( $iNeeds >= 4 )
				{
					$this -> m_aUsers[ $sFrom ][ 'Authed' ] = true;
					$this -> m_aUsers[ $sFrom ][ 'Level' ] = $aUser[ 'Level' ];

					$oBot -> msg( $sFrom, CMD_SUCCESS . ' logged in!' );

					return MOD_END;
				}
			}

			$oBot -> msg( $sFrom, CMD_ERROR . ' invalid pass, host, ident or Nick.' );
			return MOD_END;
		}
		else if ( strtolower( $aMsg[ 0 ] ) == 'logout' )
		{
			if ( $this -> m_aUsers[ $sFrom ][ 'Authed' ] == true )
				unset( $this -> m_aUsers[ $sFrom ] );

			$oBot -> msg( $sFrom, CMD_SUCCESS . ' logged out!' );
			return MOD_END;
		}
		else if ( $aMsg[ 0 ] == CMD_RAW )
		{
			if ( $this -> m_aUsers[ $sFrom ][ 'Authed' ] == false )
				return false;

			$sLine = implode( ' ', array_slice( $aMsg, 1 ) );
			$this -> raw( $sLine, $sFrom, $oBot );
		}
	}


	public function onMsg( )
	{

		$oBot = $this->m_oBot;
		$sTo = $oBot['in']->Channel;
		$sFrom = $oBot['in']->From;
		

		if ( Bots::obj()->getDef() != $oBot->m_sNick )
			return false;


		if ( ! isset( $this -> m_aUsers[ $sFrom ][ 'Authed' ] ) )
			return false;


		if ( $this -> m_aUsers[ $sFrom ][ 'Authed' ] == false )
			return false;


		$aMsg = explode( ' ', $oBot['in']->NedukaStr );

		$sLine = implode( ' ', array_slice( $aMsg, 1 ) );
		if ( $aMsg[ 0 ] == CMD_RAW )
		{

			$this -> raw( $sLine, $sTo, $oBot );
			return MOD_END;
		}
	}

	public function raw( $sLine, $sWhere, Bot $oBot )
	{
		$obot = $oBot;
		$oBOt = $oBot;
		$OBOT = $oBot;
		$oBOT = $oBot;
		$OBOt = $oBot;


		if ( function_exists( 'runkit_lint' ) )
		{
			if ( ! runkit_lint( $sLine ) )
			{
				return $oBot -> msg( $sTo, 'Error with the given code!' );
			}
		}

		$sRes = $this->parse( $sLine );

		if ( $sRes != '' )
			return $oBot->msg( $sWhere, '[Error] ' . $sRes );

		//Logs::obj()->m_oToObj = $oBot;
		//Logs::obj()->m_oLogTo = $sWhere;

		ob_start( );

		error_reporting( E_ALL );

		eval
		(
			'error_reporting( E_ALL ); ' . $sLine
		);

		$sOutput = ob_get_clean( );

		$aOutput = explode( chr(10), trim( $sOutput ) );


		foreach( $aOutput as $sOut )
		{

			if ( ( $sOut != null ) && ( $sOut != '' ) )
			{
				$oBot -> msg( $sWhere, $sOut );
			}
		}



		return true;
	}

	public function isAuthed( $sNick )
	{
		return isset( $this -> m_aUsers[ $sNick ] );
	}

	public function userLevel( $sNick )
	{

		if ( isset( $this -> m_aUsers[ $sNick ] ) )
			return $this -> m_aUsers[ $sNick ][ 'Level' ];
		else
			return 0;
	}

	public function onQuit( )
	{

		if ( isset( $this -> m_aUsers[ $sWho ] ) )
			unset( $this -> m_aUsers[ $sWho ] );

	}

	public function onPart( )
	{
		if ( $sChan == AUTH_CHAN )
		{
			if ( isset( $this -> m_aUsers[ $sWho ] ) )
				unset( $this -> m_aUsers[ $sWho ] );
		}
	}

	/**
	 * This is called when a user is kickd.
	 *
	 * @param string $sWho The person doing the kicking
	 * @param string $sChan The channel the user was kicked from
	 * @param string $sLeft The user who was kicked from teh channel
	 * @param string $sWhy The reason for being kicked
	 */
	public function onKick( )
	{
		if ( $sChan == AUTH_CHAN )
		{
			if ( isset( $this -> m_aUsers[ $sLeft ] ) )
				unset( $this -> m_aUsers[ $sLeft ] );
		}
	}

	public function onNick( )
	{

		if ( isset( $this -> m_aUsers[ $sOld ] ) )
		{
			$this -> m_aUsers[ $sNew ] = $this -> m_aUsers[ $sOld ];
			unset( $this -> m_aUsers[ $sOld ] );
		}
	}

	public function __toString( )
	{
	    return 'RawEval Module';
	}

	public function parse( $sCode )
	{
	    $aThings = token_get_all( '<?php ' . $sCode . ' ?>' );

	    $sReturn = '';
	    // Loop through all elements
	    for( $iIndex = 0; $iIndex <= count( $aThings ); $iIndex +=1  )
	    {
			if ( ! isset( $aThings[ $iIndex ] ) || ! is_array( $aThings[ $iIndex ] ) )
				continue;

			$iVal = $aThings[ $iIndex ][ 0 ];

			if ( $iVal != T_VARIABLE && $iVal != T_STRING )
				continue;
			


			if ( ! is_array( $aThings[ $iIndex + 1 ] ) && $aThings[ $iIndex + 1 ] == '(' && $aThings[ $iIndex - 1 ][ 1 ] != '->' && $aThings[ $iIndex - 1 ][ 1 ] != '::' )
			{
				if ( is_array( $aThings[ $iIndex ] ) && $aThings[ $iIndex ][0] == T_FUNCTION || $aThings[ $iIndex ][0] == T_VARIABLE )
					continue;
				if ( ! function_exists( $aThings[ $iIndex ][ 1 ] ) )
					$sReturn .= $aThings[ $iIndex ][ 1 ] . ' does not exist, ';
			}
	    }
	    return $sReturn;
	}

}

?>
