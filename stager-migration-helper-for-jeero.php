<?php
/**
 * Plugin Name: Stager Migration Helper for Jeero
 * Description: Prepares your site for migration from Theater for WordPress legacy importer extensions to Jeero, specifically for users of the Stager ticketing platform, by adding identifiers to previously imported events.
 * Version: 1.0
 * Author: Jeroen Schmit
 */

// Prevent direct file access.
if (!defined('ABSPATH')) {
	exit;
}

// Hook into admin init to ensure migration is checked.
add_action('admin_init', 'tmhjeero_process_migration');

function tmhjeero_process_migration() {
		// Assuming events are stored as a custom post type called 'wp_theatre_prod'.
	$args = [
		'post_type' => 'wp_theatre_prod',
		'posts_per_page' => -1,
		'post_status' => 'any',
		'meta_query' => [
			'relation' => 'OR',
			[
				'key' => '_wpt_source',
				'compare' => 'EXISTS'
			],
			[
				'key' => '_wpt_source_ref',
				'compare' => 'EXISTS'
			]
		]
	];

	$events = get_posts($args);
	$updated = 0;

	foreach ($events as $event) {
		// Check if the event already has a reference.
		$existing_ref = get_post_meta($event->ID, 'jeero/Theater_For_WordPress/stager', true);
		if (empty($existing_ref)) {
			// Generate a unique identifier based on the event ID and add it as post meta.
			$identifier = (int) filter_var(get_post_meta($event->ID, '_wpt_source_ref', true), FILTER_SANITIZE_NUMBER_INT);
			update_post_meta($event->ID, 'jeero/Theater_For_WordPress/stager', $identifier);
			$updated++;
		}
	}

	// Admin notice after completion if any events were updated.
	if ($updated > 0 && is_admin()) {
		add_action('admin_notices', function() use ($updated) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo $updated; ?> event(s) have been successfully updated with identifiers for Jeero migration.</p>
			</div>
			<?php
		});
	}
}
?>
