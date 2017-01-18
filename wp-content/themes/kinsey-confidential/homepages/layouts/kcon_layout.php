<?php

// Include Largo's Homepage class
include_once get_template_directory() . '/homepages/homepage-class.php';

class KCon_Layout extends Homepage {
	function __construct( $options=array() ) {
		if ( is_home() || isset( $_POST['is_home'] ) && $_POST['is_home'] === 'true' ) {
			add_action( 'largo_lmp_args', array( $this, 'lmp_args' ) );
		}

		$defaults = array(
			'name' => __( 'Kinsey Confidential homepage', 'kcon' ),
			'type' => 'kcon',
			'description' => __( 'Full-width header image, three posts across top, widget area, six posts, sidebar', 'kcon' ),
			'template' => get_stylesheet_directory() . '/homepages/templates/kcon_template.php',
			'assets' => array(
				array(
					'kcon_homepage_css',
					get_stylesheet_directory_uri() . '/homepages/assets/css/homepage.css',
					array()
				)
			),
			'prominenceTerms' => array(
				array(
					'name' => __( 'Homepage Featured', 'largo' ),
					'description' => __( 'These posts will be displayed in the three top slots on the homepage', 'kcon' ),
					'slug' => 'homepage-featured'
				)
			)
		);
		$options = array_merge( $defaults, $options );
		parent::__construct( $options) ;
	}

	/**
	 * The three posts at the top of the homepage
	 *
	 * These are all "Homepage Featured"
	 */
	function top_three() {
		global $shown_ids;

		$featured_stories = largo_home_featured_stories();

		// if there aren't enough posts in the Homepage Featured term
		if ( count( $featured_stories ) < 3 ) {
			$recent_stories = wp_get_recent_posts( array(
				'numberposts' => 3 - count( $featured_stories ),
				'offset' => 0,
				'cat' => 0,
				'orderby' => 'post_date',
				'order' => 'DESC',
				'post__not_in' => $shown_ids,
				'post_type' => 'post',
				'post_status' => 'publish',
			), 'OBJECT');

			$featured_stories = array_merge( $featured_stories, $recent_stories );
		}

		ob_start();
		foreach ( $featured_stories as $featured ) {
			$shown_ids[] = $featured->ID;
			$thumbnail = get_the_post_thumbnail( $featured->ID, 'full' ); 
			?>
				<div class="span4">
					<div class="post-lead <?php echo $thumbnail ? 'has-thumbnail' : ''?> ">
						<h5 class="top-tag"><?php largo_top_term( array( 'post' => $featured->ID ) ); ?></h5>
						<?php if ( !empty( $thumbnail ) ) { ?>
							<a href="<?php echo esc_attr( get_permalink( $featured->ID ) ); ?>">
								<?php echo $thumbnail; ?>
							</a>
						<?php } ?>
						<h3><a href="<?php echo esc_url( get_permalink( $featured->ID ) ); ?>"><?php echo $featured->post_title; ?></a></h3>
						<h5 class="byline"><?php largo_byline( true, false, $featured->ID ); ?></h5>
					</div>
				</div>
			<?php
		}
		return ob_get_clean();
	}

	/**
	 * The six posts near the bottom of the homepage
	 *
	 * These are all "Homepage Featured"
	 */
	function home_six() {
		global $shown_ids;

		$featured_stories = get_posts(array(
			'posts_per_page' => 6,
			'post__not_in' => $shown_ids
		));

		// if there aren't enough posts in the Homepage Featured term
		if ( count( $featured_stories ) < 6) {
			$recent_stories = wp_get_recent_posts( array(
				'numberposts' => 6 - count( $featured_stories ),
				'offset' => 0,
				'cat' => 0,
				'orderby' => 'post_date',
				'order' => 'DESC',
				'post__not_in' => $shown_ids,
				'post_type' => 'post',
				'post_status' => 'publish',
			), 'OBJECT');

			$featured_stories = array_merge( $featured_stories, $recent_stories );
		}

		ob_start();
		foreach ( $featured_stories as $featured ) {
			$shown_ids[] = $featured->ID;
			
			// Set up our current post as The post
			global $post;
			$post = $featured;
			setup_postdata( $post );


			get_template_part( 'partials/content', 'kcon-home' );
		}
		wp_reset_postdata();
		return ob_get_clean();
	}

	function lmp_args( $args ) {
		$args['posts_per_page'] = 6;
		return $args;
	}

	// Copied from theme-rns/homepages/zones/rns-zones.php
	// Modified to use featured posts
	function homepage_lmp_button() {
		global $shown_ids;
		$posts_term = of_get_option( 'posts_term_plural' );
		$nav_id = 'nav-below';

		$the_query = new WP_Query( array(
			'posts_per_page' => 6,
			'post__not_in' => $shown_ids
		));

		ob_start();
		largo_render_template( 'partials/load-more-posts', array(
			'nav_id' => $nav_id,
			'the_query' => $the_query,
			'posts_term' => ( $posts_term ) ? $posts_term : 'Posts'
		));
		return ob_get_clean();
	}
}

function kcon_add_homepage_widget_areas() {
	$sidebars = array(
		array(
			'name' => 'Homepage Main Column Top',
			'id' => 'homepage-main-column-top',
			'before_widget' => '<div class="row"><div class="span12">',
			'after_widget' => '</div></div>',
			'before_title' => '<h3 class="widgettitle">',
			'after_title' => '</h3>',
		)
	);
	foreach ( $sidebars as $sidebar ) {
		register_sidebar( $sidebar );
	}
}
add_action( 'widgets_init', 'kcon_add_homepage_widget_areas' );

/**
 * In order to properly add the "row-fluid" markup to homepage tiles, hijack the
 * "largo_lmp_template_partial" filter to set $wp_query;
 */
function kcon_homepage_tiles_query( $partial, $query ) {
	global $wp_query;

	if ( isset( $_POST['is_home'] ) && $_POST['is_home'] == 'true' && $partial == 'home' ) {
		$wp_query = $query;
		$partial = 'kcon-home';
	}

	return $partial;
}
add_filter( 'largo_lmp_template_partial', 'kcon_homepage_tiles_query', 10, 2 );