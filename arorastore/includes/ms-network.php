<?php
/**
 * Network API
 *
 * @package 
 * @subpackage Multisite
 * @since 5.1.0
 */

/**
 * Retrieves network data given a network ID or network object.
 *
 * Network data will be cached and returned after being passed through a filter.
 * If the provided network is empty, the current network global will be used.
 *
 * @since 4.6.0
 *
 * @global _Network $current_site
 *
 * @param _Network|int|null $network Optional. Network to retrieve. Default is the current network.
 * @return _Network|null The network object or null if not found.
 */
function get_network( $network = null ) {
	global $current_site;
	if ( empty( $network ) && isset( $current_site ) ) {
		$network = $current_site;
	}

	if ( $network instanceof _Network ) {
		$_network = $network;
	} elseif ( is_object( $network ) ) {
		$_network = new _Network( $network );
	} else {
		$_network = _Network::get_instance( $network );
	}

	if ( ! $_network ) {
		return null;
	}

	/**
	 * Fires after a network is retrieved.
	 *
	 * @since 4.6.0
	 *
	 * @param _Network $_network Network data.
	 */
	$_network = apply_filters( 'get_network', $_network );

	return $_network;
}

/**
 * Retrieves a list of networks.
 *
 * @since 4.6.0
 *
 * @param string|array $args Optional. Array or string of arguments. See _Network_Query::parse_query()
 *                           for information on accepted arguments. Default empty array.
 * @return array|int List of _Network objects, a list of network IDs when 'fields' is set to 'ids',
 *                   or the number of networks when 'count' is passed as a query var.
 */
function get_networks( $args = array() ) {
	$query = new _Network_Query();

	return $query->query( $args );
}

/**
 * Removes a network from the object cache.
 *
 * @since 4.6.0
 *
 * @global bool $__suspend_cache_invalidation
 *
 * @param int|array $ids Network ID or an array of network IDs to remove from cache.
 */
function clean_network_cache( $ids ) {
	global $__suspend_cache_invalidation;

	if ( ! empty( $__suspend_cache_invalidation ) ) {
		return;
	}

	$network_ids = (array) $ids;
	_cache_delete_multiple( $network_ids, 'networks' );

	foreach ( $network_ids as $id ) {
		/**
		 * Fires immediately after a network has been removed from the object cache.
		 *
		 * @since 4.6.0
		 *
		 * @param int $id Network ID.
		 */
		do_action( 'clean_network_cache', $id );
	}

	_cache_set( 'last_changed', microtime(), 'networks' );
}

/**
 * Updates the network cache of given networks.
 *
 * Will add the networks in $networks to the cache. If network ID already exists
 * in the network cache then it will not be updated. The network is added to the
 * cache using the network group with the key using the ID of the networks.
 *
 * @since 4.6.0
 *
 * @param array $networks Array of network row objects.
 */
function update_network_cache( $networks ) {
	$data = array();
	foreach ( (array) $networks as $network ) {
		$data[ $network->id ] = $network;
	}
	_cache_add_multiple( $data, 'networks' );
}

/**
 * Adds any networks from the given IDs to the cache that do not already exist in cache.
 *
 * @since 4.6.0
 * @access private
 *
 * @see update_network_cache()
 * @global db $db  database abstraction object.
 *
 * @param array $network_ids Array of network IDs.
 */
function _prime_network_caches( $network_ids ) {
	global $db;

	$non_cached_ids = _get_non_cached_ids( $network_ids, 'networks' );
	if ( ! empty( $non_cached_ids ) ) {
		$fresh_networks = $db->get_results( sprintf( "SELECT $db->site.* FROM $db->site WHERE id IN (%s)", implode( ',', array_map( 'intval', $non_cached_ids ) ) ) ); // phpcs:ignore .DB.PreparedSQL.NotPrepared

		update_network_cache( $fresh_networks );
	}
}
