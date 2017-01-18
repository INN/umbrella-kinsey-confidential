<?php
/*
 * Largo Header
 *
 * Calls largo_header() output function and displays print header
 *
 * @package Largo
 * @see inc/header-footer.php
 */
if ( ! is_single() && ! is_singular() || ! of_get_option( 'main_nav_hide_article', false ) ) {
?>
<?php
/**
 * Copied from Largo 0.5.4 to make the full-wdith header image happen
 */

?>
<header
	id="site-header"
	class="full-width clearfix nocontent"
	itemscope
	itemtype="http://schema.org/Organization"
	style="background-image: url('<?php echo of_get_option( 'home_header_full_image' ); ?>')"
>
	<?php
	/**
	 * Before largo_header()
	 *
	 * Use add_action( 'largo_header_before_largo_header', 'function_to_add');
	 *
	 * @link https://codex.wordpress.org/Function_Reference/add_action
	 * @since 0.5.5
	 */
	do_action('largo_header_before_largo_header');

	
	//largo_header();
	?>
	<h1 class="sitename"><a itemprop="url" href="/"><strong>#Kinsey</strong>Confidential</a></h1>
	
	<?php
	/**
	 * After largo_header()
	 *
	 * Use add_action( 'largo_header_after_largo_header', 'function_to_add');
	 *
	 * @link https://codex.wordpress.org/Function_Reference/add_action
	 * @since 0.5.5
	 */
	do_action('largo_header_after_largo_header');

	?>
</header>
<header class="print-header nocontent">
	<p>
		<strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong>
		(<?php /* Documented in inc/helpers.php */
			echo esc_url( largo_get_current_url() ); ?>)
	</p>
</header>
<?php }
