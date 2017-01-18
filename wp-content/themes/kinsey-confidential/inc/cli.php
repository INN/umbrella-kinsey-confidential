<?php

/**
 * Manage Kinsey Confidential post-migration tasks
 *
 * Functions:
 *    for_wpss_photo_do_create_or_update_attachment
 *    for_attachment_do_copy_wpss_metadata_to_attachment // this is deprecated, and doesn't do what was intended
 *    get_posts_with_wpss_photos 
 */
class KCon_WP_CLI extends WP_CLI_Command {

	// The prefix used by the former WPSS plugin for its tables.
	public $plugin_prefix = 'wpss_';

	/**
	 * For every WP_Post described in the WPSS metadata:
	 *     - check whether it's described by multiple WPSS photo_id
	 *     - if it is described by one single photo_id, copy the meta over
	 *     - if there are other photo_id:
	 *         - Copy the attachment metadata using https://codex.wordpress.org/Function_Reference/wp_get_attachment_metadata
	 *         - Create new attachement posts using https://codex.wordpress.org/Function_Reference/wp_insert_attachment and the copied attachment metadata and filename
	 *         - Update the metadata for each new attachment post using the alt and caption from the WPSS table
	 *
	 * ## USAGE
	 *     wp kcon for_wpss_photo_do_create_or_update_attachment
	 *
	 * @uses $this->kcon_wpss_update_single
	 * @uses $this->kcon_wpss_update_multiple

	 */
	private function for_wpss_photo_do_create_or_update_attachment() {
		// get database tables
		global $wpdb;
		$photo_meta_rels = $wpdb->prefix.$this->plugin_prefix."photo_meta_relations";
		$slideshow_photos = $wpdb->prefix.$this->plugin_prefix."photos";
		$photo_meta = $wpdb->prefix.$this->plugin_prefix."photo_meta";

		// Find all WPSS photos
		$query = "SELECT DISTINCT
					wp_photo_id,
					meta_name,
					meta_value,
					sp.photo_id
				FROM
					$slideshow_photos as sp,
					$photo_meta as pm,
					$photo_meta_rels as pmr
				WHERE
					sp.photo_id=pmr.photo_id AND
					pmr.meta_id=pm.meta_id";
		$results = $wpdb->get_results($query);

		/*
		 * Create an array:
		 * wp_photo_id
		 *     photo_id
		 *         meta_name => meta_value
		 *         meta_name => meta_value
		 *         meta_name => meta_value
		 *     photo_id
		 *         meta_name => meta_value
		 *         meta_name => meta_value
		 *
		 * and so on.
		 *
		 * The goal of this array is to find wordpress image attachments (wp_photo_id is the post ID) with more than one WPSS photo related.
		 */
		$attachments = array();
		foreach ($results as $result) {
			$result = (array) $result;
			$attachments[$result['wp_photo_id']][$result['photo_id']] = array( $result['meta_name'] => $result['meta_value'] );
		}

		// Actually create the new attachment posts, and get back arrays of array( 'wpss id' => 'attachment id' );
		$new_ids = array();
		$creation_progress = \WP_CLI\Utils\make_progress_bar(
			"Creating new attachment posts...",
			count( $attachments )
		);
		foreach ($attachments as $id => $attachment) {
			$count = count($attachment);
			if ( $count > 1 ) {
				$new_posts = $this->kcon_wpss_update_multiple($id, $attachment);
			} else {
				$new_posts = $this->kcon_wpss_update_single($id, $attachment);
			}
			// create a unified array of arrays
			$new_ids = array_merge( $new_ids, $new_posts );
			$creation_progress->tick();
		}
		$creation_progress->finish();

		// Shuffle the array of array( 'wpss id' => 'attachment id' ) arrays to be a plain associative array
		$actual_new_ids = array();
		foreach ( $new_ids as $array ) {
			foreach ( $array as $k => $v ) {
				$actual_new_ids[$k] = $v;
			}
		}

		// some debugging stuff
		$this->log("Count of new ids:");
		$this->log(count($actual_new_ids));
		return $actual_new_ids;
	}

