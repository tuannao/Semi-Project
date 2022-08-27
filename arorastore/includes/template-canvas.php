<?php
/**
 * Template canvas file to render the current '_template'.
 *
 * @package 
 */

/*
 * Get the template HTML.
 * This needs to run before <head> so that blocks can add scripts and styles in _head().
 */
$template_html = get_the_block_template_html();
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<?php _head(); ?>
</head>

<body <?php body_class(); ?>>
<?php _body_open(); ?>

<?php echo $template_html; // phpcs:ignore .Security.EscapeOutput ?>

<?php _footer(); ?>
</body>
</html>
