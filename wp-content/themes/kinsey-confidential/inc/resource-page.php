<?php
/**
 * Functions specific to the resources pages
 */

/**
 * Let pages be categorized
 */
function inn_page_enhancements() {
	register_taxonomy_for_object_type( 'category', 'page' );
	add_post_type_support( 'page', 'excerpt' );
}
add_action( 'init', 'inn_page_enhancements' );

/**
 * Get a page in this $cat_id
 * copied from: https://github.com/INN/inn/blob/master/inn_resources.php#L70
 * @constant KCON_RESOURCES_PARENT_ID  the ID of the page that is the root of the resources section
 */
function kcon_resources_get_top_item( $cat_id ) {
	//try to get a Page tagged with this $cat_id that has a parent of KCON_RESOURCES_PARENT_ID
	//sort by guide_rank metafield, if present
	$args = array(
		'posts_per_page' => 1,
		'cat' => $cat_id,
		'post_type' => 'page',
		'meta_key' => 'guide_rank',
		'orderby' => 'meta_value_num',
		'order' => 'ASC',
		'post_parent' => KCON_RESOURCES_PARENT_ID,
		'suppress_filters' => 1,
		'update_post_term_cache' => 0,
		'update_post_meta_cache' => 0
	);
	$the_page = new WP_Query( $args );
	
	//if at first you don't succeed...
	//try again with 
	if ( !$the_page->have_posts() ) {
		$args = array_merge( $args, array(
			'meta_key' => '',
			'orderby' => 'date',
			'order' => 'DESC'
		) );
		$the_page->query( $args );
	}

	if ( is_string( $the_page )) {
		print $the_page;
		return false;
	}
	//$the_page->the_post();
	//the_title();
	//get_template_part( 'content', 'resource' );
	wp_reset_postdata();	//always give back
	
	return $the_page;
}

/**
 * output the resource page corresponding to a category on that category's page
 */
function kcon_category_resources_link() {
	if ( is_category() ) {
		$qo = get_queried_object();
		$query = kcon_resources_get_top_item( $qo->cat_ID );
		$pages = $query->posts;
		
		if ( is_array( $pages ) && !empty( $pages ) ) {
			$page = $pages[0];
			printf(
				'<h5 class="top-term category-resource-link"><a href="%1$s">%2$s</a></h5>',
					get_permalink( $page->ID ),
					__( 'Read our brief guide on this topic', 'kcon' )
			);
		}
	}
}
add_action( 'largo_category_after_description_in_header', 'kcon_category_resources_link' );