	/**
	 * Update the WP attachment post metadata to the values contained in just one WPSS attachment.
	 * This assumes that the length of $attachment is only 1
	 *
	 * @param int $id The ID of the WordPress post
	 * @param array $attachment array( $wpss_id => array( $meta_name => $meta_value ) )
	 * @return array of wpss_id => attachment ID
	 */
	private function kcon_wpss_update_single($id, $attachment) {
		$metadata = wp_get_attachment_metadata($id);

		$new_ids = array();

		foreach ( $attachment as $wpss_id => $attributes ) {
			foreach ( $attributes as $meta_name => $meta_value ) {
				$metadata['image_meta'][$meta_name] = $meta_value;

				// special treatment for captions
				// Largo uses the post_excerpt as the caption: https://github.com/INN/Largo/blob/a5a0b7fdaedcfe261023be7e6f642956d51e58b4/inc/featured-media.php#L147
				if ( $meta_name == 'caption' && ! empty( $meta_value ) ) {
					$caption_post['ID'] = $id;
					$caption_post['post_excerpt'] = $meta_value;
					wp_update_post($caption_post);
				}

				// Special treatment for alts
				if ( $meta_name == 'alt' && ! empty( $meta_value ) ) {
					update_post_meta( $id, '_wp_attachment_image_alt', $meta_value );
				}
			}
			$new_ids[] = array( $wpss_id => $id );
		}

		// save metadata back to database
		wp_update_attachment_metadata($id, $metadata);

		return $new_ids;
	}

	/**
	 * Create additional attachment posts for WPSS photos that have multiple WPSS photos per WP attachment post
	 *
	 * New posts' post meta are copied from the existing WP post, then overlaid with the WPSS post meta.
	 *
	 * @param int $id The ID of the WordPress post
	 * @param array $attachment array(
	 *                              $wpss_id => array( $meta_key => $meta_value ),
	 *                              $wpss_id => array( $meta_key => $meta_value, $meta_key => $meta_value ),
	 *                              ...
	 *                          )
	 * @return array of wpss_id => attachment ID
	 */
	private function kcon_wpss_update_multiple($id, $attachment) {
		$metadata = wp_get_attachment_metadata($id);
		$orig_attachment_post = get_post($id);

		// I don't know why this happens, but it is needed in the event that a post which allegedly exists does not
		if ( $metadata === false && $orig_attachment_post === null ) {
			return array();
		}

		/*
		 * Generate the filename
		 */
		// this normally returns the upload dir for the current year and month, so we're going to have to remove the /yyyy/mm
		$wp_upload_dir = wp_upload_dir( );
		$wpud_split = explode('/', $wp_upload_dir['url']);

		// remove empty last element
		if ( end($wpud_split) == '' ) {
			array_pop($wpud_split);
		}
		array_pop($wpud_split); // remove /mm
		array_pop($wpud_split); // remove /yyyy
		$wp_upload_dir = implode('/', $wpud_split);
		// for example, this is now http://vagrant.dev/wp-content/uploads

		// generate the filename for the image in the uploads directory
		$filename = $wp_upload_dir['url'] . '/' . $metadata['file'];


		// Track IDs of created posts
		$new_ids = array();

		// The things that need to be done for every WPSS photo
		foreach ( $attachment as $wpss_id => $attributes ) {
			// Create new post metadata for this WPSS photo
			$local_metadata = $metadata;
			foreach ( $attributes as $meta_name => $meta_value ) {
				if ( ! $meta_name === 'caption' && $meta_value != '' ) {
					$local_metadata['image_meta'][$meta_name] = $meta_value;
				}
			}

			// Create new attachment posts for the current WPSS photo ID with existing files and titles from the corresponding WP attachment post
			$new_id = wp_insert_attachment(
				array(
					'guid' => $filename . $wpss_id, // This is a GUID, not really used for anything other than a unique identifier in the DB.
					'post_mime_type' => wp_check_filetype( basename( $filename ), null ),
					'post_title' => $orig_attachment_post->post_title,
					'post_content' => empty($orig_attachment_post->post_content) ? ' ' : $orig_attachment_post->post_content,
					'post_status' => $orig_attachment_post->post_status,
					'post_excerpt' => empty($attributes['caption']) ? $orig_attachment_post->post_excerpt : $attributes['caption'] ,
				),
				$filename // filename
				// The parent ID's visibility is not necessary.
			);
			$new_ids[] = array( $wpss_id => $new_id );

			// Update the metadata of the new post with the merged metadata
			wp_update_attachment_metadata( $new_id, $local_metadata );

		} // end foreach of WPSS photo IDs
		return $new_ids;
	}

