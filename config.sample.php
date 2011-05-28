<?php

/**
 * Please copy this file to config.php when you are using it.
 */


// RawEval Module Constants
define( 'AUTH_CHAN', '#dab.beta' ); // This is so if someone leaves chan, they are logged out

// Cmd Module Constants
define( 'CMD', '.' ); // For the Cmds module, what all commands start with
define( 'CMD_RAW', '..' ); // What the raw command starts with
define( 'CMD_ERROR', '10(5Error10)' ); // Used for echoing error
define( 'CMD_SUCCESS', '10(3Success10)' ); // Used for echoing success

// Log Module Constants
define( 'LOGS_DEBUG', true ); // For the log module later

// This is a failsafe if your PHP is configured incorrectly.
define( 'TIME_ZONE', 'America/Los_Angeles' );

/*
 This config array is really for configuring Servers and Bots
 Not so much modules. So that is why most module settings
 are constants and not part of this array. I wasn't sure why
 I do it this way I just decided it was easier. I think its my
 laziness kicking in. xD
*/
$aConfig = array
(
	/**
	 * This modules array loads in modules that are to be run seperate from bots.
	 * This way we can keep things running even if the bots aren't even running
	 * aren't working atm.
	 */

	// Increase this if you think DeBot is using too much CPU
	'TickRate' => 5000,

	'GlobalModules' => array('DynamicTick'),

	'Modules' => array
	(
		// 'remote'
	),

	'Servers' => array
	(
		'dab-media' => array
		(
			'server'	=> 'irc.dab.biz',
			'port'		=> 6667,
			'ssl'		=> false,
		),

		'ffs' => array
		(
			'server'	=> 'irc.ffsnetwork.com',
			'port'		=> 6697,
			'ssl'		=> true,
		),

		'freenode' => array
		(
			'server'	=> 'irc.freenode.org',
			'port'		=> 6667,
			'ssl'		=> false,
		),

		'gtanet' => array
		(
			'server'	=> 'irc.gtanet.com',
			'port'		=> 6697,
			'ssl'		=> true,
		),

		'rizon' => array
		(
			'server'	=> 'irc.rizon.net',
			'port'		=> 6697,
			'ssl'		=> true,
		),
	),
	// End ['Servers']

	'Default' => array
	(
		'ident'		=> 'dabitp',
		'real'		=> 'dab\'s Bot',
		'defBot'	=> false,
		'useThread'	=> true,
		'networks'	=> array
		(
			// Network => Options
			'dab-media'=> array
			(
				'bind' => null,
				// onConnect for this dab-Media network
				'onConnect' => array
				(
					'',
					//'JOIN #thebotnet,#knowledgesutra,#dryrid',
				),
			),
		),
		'Modules'	=> array
		(
			'RawEval', // For root bots, raw evaulating PHP
			'Cmds',
		),

		// General onConnect for all networks
		'onConnect' => array
		(
			//'JOIN #dab',  //The previous method was just too much work
			'JOIN #dab,#dab.beta',
			//'NS IDENTIFY PASSWORD',
		),
	),
	// End ['Default']

	'Bots' => array
	(
		'DyBot' => array
		(
			'ident'		=> 'dabitp',
			'real'		=> 'Dyvid Botajas (dab\'s bot)',
			'defBot'	=> true, // Only one bot can be set as the default
			'networks'	=> array
			(
				// Network => Options
				'dab-media' => array
				(
					'bind' => null,
					'onConnect' => array
					(
						'JOIN #test',
					),
				),

				'rizon' => array
				(
					'bind' => null,
					'onConnect' => array
					(
						'JOIN #news',
					),
				),
			),

			// General onConnect for all networks
			'onConnect'	=> array
			(
				'JOIN #dab.beta',
				'NS IDENTIFY Password',
			),

			'Modules'	=> array
			(
				'RawEval',
				'Cmds',
			),
		),
		// End ['DeBot']

		/**
		 * Uncomment the following code if you would like another bot
		 */

		/*
                'DyBot2' => array
                (
                        'ident'         => 'dabitp',
                        'real'          => 'Dyvid Botajas (dab\'s bot)',
                        'defBot'        => false, // Only one bot can be set as the default
                        'networks'      => array
                        (
                                // Network => Options
                                'dab-media' => array
                                (
                                        'bind' => null,
                                        'onConnect' => array
                                        (
                                                'JOIN #test',
                                        ),
                                ),

                                'rizon' => array
                                (
                                        'bind' => null,
                                        'onConnect' => array
                                        (
                                                'JOIN #news',
                                        ),
                                ),
                        ),

                        // General onConnect for all networks
                        'onConnect'     => array
                        (
                                'JOIN #dab.beta',
                                'NS IDENTIFY Password',
                        ),

                        'Modules'       => array
                        (
                                'RawEval',
                                'Cmds',
                        ),
                ),
                // End ['DeBot2']

		*/
	),
	// End ['Bots']

	'Users' => array
	(
		// You almost certainly want to restrict this to yourself, or at least your ident.
		'*!*@*' => array
		(
			'Level'	=> 5,
			// Use a site like http://www.adamek.biz/md5-generator.php to generate a MD5 hash to put here.
			'Pass'	=> 'dc647eb65e6711e155375218212b3964', // This is the hash for 'Password'
		),

		'*!~foobar@*.foo.bar' => array
		(
                        'Level' => 5,
                        // Use a site like http://www.adamek.biz/md5-generator.php to generate a MD5 hash to put here.
                        'Pass'  => 'dc647eb65e6711e155375218212b3964', // This is the hash for 'Password'
		),
	),
	// End ['Users']
); // End $aConfig

?>
