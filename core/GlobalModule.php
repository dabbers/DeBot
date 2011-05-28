<?php

/**
 * An abstract class to be implemented on modules not bound to a bot
 * @author ss23 <ss23@ss23.geek.nz>
 */

/**
 * The class
 */
abstract class GlobalModule {

		/**
		 * Array of timers that have been assigned
		 */
		private $m_aTimers;

	/**
	 * Do any sort of set-up we need here
	 */
	final public function __construct() {

	}

		/**
		 * Do our destruction commands here
		 */
		final public function __destruct( )
		{
			if (!empty($this -> m_aTimers) && is_array($this -> m_aTimers))
			{
				// Remove all the assigned timers
				$count = 0;
				foreach ( $this -> m_aTimers as $id )
				{
						if ( Timers :: obj( ) -> isTimer( $id) )
						{
								Timers :: obj( ) -> delTimer( $id);
								$count++;
						}
				}
				//echo "Module had $count timers still loaded";
			}
		}

	/**
	 * Hook for modules when they're loaded
	 */
	public function init() {

	}

	/**
	 * Hook for modules when they're unloaded
	 */
	public function unload() {

	}

		/**
		 * Add a timer (these are removed on object destruction)
		 *
		 * @param float   $fSeconds  Seconds between ticks.
		 * @param integer $iRepeat   Times to repeat. Use -1 for forever
		 * @param object  $oFunction The object function array(class, 'function' )
		 * @param array   $aParams   The params to send to teh function/method call
		 *
		 * @return string The ID of the Timer
		 */
		protected function addTimer( $fSeconds, $iRepeat, $oFunction, $aParams = null)
		{
				$id  = Timers :: obj( ) -> addTimer( $this -> m_sNick, $fSeconds, $iRepeat, $oFunction, $aParams);
				$this -> m_aTimers[ ] = $id;
				return $id;
		}

		/**
		 * Remove a timer
		 *
		 * @param string $ID Timer to delete
		 *
		 * @return void
		 */
		protected function delTimer( $id )
		{
				if ( isset( $this -> m_aTimers[ $id] ) )
						Timers :: obj( ) -> delTimer( $id);
		}

}
