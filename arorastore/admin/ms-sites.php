<?php
/**
 * Multisite sites administration panel.
 *
 * @package 
 * @subpackage Multisite
 * @since 3.0.0
 */

require_once __DIR__ . '/admin.php';

wp_redirect( network_admin_url( 'sites.php' ) );
exit;