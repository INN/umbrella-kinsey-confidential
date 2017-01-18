<?php
/**
 * Template partial used for the posts at the bottom of the homepage
 */

$thumbnail = get_the_post_thumbnail( null, 'full' );
?>

<div class="sm-span6 lg-span4">
	<div class="post-lead <?php echo $thumbnail ? 'has-thumbnail' : ''?> ">
		<h5 class="top-tag"><?php largo_top_term(); ?></h5>
		<?php if (!empty($thumbnail)) { ?>
			<a href="<?php echo esc_attr(get_permalink()); ?>">
				<?php echo $thumbnail; ?>
			</a>
		<?php } ?>
		<h3><a href="<?php echo esc_url(get_permalink()); ?>"><?php the_title(); ?></a></h3>
		<h5 class="byline"><?php largo_byline(); ?></h5>
	</div>
</div>
