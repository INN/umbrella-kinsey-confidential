<?php
/**
 * The theme for Kinsey Confidential
 */

//=//

/**
 * Define some constants
 */
define('KCON_RESOURCES_PARENT_ID', 999); // 
/**
 * Include theme files
 *
 * Based off of how Largo loads files: https://github.com/INN/Largo/blob/master/functions.php#L358
 *
 * 1. hook function Largo() on after_setup_theme
 * 2. function Largo() runs Largo::get_instance()
 * 3. Largo::get_instance() runs Largo::require_files()
 *
 * This function is intended to be easily copied between child themes, and for that reason is not prefixed with this child theme's normal prefix.
 *
 * @link https://github.com/INN/Largo/blob/master/functions.php#L145
 */
function largo_child_require_files() {
	$includes = array(
		'/inc/resource-page.php',
		'/inc/powerpress.php'
	);

	if ( class_exists( 'WP_CLI_Command' ) ) {
		require __DIR__ . '/inc/cli.php';
	}

	foreach ($includes as $include ) {
		require_once( get_stylesheet_directory() . $include );
	}
}
add_action( 'after_setup_theme', 'largo_child_require_files' );

/**
 * Register the homepage layout
 */
function kcon_register_homepage_layout() {
	include_once __DIR__ . '/homepages/layouts/kcon_layout.php';
	register_homepage_layout( 'KCon_Layout' );
}
add_action('init', 'kcon_register_homepage_layout', 0);

function kcon_register_topics_menu() {
  register_nav_menu( 'topics-menu', 'Topics Menu' );
}
add_action( 'init', 'kcon_register_topics_menu' );

/**
 * Enqueue theme styles and fonts
 */
function kcon_enqueue_style() {
	wp_enqueue_style( 'kcon', get_stylesheet_directory_uri() . '/css/style.css' );
	wp_dequeue_style( 'largo-child-styles' );
}
add_action( 'wp_enqueue_scripts', 'kcon_enqueue_style', 11 );

/**
 * Custom theme options, primarily used for the homepage header image background
 */
function kcon_custom_options($options) {
	// tacking this option on to the end of the theme options
	$options[] = array(
		'name' => __('Child Theme Options', 'kcon'),
		'type' => 'heading');
	$options[] = array(
		'name' => __('Homepage options', 'kcon'),
		'type' => 'info');
	$options[] = array(
		'desc' => __('<b>Homepage header image</b>: This image is used as the background image for the full-width header image. It should be at least ', 'kcon'),
		'id'   => 'home_header_full_image',
		'std'  => '',
		'type' => 'upload');
	$options[] = array(
		'desc' => __('<b>Subtitle</b>: This describes your site. <code>&lt;b&gt;</code> and <code>&lt;br /&gt;</code>tags tags are permitted.', 'kcon'),
		'id'   => 'subtitle',
		'std'  => 'With greater <b>understanding</b><br/>Comes greater <b>acceptance</b>',
		'type' => 'textarea'
	);
	$options[] = array(
		'desc' => __('<b>Description</b>: A couple of sentences describing the site\'s purpose.', 'kcon'),
		'id'   => 'site_description',
		'std'  => "At Kinsey Confidential, sex isn't taboo &mdash; It's a welcome fact of life. Join our community for research-based answers and insights to your most important questions.",
		'type' => 'textarea',
	);

	return $options;
}
add_filter( 'largo_options', 'kcon_custom_options' );

/**
 * This isn't shown on interior layouts, and interferes with the homepage layout
 */
define( 'SHOW_GLOBAL_NAV', false );

/**
 * Add theme social buttons to main nav header
 *
 * Copied in part from https://github.com/INN/Largo/blob/master/partials/nav-global.php#L31-L37
 */
function kcon_main_nav_add_social() {
	/* Check to display Social Media Icons */
	if ( of_get_option( 'show_header_social') ) { ?>
		<div class="nav-right">
			<ul id="header-social-links" class="social-icons hide-mobile">
				<?php largo_social_links(); ?>
			</ul>
		</div>
	<?php }
}
add_action( 'largo_after_main_nav_shelf', 'kcon_main_nav_add_social' );

/**
 * Output the site subtitle and description inside the main header.
 *
 * Kinsey uses a custom partials/largo-header.php, but this action is part of the main Largo: https://github.com/INN/Largo/blob/master/partials/largo-header.php#L22
 */
function kcon_subtitle_description() {
	echo '<div id="subtitle-description" class="">';
	echo '<div class="subtitle"><span>';
	echo of_get_option('subtitle');
	echo '</span></div>';
	echo '<div class="description"><span>';
	echo of_get_option('site_description');
	echo '</span></div>';
	echo '</div>';
}
add_action( 'largo_header_after_largo_header', 'kcon_subtitle_description', 5 ); // This comes before the Largo one, so it can be floated to the left.

// for the powerpress shortcode in the text widget on the homepage
add_filter('widget_text', 'do_shortcode');
