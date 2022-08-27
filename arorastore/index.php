<?php
/**
 * Front to the  application. This file doesn't do anything, but loads
 * blog-header.php which does and tells  to load the theme.
 *
 * @package 
 */

/**
 * Tells  to load the  theme and output it.
 *
 * @var bool
 */
define( '_USE_THEMES', true );

/** Loads the  Environment and Template */
require __DIR__ . '/blog-header.php';
