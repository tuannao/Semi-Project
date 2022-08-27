<?php
/**
 * Sitemaps: _Sitemaps_Registry class
 *
 * Handles registering sitemap providers.
 *
 * @package 
 * @subpackage Sitemaps
 * @since 5.5.0
 */

/**
 * Class _Sitemaps_Registry.
 *
 * @since 5.5.0
 */
class _Sitemaps_Registry {
	/**
	 * Registered sitemap providers.
	 *
	 * @since 5.5.0
	 *
	 * @var _Sitemaps_Provider[] Array of registered sitemap providers.
	 */
	private $providers = array();

	/**
	 * Adds a new sitemap provider.
	 *
	 * @since 5.5.0
	 *
	 * @param string               $name     Name of the sitemap provider.
	 * @param _Sitemaps_Provider $provider Instance of a _Sitemaps_Provider.
	 * @return bool Whether the provider was added successfully.
	 */
	public function add_provider( $name, _Sitemaps_Provider $provider ) {
		if ( isset( $this->providers[ $name ] ) ) {
			return false;
		}

		/**
		 * Filters the sitemap provider before it is added.
		 *
		 * @since 5.5.0
		 *
		 * @param _Sitemaps_Provider $provider Instance of a _Sitemaps_Provider.
		 * @param string               $name     Name of the sitemap provider.
		 */
		$provider = apply_filters( '_sitemaps_add_provider', $provider, $name );
		if ( ! $provider instanceof _Sitemaps_Provider ) {
			return false;
		}

		$this->providers[ $name ] = $provider;

		return true;
	}

	/**
	 * Returns a single registered sitemap provider.
	 *
	 * @since 5.5.0
	 *
	 * @param string $name Sitemap provider name.
	 * @return _Sitemaps_Provider|null Sitemap provider if it exists, null otherwise.
	 */
	public function get_provider( $name ) {
		if ( ! isset( $this->providers[ $name ] ) ) {
			return null;
		}

		return $this->providers[ $name ];
	}

	/**
	 * Returns all registered sitemap providers.
	 *
	 * @since 5.5.0
	 *
	 * @return _Sitemaps_Provider[] Array of sitemap providers.
	 */
	public function get_providers() {
		return $this->providers;
	}
}
