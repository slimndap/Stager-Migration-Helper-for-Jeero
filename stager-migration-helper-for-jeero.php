<?php
/**
 * Plugin Name: Stager Migration Helper for Jeero
 * Description: Prepares your site for migration from Theater for WordPress legacy importer extensions to Jeero, specifically for users of the Stager ticketing platform, by adding identifiers to previously imported events.
 * Version: 1.1
 * Author: Jeroen Schmit
 */

// Prevent direct file access.
if (!defined('ABSPATH')) {
	exit;
}

// Hook into admin init to ensure migration is checked.
add_action('admin_init', 'tmhjeero_process_migration');

function tmhjeero_process_migration() {
	// Process posts of type 'wp_theatre_prod'.
	$args_prod = [
		'post_type' => 'wp_theatre_prod',
		'posts_per_page' => -1,
		'post_status' => 'any',
		'meta_query' => [
			'relation' => 'AND',
			[
				'key' => '_wpt_source',
				'value' => 'wpt_stager',
				'compare' => '='
			],
			[
				'key' => '_wpt_source_ref',
				'compare' => 'EXISTS'
			]
		]
	];

	$events_prod = get_posts($args_prod);
	$updated = 0;

	foreach ($events_prod as $event) {
		// Check if the event already has a reference.
		$existing_ref = get_post_meta($event->ID, 'jeero/Theater_For_WordPress/stager_pushfeed/ref', true);
		if (empty($existing_ref)) {
			// Generate a unique identifier:
			$source_ref = get_post_meta($event->ID, '_wpt_source_ref', true);

			// Replace 'ps' with '', then replace any remaining 'p' with 'e'.
			$identifier = str_replace('ps', '', $source_ref);
			$identifier = str_replace('p', 'e', $identifier);

			update_post_meta($event->ID, 'jeero/Theater_For_WordPress/stager_pushfeed/ref', $identifier);
			$updated++;
		}
	}

	// Process posts of type 'wp_theatre_event'.
	$args_event = [
		'post_type' => 'wp_theatre_event',
		'posts_per_page' => -1,
		'post_status' => 'any',
		'meta_query' => [
			[
				'key' => '_wpt_source',
				'value' => 'wpt_stager',
				'compare' => '='
			],
			[
				'key' => '_wpt_source_ref',
				'compare' => 'EXISTS'
			]
		]
	];

	$events_event = get_posts($args_event);
	$updated_event = 0;

	foreach ($events_event as $event) {
		// Update '_wpt_source' to 'stager_pushfeed'.
		update_post_meta($event->ID, '_wpt_source', 'stager_pushfeed');

		// Update '_wpt_source_ref' to remove the leading 'e'.
		$existing_ref = get_post_meta($event->ID, '_wpt_source_ref', true);
		$new_ref = ltrim($existing_ref, 'e');
		update_post_meta($event->ID, '_wpt_source_ref', $new_ref);
		$updated_event++;
	}

	// Admin notice after completion if any events were updated.
	if (($updated > 0 || $updated_event > 0) && is_admin()) {
		add_action('admin_notices', function() use ($updated, $updated_event) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo $updated; ?> event(s) of type 'wp_theatre_prod' and <?php echo $updated_event; ?> event(s) of type 'wp_theatre_event' have been successfully updated for Jeero migration.</p>
			</div>
			<?php
		});
	}
}
?>