	/**
	 * Get all post IDs in the database that have WPSS-associated photo metadata
	 *
	 * @return Array of array(
	 *     'meta_id' => 'number',
	 *     'post_id' => 'number',
	 *     'meta_key' => 'wpss_post_image or wpss_photo_id',
	 *     'meta_value' => 'the wpss photo id'
	 * )
	 *
	 * The return values are straight from the database, and may actually be an array of stdClass::__set_state(array()) instead of an array of Array()
	 */
	private function get_posts_with_wpss_photos() {
		global $wpdb;
		$query = "SELECT *
				FROM
					wp_postmeta
				WHERE
					meta_key like 'wpss_photo_id' OR
					meta_key like 'wpss_post_image'
				";
		$posts = $wpdb->get_results($query);
		return $posts;
	}

	/**
	 * Get all post IDs in the database that WPSS-associated slideshow metadata
	 *
	 * @return Array of array(
	 *     'meta_id' => 'number',
	 *     'post_id' => 'number',
	 *     'meta_key' => 'slideshow_id',
	 *     'meta_value' => 'the wpss photo id'
	 * )
	 *
	 * The return values are straight from the database, and may actually be an array of stdClass::__set_state(array()) instead of an array of Array()
	 */
	private function get_posts_with_wpss_slideshows() {
		global $wpdb;
		$query = "SELECT *
				FROM
					wp_postmeta
				WHERE
					meta_key like 'slideshow_id'
				";
		$posts = $wpdb->get_results($query);
		$this->log( count($posts) . " posts have slideshows. This migration does not account for those posts at this time." );
		return $posts;
	}

	/**
	 * Update posts with WPSS featured photo ids
	 *
	 * @param Array $new_ids array( 'wpss id number' => 'new wp attachment post id' );
	 * @param Array of arrays, each containing at least a 'post_id' and 'meta_value' key
	 * @link How Largo does this: largo_featured_media_save  https://github.com/INN/Largo/blob/master/inc/featured-media.php#L535
	 */
	private function update_posts_with_wpss_photo_ids( $new_ids, $photos_posts ) {
		$progress = \WP_CLI\Utils\make_progress_bar(
			"Updating posts with WPSS image metadata to use WordPress attachment posts and Largo-style featured media...",
			count( $photos_posts )
		);
		// collect all the posts that need to be updated
		foreach ( $photos_posts as $photo_post ) {
			$photo_post = (array) $photo_post;

			// most posts will be using the wpss_post_image metadata
			if ( $photo_post['meta_key'] === 'wpss_post_image' ) {
				set_post_thumbnail( $photo_post['post_id'], $new_ids[$photo_post['meta_value']] ); // set the thumbnail
				// let's fake some Largo-style featured_media post meta
				$featured = array (
					'type' => 'image',
					'attachment' => $new_ids[$photo_post['meta_value']],
					'attachment_data' => get_post_meta( $new_ids[$photo_post['meta_value']] )
				);
				update_post_meta( $photo_post['post_id'], 'featured_media', $featured );
			}

			// Assume that the wpss_photo_id overrides wpss_post_image, because the three posts seen in the Kinsey Confidential database that had wpss_photo_id meta were using the highest wpss photo id number associated with the attachment WP post.
			if ( $photo_post['meta_key'] === 'wpss_photo_id' ) {
				set_post_thumbnail( $photo_post['post_id'], $new_ids[$photo_post['meta_value']] ); // set the thumbnail
				// let's fake some Largo-style featured_media post meta
				$featured = array (
					'type' => 'image',
					'attachment' => $new_ids[$photo_post['meta_value']],
					'attachment_data' => wp_get_attachment_metadata( $new_ids[$photo_post['meta_value']] )
				);
				update_post_meta( $photo_post['post_id'], 'featured_media', $featured );
			}
			$progress->tick();
		}
		$progress->finish();
	}

	/**
	 * Copy over all the photo_credit post metas to the places that Largo expects it to be
	 */
	private function photo_credit_copy( $new_ids ) {
		$progress = \WP_CLI\Utils\make_progress_bar(
			"Copying photo_credit post meta to _media_credit",
			count($new_ids)
		);
		foreach ( $new_ids as $id ) {
			$credit = get_post_meta( $id, 'photo_credit' );
			if ( is_array($credit) ) {
				$credit = $credit[0];
			}
			update_post_meta( $id, '_media_credit', $credit );
			$progress->tick();
		}
		$progress->finish();
	}

	/**
	 * Ties together all the WPSS photo migration functions
	 *
	 * @uses for_wpss_photo_do_create_or_update_attachment
	 * @uses get_posts_with_wpss_photos
	 * @uses get_posts_with_wpss_slideshows
	 * @uses update_posts_with_wpss_photo_ids
	 * @uses photo_credit_copy
	 */
	public function migrate_wpss_photos() {
		$this->new_ids = $this->for_wpss_photo_do_create_or_update_attachment();
		$this->photos_posts = $this->get_posts_with_wpss_photos();

		// Slideshow posts are not migrated; we're just getting a count of them here.
		$this->slideshow_posts = $this->get_posts_with_wpss_slideshows();

		$this->update_posts_with_wpss_photo_ids( $this->new_ids, $this->photos_posts );
		$this->photo_credit_copy($this->new_ids);
	}

