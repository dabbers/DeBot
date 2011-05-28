<?php

/**
 * Holds all the loaded global modules.
 * @author ss23 <ss23@ss23.geek.nz>
 */

class GlobalModules extends Singleton implements ArrayAccess
{
	/**
	 * This variable stores all the modules.
	 * @var Modules[]
	 */
	private $m_aModules;

	/**
	 * Our construction function. Loads the bot modules to load,
	 * and loads them.
	 *
	 * @param String $sNick The nickname of the bot we are loading for
	 * @return null Nothing to return.
	 */

	public function __construct( )
	{
		$aModules = Config::obj( )->offsetGet( 'GlobalModules' );

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

		$sClass = 'm_' . $sModule;

		if ( ! class_exists( $sClass ) )
		{
			if ( ! file_exists( 'Modules/' . trim( $sModule ) . '.php' ) )
			{
				// LogFile
				//var_dump( 'Modules/' . $sModule . '.php' );

				Logs::obj()->addLog( 'Cannot load ' . $sModule . ' for a module!', true );
				return false;
			}

			include 'Modules/' . $sModule . '.php';
		}

		$this -> m_aModules[ $sModule ] = new $sClass( );
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
	 * This method reloads a module from the disk. Assuming you have runkit
	 * installed (Required!)
	 *
	 * @param String $sModule The name of the module you want to reload
	 * @return Boolean True on success false on failure
	 *
	 */
	public function reload( $sModule )
	{
		if ( ! isset( $this -> m_aModules[ $sModule ] ) )
			return false;

		if ( ! function_exists( 'runkit_import' ) )
			return false;

		return runkit_import( 'Modules/' . $sModule . '.php' );
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
		if (!empty($this -> m_aModules) && is_array($this -> m_aModules))
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
