<?php

/**
 * A hacked up implementation of a FIFO Priority Queue until
 * http://bugs.php.net/53710 is fixed (will probably kept even after)
 *
 * @see http://bugs.php.net/53710
 * @see http://weierophinney.net/matthew/archives/253-Taming-SplPriorityQueue.html
 *
 * @author ss23 <ss23@ss23.geek.nz>
 */

/**
 * The FIFO Priority Queue class
 */
class SplFIFOPriorityQueue extends SplPriorityQueue
{
	/**
	 * The place in the queue (Note that this places
	 * an artifical (albiet high) limit on how many
	 * items can be placed in a queue per lifetime
	 */
	protected $queueOrder = PHP_INT_MAX;

	/**
	 * Insert a value into the queue
	 *
	 * @param mixed $dataum   The value to insert
	 * @param int   $priority The priority of the value
	 *
	 * @return void
	 */
	public function insert( $datum, $priority)
	{
		if ( is_int( $priority) )
		{
			$priority = array( $priority, $this -> queueOrder--);
		}
		parent :: insert( $datum, $priority);

	}
}
