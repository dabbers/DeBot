<?php

/**
 * DeBot Core - Singleton
 * Created by dab ??? ?? 2009
 * Last Edited: Aug 15 2010
 *
 * This class lets us makes all of the classes available anywhere via a
 * static call. Also makes sure an object isn't declared more than once.
 * Utilizes the get_called_class function only available in PHP 5.3+.
 *
 * Sorry non 5.3-ers :(
 *
 * @author David (dab) <dabitp@gmail.com>
 * @version v1.0
*/
class Singleton
{
	/**
	 * Our objects are stored here (The object instances)
	 *
	 * @var array
	 */
	private static $m_aObjs;

	private function __construct( )
	{

	}

	/**
	 * Use this to fetch the object of the er... object? It's 3am. I can't
	 * think of the correct terms...
	 *
	 * @return object The object from the class
	 */
	public static function obj( )
	{
		$for = get_called_class( );

		if ( !isset( self :: $m_aObjs[ $for ] ) )
			self :: $m_aObjs[ $for ] = new $for ( );

		return self :: $m_aObjs[ $for ];
	}

	public function __clone( )
	{
		trigger_error('Cloning doesn\'t exist fool.', E_USER_ERROR);
	}

	public function __wakeup( )
	{
		trigger_error( 'Class isn\'t asleep.', E_USER_ERROR );
	}

}

?>