	/**
	 * Ensure the Kinsey Confidential theme is active
	 *
	 * ## EXAMPLES
	 *
	 *    wp kcon ensure_theme
	 *
	 */
	public function ensure_theme() {
		$theme = wp_get_theme( $stylesheet, $theme_root );

		if ( $theme->stylesheet != 'kinsey-confidential' ) {
			$this->log("The RNS theme must be enabled");
			return false;
		}

		if ( ! function_exists( 'of_set_option' ) ) {
			$this->log("Function of_set_option not available");
			return false;
		}

		return true;
	}

	private function log($stuff) {
		WP_CLI::line( var_export( $stuff, true ) );
	}

	/**
	 * Find posts with the meta 'teaser_text' and convert that meta_key to 'subtitle'
	 *
	 * It's probably easier to just copy out the heredoc-delimited SQL query and run it via Sequel Pro or PHPmyAdin
	 */
	public function migrate_teaser_text_postmeta() {
		global $wpdb;
		$query = <<<EOD
-- find posts by meta
drop temporary table if exists tmp_teaser_texts;
create temporary table if not exists tmp_teaser_texts
	select post_id from wp_postmeta
		where meta_key = 'teaser_text';
-- testing from 2016-05-26 dump
-- select count(distinct post_id) from tmp_teaser_texts;
-- 1078

-- find post meta entries that mark posts as exempt from the paywall
drop temporary table if exists tmp_subtitles;
create temporary table if not exists tmp_subtitles
	select post_id from wp_postmeta
		where meta_key = 'subtitle';
-- testing from 2016-05-26 dump
-- select count(distinct post_id) from tmp_subtitles;
-- 1

-- find posts that have teaser_text but not subtitle
drop temporary table if exists tmp_need_meta;
create temporary table if not exists tmp_need_meta
	select a.post_id from tmp_teaser_texts a
		left outer join tmp_subtitles b
		on a.post_id = b.post_id
		where b.post_id is null;
-- testing from 2016-05-26 dump
-- select count(distinct post_id) from tmp_need_meta;
-- 1078

-- change meta_key for those posts' 'teaser_text' entries to 'subtitle'
update wp_postmeta a
	left outer join tmp_need_meta b on a.post_id = b.post_id
set a.meta_key = 'subtitle'
where a.meta_key = 'teaser_text';
EOD;

		$message = <<<EOE

This command doesn't do anything.

Open inc/search.php in a text editor and copy out the SQL statement
contained in migrate_teaser_text_postmeta, and run that in Sequel Pro
or phpMyAdmin or directly via mysql.

It wasn't written to work with WP CLI.


EOE;
		print( $message );
		return false;
	}

	/**
	 * For every post with a 'question' custom meta field, append the cntents of that field to the top of the post, inside a  <p class="intro">
	 * If this succeeds, delete the 'question' post meta for that post
	 *
	 * This allows the questions to be displayed using the styling in largo.
	 */
	public function migrate_question_postmeta() {
		global $wpdb;
		$query = "select distinct meta_id, post_id, meta_key, meta_value from wp_postmeta where meta_key = 'question';";
		$results = $wpdb->get_results($query);
		$progress = \WP_CLI\Utils\make_progress_bar(
			"copying 'question' post meta into p.intro in post...",
			count( $results)
		);

		foreach ($results as $row) {
			// meta_id, post_id, meta_key = 'question', meta_value = slash-escaped html
			extract( (array) $row);

			// Get the post
			$post  = get_post( $post_id, 'ARRAY_A');

			// Create the new paragraph
			$intro = '<p class="intro question"><span class="question-label">Question:</span> ' . stripslashes($meta_value) . "</p>\n\n";
			$content = $intro . $post['post_content'];

			// add the new paragraph to the start of the post content
			$error = wp_update_post( array(
				'ID' => $post_id,
				'post_content' => $content
			), true);

			// Be cautious about deleting the post meta
			if ( is_wp_error($error) ) {
				$this->log($error);
			} else {
				delete_post_meta( $post_id, $meta_key );
			}
			$progress->tick();
		}
		$progress->finish();
	}
}
WP_CLI::add_command( 'kcon', 'KCon_WP_CLI' );
