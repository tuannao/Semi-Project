<?php
/**
 * Bootstrap file for setting the ABSPATH constant
 * and loading the config.php file. The config.php
 * file will then load the settings.php file, which
 * will then set up the  environment.
 *
 * If the config.php file is not found then an error
 * will be displayed asking the visitor to set up the
 * config.php file.
 *
 * Will also search for config.php in ' parent
 * directory to allow the  directory to remain
 * untouched.
 *
 * @package 
 */

/** Define ABSPATH as this file's directory */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/*
 * The error_reporting() function can be disabled in php.ini. On systems where that is the case,
 * it's best to add a dummy function to the config.php file, but as this call to the function
 * is run prior to config.php loading, it is wrapped in a function_exists() check.
 */
if ( function_exists( 'error_reporting' ) ) {
	/*
	 * Initialize error reporting to a known set of levels.
	 *
	 * This will be adapted in _debug_mode() located in includes/load.php based on _DEBUG.
	 * @see http://php.net/manual/en/errorfunc.constants.php List of known error levels.
	 */
	error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
}

/*
 * If config.php exists in the  root, or if it exists in the root and settings.php
 * doesn't, load config.php. The secondary check for settings.php has the added benefit
 * of avoiding cases where the current directory is a nested installation, e.g. / is (a)
 * and /blog/ is (b).
 *
 * If neither set of conditions is true, initiate loading the setup process.
 */
if ( file_exists( ABSPATH . 'config.php' ) ) {

	/** The config file resides in ABSPATH */
	require_once ABSPATH . 'config.php';

} elseif ( @file_exists( dirname( ABSPATH ) . '/config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/settings.php' ) ) {

	/** The config file resides one level above ABSPATH but is not part of another installation */
	require_once dirname( ABSPATH ) . '/config.php';

} else {

	// A config file doesn't exist.

	define( 'INC', 'includes' );
	require_once ABSPATH . INC . '/load.php';

	// Standardize $_SERVER variables across setups.
	_fix_server_vars();

	require_once ABSPATH . INC . '/functions.php';

	$path = _guess_url() . '/admin/setup-config.php';

	/*
	 * We're going to redirect to setup-config.php. While this shouldn't result
	 * in an infinite loop, that's a silly thing to assume, don't you think? If
	 * we're traveling in circles, our last-ditch effort is "Need more help?"
	 */
	if ( false === strpos( $_SERVER['REQUEST_URI'], 'setup-config' ) ) {
		header( 'Location: ' . $path );
		exit;
	}

	define( '_CONTENT_DIR', ABSPATH . 'content' );
	require_once ABSPATH . INC . '/version.php';

	_check_php_mysql_versions();
	_load_translations_early();

	// Die with an error message.
	$die = '<p>' . sprintf(
		/* translators: %s: config.php */
		__( "There doesn't seem to be a %s file. I need this before we can get started." ),
		'<code>config.php</code>'
	) . '</p>';
	$die .= '<p>' . sprintf(
		/* translators: 1: Documentation URL, 2: config.php */
		__( 'Need more help? <a href="%1$s">Read the support article on %2$s</a>.' ),
		__( 'https://.org/support/article/editing-config-php/' ),
		'<code>config.php</code>'
	) . '</p>';
	$die .= '<p>' . sprintf(
		/* translators: %s: config.php */
		__( "You can create a %s file through a web interface, but this doesn't work for all server setups. The safest way is to manually create the file." ),
		'<code>config.php</code>'
	) . '</p>';
	$die .= '<p><a href="' . $path . '" class="button button-large">' . __( 'Create a Configuration File' ) . '</a></p>';

	_die( $die, __( ' &rsaquo; Error' ) );
}
