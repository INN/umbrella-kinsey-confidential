<?php
/**
 * Integration functions for PowerPress, by Blubrry
 *
 * @since 2016-06-03
 * @since 0.0.1
 */

/**
 * A filter that sets the image in the PowerPress 'enclosure' post meta to the featured media
 */
function kcon_powerpress_enclosure_featured_media($post_ID) {
	$post_ID = (int) $post_ID;
	$enclosure = get_post_meta($post_ID, 'enclosure', true);
	if ( isset($enclosure) ) {
		$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id($post_ID), 'thumbnail' );
		if ( isset($thumbnail) ) {
			$enclosure = explode("\r\n", $enclosure);
			if ( isset($enclosure[3]) ) {
				$metadata = unserialize($enclosure[3]);
				if ( is_array($metadata) ) {
					$metadata['image'] = $thumbnail[0];
					$enclosure[3] = serialize($metadata);
					update_post_meta($post_ID, 'enclosure', implode("\r\n", $enclosure));
				}
			}
		}
	}
	return $post_ID;
}
add_action( 'save_post_post', 'kcon_powerpress_enclosure_featured_media', 20 );
