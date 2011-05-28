<?php

class BufferInput implements ArrayAccess
{
	private $m_sValue;
	private $m_aValues = array( );
	
	
	/**
	 * We pass the line we wish to "parse". This merely explodes by space.
	 * This is used for
	 */
	public function __construct( $sLine )
	{
		$this->m_sValue = $sLine;
		$this->m_aValues = explode( ' ', $sLine );
	}
	
	/**
	 * This function gets the command from the line. This way we can use it
	 * simply across the bot. Default is 3, since this is where the "command"
	 * would be for most of the messages sent via PRIVMSG and NOTICE. The 2nd
	 * param is to start at x characters ahead. Since PRIVMSG and NOTICE commands
	 * start with : on the message, the line would be :!command, so the 2nd
	 * param prevents the need removing it :)
	 *
	 * @param Optional|Integer $iIndex The index of the string array
	 * @param Optional|Integer $iSubStr The position to start on the word(command)
	 *
	 * @return String The string command
	 */
	public function command( $iIndex = 3, $iSubStr = 1 )
	{
		if ( ! isset( $this->m_aValues[ $iIndex ] ) )
			return '';
		
		if ( $iSubStr < strlen( $this->m_aValues[ $iIndex ] ) )
			return '';
	

		return substr( $this->m_aValues[ $iIndex ], $iSubStr );
	}
	
		
	/**
	 * This function fetches the array info from point iStart to point iEnd.
	 * If no end is mentioned, it goes to the last array item.
	 *
	 * @param integer $iStart The Position in the word array to start at
	 * @param Optional|integer $iEnd The position in the word array to stop at
	 *
	 * return string The word/phrase requested
	 */
	public function _( $iStart, $iEnd = null)
	{
		if ( $iStart > $iEnd && $iEnd != null )
			return $this->m_sValue;

		if ( $iEnd != null && $iEnd <= count( $this->m_aValues ) )
			return implode( ' ', array_slice( $this->m_aValues, $iStart, $iStart-$iEnd ) );
		else
			return implode( ' ', array_slice( $this->m_aValues, $iStart ) );
	}
	
	public function _a( $iStart, $iEnd = null)
	{
		if ( $iStart > $iEnd && $iEnd != null )
			return $this->m_sValue;

		if ( $iEnd != null && $iEnd <= count( $this->m_aValues ) )
			return array_slice( $this->m_aValues, $iStart, $iStart-$iEnd );
		else
			return array_slice( $this->m_aValues, $iStart );
	}
	
	/**
	 * This function fetches the array info from point iStart to point iEnd.
	 * If no end is mentioned, it goes to the last array item.
	 *
	 * @param Optional|integer $iStart The Position in the word array to start at
	 * @param Optional|integer $iEnd The position in the word array to stop at
	 *
	 * return string The word/phrase requested
	 */
	public function param( $iStart = 0, $iEnd = null )
	{
		return $this->_( $iStart, $iEnd );
	}
	
	public function count( )
	{
		return count( $this->m_aValues );
	}
	
	public function ex( )
	{
		return $this->m_aValues;
	}
	
	
	
	/**
	 * We can just access the variable to get the word at position $sKey.
	 * We can use this instead of param or _ if we only want/need 1 word.
	 *
	 * @param integer $sKey The key index to fetch
	 * @return string The string requested (Assuming it exists)
	 */
	public function offsetGet( $sKey )
	{
		if ( ! is_numeric( $sKey ) || ! isset( $this->m_aValues[ $sKey ] ) )
			return -1;
	
		return $this->m_aValues[ $sKey ];
	}
	
	/**
	 * Um, we don't want to allow the change of any position.
	 * This is tampering which is not a happy activity :()
	 * @return null;
	 */
	public function offsetSet( $skey, $svalu )
	{
		return ;
	}
	
	public function set( $sNew )
	{
		$this->m_sValue = $sNew;
		$this->m_aValues = explode( ' ', $sNew );
	}
	
	
	/**
	 * Does this key exist? Used if we were to do isset( $test[ 5 ] ), assuming $test
	 * is an instance of this Class.
	 * @param integer $sKey THe key to check
	 * @return boolean
	 */
	public function offsetExists( $sKey )
	{
		if ( isset( $this->m_aValues[ $sKey ] ) )
		{
			return true;
		}
		else
			return false;
	}
	
	/**
	 * We don't allow unset, since this could remove precious data.
	 *
	 * @return null
	 */
	public function offsetUnset( $sKey )
	{
		return ;
	}
	
	
	/**
	 * We can get the whole value if we need the whole line. :D
	 *
	 * return string The created value
	 */
	public function __toString( )
	{
		return $this->m_sValue;
	}
	 
}
/*
$oRes = new Test( ':dab!dabitp@hub.dab.biz PRIVMSG #dab :!define This is not the end = ok?' );

echo $oRes . "\n";

echo $oRes[ 1 ] . "\n";
echo $oRes[ 2 ] . "\n";

echo $oRes->_(4) . "\n";

echo $oRes->_(4,100);
echo "\n\n\n";
*/
?>