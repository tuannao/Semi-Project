<?php
/**
 * Robots template functions.
 *
 * @package 
 * @subpackage Robots
 * @since 5.7.0
 */

/**
 * Displays the robots meta tag as necessary.
 *
 * Gathers robots directives to include for the current context, using the
 * {@see '_robots'} filter. The directives are then sanitized, and the
 * robots meta tag is output if there is at least one relevant directive.
 *
 * @since 5.7.0
 * @since 5.7.1 No longer prevents specific directives to occur together.
 */
function _robots() {
	/**
	 * Filters the directives to be included in the 'robots' meta tag.
	 *
	 * The meta tag will only be included as necessary.
	 *
	 * @since 5.7.0
	 *
	 * @param array $robots Associative array of directives. Every key must be the name of the directive, and the
	 *                      corresponding value must either be a string to provide as value for the directive or a
	 *                      boolean `true` if it is a boolean directive, i.e. without a value.
	 */
	$robots = apply_filters( '_robots', array() );

	$robots_strings = array();
	foreach ( $robots as $directive => $value ) {
		if ( is_string( $value ) ) {
			// If a string value, include it as value for the directive.
			$robots_strings[] = "{$directive}:{$value}";
		} elseif ( $value ) {
			// Otherwise, include the directive if it is truthy.
			$robots_strings[] = $directive;
		}
	}

	if ( empty( $robots_strings ) ) {
		return;
	}

	echo "<meta name='robots' content='" . esc_attr( implode( ', ', $robots_strings ) ) . "' />\n";
}

/**
 * Adds `noindex` to the robots meta tag if required by the site configuration.
 *
 * If a blog is marked as not being public then noindex will be output to
 * tell web robots not to index the page content. Add this to the
 * {@see '_robots'} filter.
 *
 * Typical usage is as a {@see '_robots'} callback:
 *
 *     add_filter( '_robots', '_robots_noindex' );
 *
 * @since 5.7.0
 *
 * @see _robots_no_robots()
 *
 * @param array $robots Associative array of robots directives.
 * @return array Filtered robots directives.
 */
function _robots_noindex( array $robots ) {
	if ( ! get_option( 'blog_public' ) ) {
		return _robots_no_robots( $robots );
	}

	return $robots;
}

/**
 * Adds `noindex` to the robots meta tag for embeds.
 *
 * Typical usage is as a {@see '_robots'} callback:
 *
 *     add_filter( '_robots', '_robots_noindex_embeds' );
 *
 * @since 5.7.0
 *
 * @see _robots_no_robots()
 *
 * @param array $robots Associative array of robots directives.
 * @return array Filtered robots directives.
 */
function _robots_noindex_embeds( array $robots ) {
	if ( is_embed() ) {
		return _robots_no_robots( $robots );
	}

	return $robots;
}

/**
 * Adds `noindex` to the robots meta tag if a search is being performed.
 *
 * If a search is being performed then noindex will be output to
 * tell web robots not to index the page content. Add this to the
 * {@see '_robots'} filter.
 *
 * Typical usage is as a {@see '_robots'} callback:
 *
 *     add_filter( '_robots', '_robots_noindex_search' );
 *
 * @since 5.7.0
 *
 * @see _robots_no_robots()
 *
 * @param array $robots Associative array of robots directives.
 * @return array Filtered robots directives.
 */
function _robots_noindex_search( array $robots ) {
	if ( is_search() ) {
		return _robots_no_robots( $robots );
	}

	return $robots;
}

/**
 * Adds `noindex` to the robots meta tag.
 *
 * This directive tells web robots not to index the page content.
 *
 * Typical usage is as a {@see '_robots'} callback:
 *
 *     add_filter( '_robots', '_robots_no_robots' );
 *
 * @since 5.7.0
 *
 * @param array $robots Associative array of robots directives.
 * @return array Filtered robots directives.
 */
function _robots_no_robots( array $robots ) {
	$robots['noindex'] = true;

	if ( get_option( 'blog_public' ) ) {
		$robots['follow'] = true;
	} else {
		$robots['nofollow'] = true;
	}

	return $robots;
}

/**
 * Adds `noindex` and `noarchive` to the robots meta tag.
 *
 * This directive tells web robots not to index or archive the page content and
 * is recommended to be used for sensitive pages.
 *
 * Typical usage is as a {@see '_robots'} callback:
 *
 *     add_filter( '_robots', '_robots_sensitive_page' );
 *
 * @since 5.7.0
 *
 * @param array $robots Associative array of robots directives.
 * @return array Filtered robots directives.
 */
function _robots_sensitive_page( array $robots ) {
	$robots['noindex']   = true;
	$robots['noarchive'] = true;
	return $robots;
}

/**
 * Adds `max-image-preview:large` to the robots meta tag.
 *
 * This directive tells web robots that large image previews are allowed to be
 * displayed, e.g. in search engines, unless the blog is marked as not being public.
 *
 * Typical usage is as a {@see '_robots'} callback:
 *
 *     add_filter( '_robots', '_robots_max_image_preview_large' );
 *
 * @since 5.7.0
 *
 * @param array $robots Associative array of robots directives.
 * @return array Filtered robots directives.
 */
function _robots_max_image_preview_large( array $robots ) {
	if ( get_option( 'blog_public' ) ) {
		$robots['max-image-preview'] = 'large';
	}
	return $robots;
}
