<?php
/**
 * Loads the  environment and template.
 *
 * @package 
 */

if ( ! isset( $_did_header ) ) {

	$_did_header = true;

	// Load the  library.
	require_once __DIR__ . '/load.php';

	// Set up the  query.
	();

	// Load the theme template.
	require_once ABSPATH . INC . '/template-loader.php';

}
