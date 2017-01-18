<?php
/**
 * Template Name: Resources Page
 */
get_header();
$top_page = FALSE;

?>

<div id="content" class="row-fluid guide-page" role="main">
	<?php
		while ( have_posts() ) : the_post();

			// get the ID of the main page for a given guide
			$this_page_id = $post->ID;
			$ancestors = get_post_ancestors( $this_page_id );
			$page_type_id = end( $ancestors ); // the topmost parent is actually the "guides" page so let's back it up one
			$guide_type = get_post( $page_type_id )->post_name;

			if ( count($ancestors) === 1 ) {
				// this is the main page of the guide so we can just list all of its children
				$top_page = TRUE;
				$guide_parent_id = $post->ID;
			} else {
				// it's not and we need to do get the full page tree
				$guide_parent_id = prev( $ancestors ); // much better
			}

			// now get the complete tree of child pages for the guide's top page
			$children = wp_list_pages('title_li=&child_of=' . $guide_parent_id . '&echo=0');
			?>

			<nav class="guide-nav span3 navbar">
				<!-- .btn-navbar is used as the toggle for collapsed navbar content -->
				<div class="container clearfix">
		      <a class="btn btn-navbar toggle-nav-bar" title="More">
		        <div class="bars">
			        <span class="icon-bar"></span>
			        <span class="icon-bar"></span>
			        <span class="icon-bar"></span>
		        </div>
		      </a>

					<?php if ( $top_page ) { ?>
						<h4 class="guide-top"><?php echo get_the_title( $guide_parent_id ); ?></h4>
					<?php } else { ?>
						<h4 class="guide-top"><a href="<?php echo get_permalink( $guide_parent_id ); ?>"><?php echo get_the_title( $guide_parent_id ); ?></a></h4>
					<?php } ?>
					<ul class="guide-tree">
						<?php echo $children; ?>
					</ul>

				</div>
			</nav>

			<article id="post-<?php the_ID(); ?>" <?php post_class('clearfix span9'); ?>>

				<header class="entry-header">
					<h1 class="entry-title"><?php the_title(); ?></h1>
					<?php edit_post_link(__('Edit This Page', 'largo'), '<h5 class="byline"><span class="edit-link">', '</span></h5>'); ?>
				</header><!-- .entry-header -->

				<div class="entry-content">
					<?php the_content(); ?>

					<?php // in-guide navigation ?>
					<nav id="nav-below" class="pager post-nav clearfix">
					<?php
						$pagelist = get_pages('sort_column=menu_order&sort_order=asc&child_of=' . $guide_parent_id );
						$pages = array();
						$pages[] = $guide_parent_id;
						$prev_id = $next_id = '';
						foreach ( $pagelist as $page ) {
						   $pages[] += $page->ID;
						}

						$current = array_search(get_the_ID(), $pages);
						if ( array_key_exists($current-1, $pages)) {
							$prev_id = $pages[$current-1];
						}
						if ( array_key_exists($current+1, $pages)) {
							$next_id = $pages[$current+1];
						}

						if (!empty($prev_id)) {
							printf( '<div class="previous"><a href="%1$s"><h5>Previous Section</h5><span class="meta-nav">%2$s</span></a></div>',
								get_permalink( $prev_id ),
								get_the_title($prev_id)
							);
						}

						if (!empty($next_id)) {
							printf( '<div class="next"><a href="%1$s"><h5>Next Section</h5><span class="meta-nav">%2$s</span></a></div>',
								get_permalink( $next_id ),
								get_the_title( $next_id )
							);
						}
						?>

					</nav><!-- #nav-below -->
				</div><!-- .entry-content -->

			</article><!-- #post-<?php the_ID(); ?> -->

		<?php endwhile; // end of the loop.
	?>
</div><!--#content-->

<?php get_footer(); ?>
