<?php

function download( $sUrl )
{
	$aContext = stream_context_create
	(
		array
		(
			'http' => array
			(
				'timeout' => 3	  // Timeout in seconds
			)
		)
	);
	// Fetch the URL's contents
	return file_get_contents( $sUrl, 0, $aContext );
}


?>
