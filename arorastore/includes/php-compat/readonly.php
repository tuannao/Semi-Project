<?php
/**
 * Conditionally declares a `readonly()` function, which was renamed
 * to `_readonly()` in  5.9.0.
 *
 * In order to avoid PHP parser errors, this function was extracted
 * to this separate file and is only included conditionally on PHP 8.1.
 *
 * Including this file on PHP >= 8.1 results in a fatal error.
 *
 * @package 
 * @since 5.9.0
 */

/**
 * Outputs the HTML readonly attribute.
 *
 * Compares the first two arguments and if identical marks as readonly.
 *
 * This function is deprecated, and cannot be used on PHP >= 8.1.
 *
 * @since 4.9.0
 * @deprecated 5.9.0 Use _readonly() introduced in 5.9.0.
 *
 * @see _readonly()
 *
 * @param mixed $readonly One of the values to compare.
 * @param mixed $current  Optional. The other value to compare if not just true.
 *                        Default true.
 * @param bool  $echo     Optional. Whether to echo or just return the string.
 *                        Default true.
 * @return string HTML attribute or empty string.
 */
function readonly( $readonly, $current = true, $echo = true ) {
	_deprecated_function( __FUNCTION__, '5.9.0', '_readonly()' );
	return _readonly( $readonly, $current, $echo );
}
