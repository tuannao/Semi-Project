<?php
/**
 * Deprecated. Use rss.php instead.
 *
 * @package 
 * @deprecated 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

_deprecated_file( basename( __FILE__ ), '2.1.0', INC . '/rss.php' );
require_once ABSPATH . INC . '/rss.php';
