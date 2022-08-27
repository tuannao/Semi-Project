<?php
/**
 * Sitemaps: Public functions
 *
 * This file contains a variety of public functions developers can use to interact with
 * the XML Sitemaps API.
 *
 * @package 
 * @subpackage Sitemaps
 * @since 5.5.0
 */

/**
 * Retrieves the current Sitemaps server instance.
 *
 * @since 5.5.0
 *
 * @global _Sitemaps $_sitemaps Global Core Sitemaps instance.
 *
 * @return _Sitemaps Sitemaps instance.
 */
function _sitemaps_get_server() {
	global $_sitemaps;

	// If there isn't a global instance, set and bootstrap the sitemaps system.
	if ( empty( $_sitemaps ) ) {
		$_sitemaps = new _Sitemaps();
		$_sitemaps->init();

		/**
		 * Fires when initializing the Sitemaps object.
		 *
		 * Additional sitemaps should be registered on this hook.
		 *
		 * @since 5.5.0
		 *
		 * @param _Sitemaps $_sitemaps Sitemaps object.
		 */
		do_action( '_sitemaps_init', $_sitemaps );
	}

	return $_sitemaps;
}

/**
 * Gets an array of sitemap providers.
 *
 * @since 5.5.0
 *
 * @return _Sitemaps_Provider[] Array of sitemap providers.
 */
function _get_sitemap_providers() {
	$sitemaps = _sitemaps_get_server();

	return $sitemaps->registry->get_providers();
}

/**
 * Registers a new sitemap provider.
 *
 * @since 5.5.0
 *
 * @param string               $name     Unique name for the sitemap provider.
 * @param _Sitemaps_Provider $provider The `Sitemaps_Provider` instance implementing the sitemap.
 * @return bool Whether the sitemap was added.
 */
function _register_sitemap_provider( $name, _Sitemaps_Provider $provider ) {
	$sitemaps = _sitemaps_get_server();

	return $sitemaps->registry->add_provider( $name, $provider );
}

/**
 * Gets the maximum number of URLs for a sitemap.
 *
 * @since 5.5.0
 *
 * @param string $object_type Object type for sitemap to be filtered (e.g. 'post', 'term', 'user').
 * @return int The maximum number of URLs.
 */
function _sitemaps_get_max_urls( $object_type ) {
	/**
	 * Filters the maximum number of URLs displayed on a sitemap.
	 *
	 * @since 5.5.0
	 *
	 * @param int    $max_urls    The maximum number of URLs included in a sitemap. Default 2000.
	 * @param string $object_type Object type for sitemap to be filtered (e.g. 'post', 'term', 'user').
	 */
	return apply_filters( '_sitemaps_max_urls', 2000, $object_type );
}

/**
 * Retrieves the full URL for a sitemap.
 *
 * @since 5.5.1
 *
 * @param string $name         The sitemap name.
 * @param string $subtype_name The sitemap subtype name. Default empty string.
 * @param int    $page         The page of the sitemap. Default 1.
 * @return string|false The sitemap URL or false if the sitemap doesn't exist.
 */
function get_sitemap_url( $name, $subtype_name = '', $page = 1 ) {
	$sitemaps = _sitemaps_get_server();

	if ( ! $sitemaps ) {
		return false;
	}

	if ( 'index' === $name ) {
		return $sitemaps->index->get_index_url();
	}

	$provider = $sitemaps->registry->get_provider( $name );
	if ( ! $provider ) {
		return false;
	}

	if ( $subtype_name && ! in_array( $subtype_name, array_keys( $provider->get_object_subtypes() ), true ) ) {
		return false;
	}

	$page = absint( $page );
	if ( 0 >= $page ) {
		$page = 1;
	}

	return $provider->get_sitemap_url( $subtype_name, $page );
}