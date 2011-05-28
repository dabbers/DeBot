<?php
/**
 * DeBot Main
 * Created by dab ??? ?? 2009
 * Last Edited: Jul 29 2010
 *
 * This file is created to piece everything together and initiate the bot.
 * This file is REQUIRED to run the bot.
 * Jul 18 2010 - Added log support (initiating)
 *
 * @author David (dab) <dabitp@gmail.com>
 * @version v1.0
 */

// We might as well catch all errors in dev
error_reporting( -1 );

require "core/Functions.php"; // Our Functions file. Contains small useful functions.
require "config.php";	 // The file with an array of settings to use.

// Required defines
define( 'MOD_END', -1 );
define( 'BOT_PATH', dirname( __FILE__ ) );

if (!ini_get( 'date.timezone' )) {
        date_default_timezone_set( TIME_ZONE );
}

require "core/Singleton.php"; // The class that allows the 'universal' usage of my bot class
require "core/SplFIFOPriorityQueue.php";
require "core/Config.php"; // Manage Bot Settings
require "core/Bot.php";		 // The class where all bots are managed.
require "core/Bots.php";		 // The class where all bots are managed.
require "core/Server.php";	 // This loads a socket handler.
require "core/Servers.php"; // This handles all networks
require "core/Channel.php"; // Stores Channel Info
require "core/Timers.php"; // Timer class
require "core/Modules.php"; // Stores all module info, per bot
require "core/Module.php"; // Possible callbacks in a module
require "core/GlobalModules.php";
require "core/GlobalModule.php";
require "core/Logs.php"; // Logging Clas
include "core/BufferInput.php";


Config	:: obj( )->load( $aConfig );

Logs  	:: obj( )->load( );	// Sets up the Timer Class

set_error_handler( array( Logs::obj( ), 'onError' ) );
register_shutdown_function( array( Logs::obj( ), 'onFatal' ) );

Logs::obj( )->addLog( '*************************************' );
Logs::obj( )->addLog( 'Framework initializing' );
Logs::obj( )->addLog( '*************************************' );

Servers		:: obj( )->load( ); // Load in the pre-created Servers
Bots		:: obj( )->load( ); // Loads all the bots
GlobalModules	:: obj( ); // Loads our global modules

while( 1 )
{
	Timers :: obj( ) -> tick( );
	Bots :: obj( ) -> check( );
	usleep ( Config :: obj( )->offsetGet('TickRate') );
}

?>
