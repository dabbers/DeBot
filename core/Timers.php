<?php

/**
 * DeBot Core - Timers
 * Created by dab ??? ?? 2009
 * Last Edited: Aug 15 2010
 *
 * We maintain a time based
 *
 * @author David (dab) <dabitp@gmail.com>
 * @version v1.0
*/
class Timers extends Singleton // implements ArrayAccess
{

	/**
	 * The timers we are storing
	 * @var array an array of timers
	 */
	private $m_aTimers;

	/**
	 * Add a timer to be used.
	 * @param string  $sNick     Owner of the timer (bot)
	 * @param float   $fSeconds  Seconds between ticks.
	 * @param integer $iRepeat   Times to repeat. Use -1 for forever
	 * @param object  $oFunction The object function array(class, 'function' )
	 * @param array   $aParams   The params to send to teh function/method call
	 *
	 * @return string The ID of the Timer
	 */
	public function addTimer( $sNick, $fSeconds, $iRepeat, $oFunction, $aParams = null)
	{
		$iUID = substr( md5( uniqid( ) ), 3, 6 );

		$this -> m_aTimers [ $iUID ] [ 'owner' ] = $sNick;
		$this -> m_aTimers [ $iUID ] [ 'tick' ] = microtime( true );
		$this -> m_aTimers [ $iUID ] [ 'seconds' ] = $fSeconds;
		$this -> m_aTimers [ $iUID ] [ 'repeat' ] = $iRepeat;
		$this -> m_aTimers [ $iUID ] [ 'function' ] = $oFunction;
		if ( $aParams != null )
		{
			if ( is_array( $aParams ) )
				$this -> m_aTimers [ $iUID ] [ 'fparams' ] = $aParams;
			else
				$this -> m_aTimers[ $iUID ][ 'fparams' ] = array( $aParams );
		}

		return $iUID;
	}

	/**
	 * Delete a timer (Duh?)
	 *
	 * @param string $iUID The ID of the timer to remove
	 */
	public function delTimer( $iUID )
	{

		if ( isset( $this -> m_aTimers [ $iUID ] ) )
			unset( $this -> m_aTimers [ $iUID ] );

	}

	/**
	 * How much time is left till the current timer ticks over?
	 */
	public function timeLeft( $iUID )
	{
		if ( isset ( $this -> m_aTimers [ $iUID ] ) )
		{
			return ( $this -> m_aTimers[ $iUID ][ 'tick' ] - ( microtime( true ) - $this -> m_aTimers[ $iUID ][ 'seconds' ] ) );
		}
		return false;
	}

	/**
	 * @return bool Does the timer exist?
	 */
	public function isTimer ( $iUID )
	{
		return ( isset ( $this -> m_aTimers [ $iUID ] ) );
	}

	/**
	 * TICK TOCK TICK TOCK. We check for timers to update.
	 * We update our $this->m_iTime and loop through our timers,
	 * ensure we are to tick. Then ExECUTE OUR FUNCTIONS!
	 * Sorry... 3:28 am... really loopy and tired.
	 *
	 * As of now, the tick rate is only affected by the global
	 * tick rate (the usleep value in the main loop. Dynamic
	 * rate adjustment is an option. For now, we'll set a high
	 * default. TICK ACCURACY++;
	 */
	public function tick( )
	{
		if ( count( $this -> m_aTimers ) > 0 )
		{
			foreach( $this -> m_aTimers as $iUID => $aTInfo )
			{

//				var_dump($this -> m_aTimers[ $iUID ][ 'tick' ] );
//				var_dump(( microtime( true ) - $this -> m_aTimers[ $iUID ][ 'seconds' ] ));
//				var_dump($this -> m_aTimers[ $iUID ][ 'seconds' ]);
//				var_dump(microtime( true ));
//				die();
				if ($this -> m_aTimers[ $iUID ][ 'tick' ] <= ( microtime( true ) - $this -> m_aTimers[ $iUID ][ 'seconds' ] ) )
				{
					if ( $aTInfo[ 'repeat' ] >= 1 )
						$this -> m_aTimers[ $iUID ][ 'repeat' ]--;

					if ( isset( $aTInfo[ 'fparams' ] ) )
						call_user_func_array( $aTInfo[ 'function' ], $aTInfo[ 'fparams' ] );
					else
						call_user_func_array( $aTInfo[ 'function' ], array() );

					if ( $aTInfo[ 'repeat' ] == 0 )
					{
						$this -> delTimer( $iUID );
					}
					else
					{
						$this -> m_aTimers[ $iUID ][ 'tick' ] = microtime( true );
					}
				}
			}
		}
	}

/*
	public function offsetExists( $sKey )
	{
		if ( isset( $this -> m_aServerChannels[ $sName ] ) )
			return true;
	}

	public function offsetGet( $sKey )
	{


	} // End offsetGet Function

	public function offsetSet( $sKey, $mValue )
	{
		return ;
	}
	public function offsetUnset( $sKey )
	{
		return ;
	}
*/

}
?>
