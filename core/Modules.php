<?php

/**
 * DeBot Core - Modules
 * Created by dab ??? ?? 2009
 * Last Edited: Jul 29 2010
 * Last Edited: Aug 2 2010
 * Last Edited: Aug 15 2010
 *
 * This file maintains all the extensions to the DeBot Framework. It is actually
 * quite simple. Loads in the Module class into an array and calls callbacks
 * based on what the Bot class receives.
 *
 * @author David (dab) <dabitp@gmail.com>
 * @version v1.0
*/

class Modules implements ArrayAccess
{
	/**
	 * This variable stores all the modules.
	 * @var Modules[]
	 */
	private $m_aModules;

	/**
	 * This variable stores the Bot (Owner)'s nick.
	 * @var String
	 */
	private $m_sNick; // The Owner's nick


	/**
	 * Our construction function. Loads the bot modules to load,
	 * and loads them.
	 *
	 * @param String $sNick The nickname of the bot we are loading for
	 * @return void
	 */

	public function __construct( $oBot )
	{
		$aModules = Config::obj( )->getBot( $oBot->m_sNick );
		$aModules = $aModules[ 'Modules' ];

		$this -> m_oBot = $oBot;

		foreach( $aModules as $sModule )
		{
			$this -> load( $sModule );
		}
	}

	/**
	 * Load a module to this bot. Only takes the name, without the .php.
	 * So lets say teh Module is in /Modules/dabRules.php. You would pass
	 * dabRules to the function.
	 *
	 * @param String $sModule The name of the module to load
	 * @return Boolean (Loaded or not)
	 */
	public function load( $sModule )
	{

		if ( isset( $this -> m_aModules[ $sModule ] ) )
			return false;

		//$sClass = 'm_' . $sModule;

		//if ( ! class_exists( $sClass ) )
		//{
		if ( ! file_exists( 'Modules/' . trim( $sModule ) . '.php' ) )
		{
			Logs::obj()->addLog( 'Cannot load ' . $sModule . ' for a module! (Error A)', true );
			return false;
		}
		
		$sMod = file_get_contents( 'Modules/' . trim( $sModule ) . '.php' );
		
		if ( ! preg_match( '/(class[\s]{1,})m_'.$sModule.'([\s]{1,}extends[\s]{1,}Module[\s]{1,}\{)/', $sMod ) )
		{
			Logs::obj()->addLog( 'Cannot load ' . $sModule . ' for a module! (Error B)', true );
			return false;
		}
		
		$sId = uniqid( $sModule );
		
		$sMod = preg_replace( '/(class[\s]{1,}m_)'.$sModule.'([\s]{1,}extends[\s]{1,}Module[\s]{1,}\{)/', '${1}'.$sId.'$2', $sMod );

		
		file_put_contents( 'Modules/Temp/'.$sId.'.php', $sMod );
		unset( $sMod );

		include 'Modules/Temp/'.$sId.'.php';
		unlink( 'Modules/Temp/'.$sId.'.php' );
		//}
		$sId = 'm_' . $sId;
		$this -> m_aModules[ $sModule ] = new $sId( $this -> m_oBot );

		// As part of the new module interface, call init() for modules here
		$this -> m_aModules[ $sModule ] -> init( );

		return true;
	}

	/**
	 * We can unload a module so none of the callbacks are called.
	 * MAKE SURE TO ADD TIMER REMOVAL IN YOUR DESTRUCTS. Timer still makes
	 * calls to the modules if they made a timer callback.
	 *
	 * @param string $sModule The module name to unload
	 * @return Boolean True on success, false on failure.
	 *
	 */
	public function unload( $sModule )
	{
		if ( ! isset( $this -> m_aModules[ $sModule ] ) )
			return false;

		$this -> m_aModules[ $sModule ] -> unload( );
		$this -> m_aModules[ $sModule ] -> __destruct( );
		unset( $this -> m_aModules[ $sModule ] );

		return true;
	}

	/**
	 * This method reloads a module from the disk. Assuming ou have runkit
	 * installed (Required!)
	 *
	 * @param String $sModule The name of the module you want to reload
	 * @return Boolean True on success false on failure
	 *
	 */
	public function reload( $sModule )
	{
		$this->unload( $sModule );
		$this->load( $sModule );
	}

	/**
	 *
	 * This function (method) allows us to have "dynamic" methods in our
	 * modules. So it really just loops through the modules and checks the
	 * function exists in that module. If it does, we call it with the
	 * appropiate params.
	 *
	 * @param String $sName The name of the function
	 * @param Array $aParams The array of paramters to pass to the function
	 *
	 * @return Boolean Returns true usually, false if a module forced an end
	 *
	 */
	public function __call( $sName, $aParams )
	{
		if (!empty ( $this -> m_aModules) && is_array( $this -> m_aModules ))
		{
			foreach( $this -> m_aModules as $sMName => $mModules )
			{

				if ( method_exists( $mModules, $sName ) )
				{
					$iRes = @ call_user_func_array( array( $mModules, $sName ), $aParams );

					if ( $iRes == MOD_END )
						return false;

				}
			}
		}
		return ;
	}


	/**
	 * This returns all of the modules loaded by the bot.
	 *
	 * @return Module[] A module class in an array
	 */
	public function getModules( )
	{
		return $this->m_aModules;
	}


	public function offsetExists( $sKey )
	{
		if ( isset( $this->m_aModules[ $sKey ] ) )
			return true;

		return false;
	}

	public function offsetGet( $sKey )
	{
		if ( isset( $this -> m_aModules[ $sKey ] ) )
			return $this -> m_aModules[ $sKey ];

		return ;

	}

	public function offsetSet( $sKey, $mValue )
	{
		return ;
	}

	public function offsetUnset( $sKey )
	{
		return ;
	}

	public function __toString( )
	{
		return $this -> m_sNick . ' Module Class';
	}

}

?>
