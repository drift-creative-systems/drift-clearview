<?php
/**
 * Removes the plugin's options when it is deleted.
 *
 * @package Drift_Clearview
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Options to remove on every site.
 */
$drift_clearview_options = array( 'drift_clearview_placement', 'drift_clearview_hover_color' );

if ( is_multisite() ) {
	$drift_clearview_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $drift_clearview_site_ids as $drift_clearview_site_id ) {
		switch_to_blog( $drift_clearview_site_id );
		foreach ( $drift_clearview_options as $drift_clearview_option ) {
			delete_option( $drift_clearview_option );
		}
		restore_current_blog();
	}
} else {
	foreach ( $drift_clearview_options as $drift_clearview_option ) {
		delete_option( $drift_clearview_option );
	}
}
