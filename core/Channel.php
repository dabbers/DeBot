<?php
/**
 * DeBot Core - Channel
 * Created by dab ??? ?? 2009
 * Last Edited: Aug 14 2010
 *
 * This class stores all information about a channel. No real testing has been
 * done to detect the accuracy of it, but it is believed to be working.
 *
 * @author David (dab) <dabitp@gmail.com>
 * @version v1.0
 */
class Channel implements ArrayAccess
{
	/**
	 * The name of the owner of this Class
	 *
	 * @var string The name of the bot who owns this class
	 */
	private $m_sName;

	/**
	 * The list of users in this channel
	 *
	 * @var array Users nicks
	 */
	private $m_aUsers;

	/**
	 * The list of modes in the channel
	 *
	 * @var Array Mode list
	 */
	private $m_aModes;

	/**
	 * The topic of the channel
	 *
	 * @var string The topic of the channel
	 */
	private $m_sTopic;

	/**
	 * An array of the invites available.
	 *
	 * @var array of invites site ( +I )
	 */
	private $m_aInvites;

	/**
	 * List of ban exceptions (+e)
	 *
	 * @var array of exceptions (+e)
	 */
	private $m_aExcepts;

	/**
	 * Array of bans set on the chanenl
	 *
	 * @var array bans set on channel (+b)
	 */
	private $m_aBans;


	/**
	 * Begins the creation of this channel (onJoin)
	 *
	 * @param String $sName The nick of the owner (bot)
	 * @param mixed $mParam Array if you are creating a channel with a list of users, string if you are starting with a topic.
	 */
	public function __construct( $sName, $mParam )
	{
		$this -> m_sName = $sName;

		if ( is_array( $mParam ) )
			$this -> onNames( $mParam );
		else
			$this -> onTopic( $mParam );
	}

	/**
	 * Run this function onNames callback.
	 *
	 * @param array $aNames A list of names to place in the channel
	 */
	public function onNames( $aNames )
	{
		 foreach( $aNames as $sName )
		 {
		 	if ( preg_match( '/([\+:%@&~])([^\&]*)/', $sName, $aMatches ) )
		 	{
		 		if ( $aMatches[ 1] != ':' )
					$this -> m_aUsers[ $aMatches[ 2 ] ] = $aMatches[ 1 ];
				else
					$this -> m_aUsers[ $aMatches[ 2 ] ] = '';
		 	}
		 	else
		 	{
		 		$this -> m_aUsers[ $sName ] = '';
		 	}
		 }

	}
	
	/**
	 * Remove a user from our list.
	 *
	 * @param string $sName Nick name to remove
	 */
	public function removeNick($sName) {
		if (isset($this->m_aUsers[$sName])) {
			unset($this->m_aUsers[$sName]);
		}
	}
	
	/**
	 * Change user's nick in our list.
	 *
	 * @param string $sName Nick of user
	 * @param string $sNew New nick of user
	 */
	public function changeNick($sName, $sNew) {
		if (isset($this->m_aUsers[$sName])) {
			$uModes = $this->m_aUsers[$sName];
			unset($this->m_aUsers[$sName]);
			$this->m_aUsers[$sNew] = $uModes;
		}
	}
	
	/**
	 * Add a user to our list.
	 *
	 * @param string $sName Nick name to add
	 */
	public function addNick($sName) {
		if (!isset($this->m_aUsers[$sName])) {
			$this->m_aUsers[$sName] = "";
		}
	}

	/**
	 * The mode and the parameters used on a channel
	 *
	 * @param String $sMode The mode(s) used in a string (No spaces)
	 * @param string $sParam The strings of params (for example in bans)
	 *
	 */
	public function onMode( $sMode, $sParam )
	{
		$iModes = strlen( $sMode );
		$aParams = explode( ' ', $sParam );
		$PlusMin = '+';
		$iParam = 0;
		for( $x=0; $x < $iModes; $x++ )
		{
			$chr = $sMode[ $x ];
			if ( $chr == '+' )
			{
				$PlusMin = '+';
			}
			else if ( $chr == '-' )
			{
				$PlusMin = '-';
			}
			else
			{
				switch( $chr )
				{
					case 'q':
					case 'a':
					case 'o':
					case 'h':
					case 'v':
						if ( $PlusMin == '+' )
							if ( isset( $this -> m_aUsers[ $aParams[ $iParam ] ] ) )
								$this -> m_aUsers[ $aParams[ $iParam ] ] .= $chr;
							else
								$this -> m_aUsers[ $aParams[ $iParam ] ] = $chr;
						else
						{
							$this -> m_aUsers[ $aParams[ $iParam ] ] = str_replace
								(
									$chr,
									'',
									$this -> m_aUsers[ $aParams[ $iParam ] ]
								);
						}
						$iParam++;
						continue;
					break;
					case 'b':
						if ( $PlusMin == '+' )
							$this -> m_aBans[ $aParams[ $iParam ] ] = 1;
						else
							unset( $this -> m_aBans[ $aParams[ $iParam ] ] );
						$iParam++;
						continue;
					case 'I':
						if ( $PlusMin == '+' )
							$this -> m_aInvites[ $aParams[ $iParam ] ] = 1;
						else
							unset( $this -> m_aInvites[ $aParams[ $iParam ] ] );
						$iParam++;
						continue;
					case 'e':
						if ( $PlusMin == '+' )
							$this -> m_aExcepts[ $aParams[ $iParam ] ] = 1;
						else
							unset( $this -> m_aExcepts[ $aParams[ $iParam ] ] );
						$iParam++;
						continue;
					case 'k':
					case 'j':
					case 'f':
						if ( $PlusMin == '+' )
							$this -> m_aModes [ $chr ] = $aParams[ $iParam ];
						else
							unset( $this -> m_aModes [ $chr ] );
						$iParam++;
						continue;
					default:
						if ( $PlusMin == '+' )
							$this -> m_aModes [ $chr ] = 1;
						else
							unset( $this -> m_aModes [ $chr ] );
						continue;
				}
			}
		}
	}

	/**
	 * Set the chanenl's topic
	 *
	 * @param string $sTopic The topic of the channel to set
	 */
	public function onTopic( $sTopic )
	{
		$this -> m_sTopic = $sTopic;
	}


	public function offsetExists( $sKey )
	{
		switch( strtolower( $sKey ) )
		{
			case 'user':
			case 'users':
				return true;
			case 'mode':
			case 'modes':
			case 'chanmode':
			case 'chanmodes':
			case 'channelmodes':
				return true;
			case 'topic':
			case 'chantop':
			case 'chantopic':
				return true;
			case 'invite':
			case 'invites':
				return true;
			case 'except':
			case 'excepts':
				return true;
			case 'bans':
			case 'ban':
				return true;
			break;
			default:
				return false;
		}
	}

	public function offsetGet( $sKey )
	{

		switch( strtolower( $sKey ) )
		{
			case 'user':
			case 'users':
				return $this -> m_aUsers;
			case 'mode':
			case 'modes':
			case 'chanmode':
			case 'chanmodes':
			case 'channelmodes':
				return $this -> m_aModes;
			case 'topic':
			case 'chantop':
			case 'chantopic':
				return $this -> m_sTopic;
			case 'invite':
			case 'invites':
				return $this -> m_aInvites;
			case 'except':
			case 'excepts':
				return $this -> m_aExcepts;
			case 'bans':
			case 'ban':
				return $this -> m_aBans;
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
