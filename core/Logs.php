<?php
/**
 * DeBot Core - Logs
 * Created by dab Jul ?? 2010
 * Last Edited: Aug 14 2010
 *
 * Handles the config, and allows for a global access through singleton (Static).
 *
 * @author David (dab) <dabitp@gmail.com>
 * @version v1.0
 */

class Logs extends Singleton
{
	/**
	 * A path to the logs
	 *
	 * @var string The path to the log directories. RELATIVE, not absolute
	 */
	private $m_sLogPath;

	/**
	 * The current date for writing logs
	 *
	 * @var string The date in string format
	 */
	private $m_sLogDate;

	/**
	 * The handle for the log file
	 *
	 * @var object The log handle
	 */
	public $m_oLogHandle;

	/**
	 * The handle for the error log
	 * @var object the error handle
	 */
	public $m_oErrorHandle;

	/**
	 * Er, it was to be used for sending to a channel.... but I don't need it anymore
	 *
	 * @var object Where to send? (Bot object)
	 * @deprecated since v1.0
	 */
	public $m_oLogTo = '';

	/**
	 * Er, it was to be used for sending to a channel.... but I don't need it anymore
	 *
	 * @var object Where to send? (Bot object)
	 * @deprecated since v1.0
	 */
	public $m_oToObj = null;

	/**
	 * Initiate our Logs object
	 *
	 */
	public function load( )
	{
		$this->m_sLogPath = BOT_PATH . '/Logs/';
		$this->m_sLogDate = date( 'Y-m-d' );

		// Open the log
		$this->m_oLogHandle = fopen
		(
			$this->m_sLogPath . $this->m_sLogDate . '.txt',
			'a'
		);
		// Open the error log
		$this->m_oErrorHandle = fopen
		(
			$this->m_sLogPath . 'error.txt',
			'a'
		);

		Timers::obj()->addTimer( '', 60, 31556926, array( $this, 'updateLogHandle' ) );
		//	 1 year of seconds. If you get your bot to run this long, I applaud you
	}

	/**
	 * Add a new line to our logs.
	 *
	 * @param string $sLine The message to add
	 * @param boolean $bError Is this message an error?
	 * @return boolean
	 */
	public function addLog( $sLine, $bError = false )
	{
		$aFrom = debug_backtrace( );
		preg_match
		(
			'#^[A-Z:]{0,2}[\\\/].*[\\\/](.*?)[\\\/]([^\/\ \(\)]+)#',
			$aFrom[ 0 ][ 'file' ],
			$aM
		);

		$sFrom = ( $aM[ 1 ] == 'Modules' ? 'Modules->' : '' ) . $aM[ 2 ];
		fwrite
		(
			$this->m_oLogHandle,
			'['. date( 'H:i:s' ) .'] (' . $sFrom . ') ' . trim($sLine) . "\n"
		);

		if ( $bError )
		{
			fwrite
			(
				$this->m_oErrorHandle,
				'['. date( 'Y-m-d H:i:s' ) .'] (' . $sFrom . ') ' . trim($sLine) . "\n"
			);
		}
		return true;
	}

	/**
	 * Adds a debug line to the logs, assuming debug is enabled in the config
	 *
	 * @param string $sLine The line to add
	 *
	 * @param boolean The result
	 */
	public function addDebug( $sLine )
	{

		if ( ! LOGS_DEBUG )
			return false;

		$aFrom = debug_backtrace( );
		preg_match
		(
			'#^[A-Z:]{0,2}[\\\/].*[\\\/](.*?)[\\\/]([^\/\ \(\)]+)#',
			$aFrom[ 0 ][ 'file' ],
			$aM
		);

		$sFrom = ( $aM[ 1 ] == 'Modules' ? 'Modules->' : '' ) . $aM[ 2 ];

		fwrite
		(
			$this->m_oLogHandle,
			'['. date( 'H:i:s' ) .'] Debug: (' . $sFrom . ') ' . trim($sLine) . "\n"
		);

		return true;
	}

	/**
	 * This occurs every so often (seconds) to update the file we are writing
	 * to if the time of day changes (midnight).
	 *
	 */
	public function updateLogHandle( )
	{
		if ( date( 'Y-m-d' ) != $this->m_sLogDate )
		{
			$this->m_sLogDate = date( 'Y-m-d' );

			fclose( $this->m_oLogHandle );

			$this->m_oLogHandle = fopen
			(
				$this->m_sLogPath . $this->m_sLogDate . '.txt',
				'a'
			);

			$this->addLog( 'Moved to a new log file date.' );
		}
	}


	/**
	 * This function is called by the Error handler of PHP.
	 *
	 * I don't feel like exlaining the parameters.
	 */
	public function onError( $iError, $sError, $sFile, $iLine )
	{

		// ss23 made a patch, dab is a noob :3 - http://php.net/set_error_handler
		if (ini_get('error_reporting') == 0) {
			// This happens when the user is supressing the error.
			// In our case, we can now return and ignore this ever happened.
			return true;
		}

		$sErrorLine = '';

		switch ( $iError )
		{
			case E_WARNING:			$sErrorLine = '[Warning] ';		break;
			case E_USER_WARNING:	$sErrorLine = '[Warning] ';		break;
			case E_NOTICE:			$sErrorLine = '[Notice] ';		break;
			case E_ERROR:			$sErrorLine = '[Error] ';		break;
			case E_USER_NOTICE:		$sErrorLine = '[Notice] ';		break;
			case E_DEPRECATED:		$sErrorLine = '[Deprecated] ';	break;
			case E_USER_DEPRECATED:	$sErrorLine = '[Deprecated] ';	break;
		}
		$sErrorLine .= $sError . ' at ' . $sFile. ':' . $iLine;

		echo '['. date( 'H:i:s' ) .']' . $sErrorLine ."\n";
		$this->addLog( $sErrorLine, true );
		/*
		if ( $this->m_oToObj != null && $this->m_oToObj instanceof Bot )
		{
			$this->m_oToObj->raw( 'PRIVMSG ' . $this->m_oLogTo . ' :' . $sErrorLine );
		}
		*/

		return true;
	}

	/**
	 * Called on fatal errors. I don't actually think it is called.
	 */
	public function onFatal( )
	{
		$aLast = error_get_last( );
		if ( $aLast[ 'type' ] === E_ERROR )
		{
			// fatal error
			$this->onError( E_ERROR, $aLast[ 'message' ], $aLast[ 'file' ], $aLast[ 'line' ] );
		}
	}

}

?>
