<?php
/**
 * Plugin Name:       Drift: Clearview
 * Plugin URI:        https://driftcreativesystems.co.uk/apps/clearview/
 * Description:       Hides the WordPress admin toolbar on the front end for logged-in editors and puts small icon links in a corner instead: WP Dashboard, Edit Page, optional shortcuts and your own custom links. Set them up under Settings → Drift: Clearview.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Drift Creative Systems
 * Author URI:        https://driftcreativesystems.co.uk/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       drift-clearview
 *
 * Copyright © 2026 Drift Creative Systems Ltd
 *
 * @package Drift_Clearview
 */

defined( 'ABSPATH' ) || exit;

/*
|--------------------------------------------------------------------------
| Duplicate install guard
|--------------------------------------------------------------------------
| A second copy (e.g. a GitHub "Source code" zip unpacked alongside the real
| folder) would fatal on redeclared functions. Bail and tell the admin instead.
*/
if ( defined( 'DRIFT_CLEARVIEW_VERSION' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Drift: Clearview is installed more than once. Please delete the duplicate plugin folder.', 'drift-clearview' ) . '</p></div>';
		}
	);
	return;
}

define( 'DRIFT_CLEARVIEW_VERSION', '1.1.0' );
define( 'DRIFT_CLEARVIEW_URL', plugin_dir_url( __FILE__ ) );
define( 'DRIFT_CLEARVIEW_OPTION_GROUP', 'drift_clearview_settings' );
define( 'DRIFT_CLEARVIEW_PAGE_SLUG', 'drift-clearview' );
define( 'DRIFT_CLEARVIEW_DEFAULT_PLACEMENT', 'top-right' );
define( 'DRIFT_CLEARVIEW_DEFAULT_HOVER_COLOR', '#c2410c' );
define( 'DRIFT_CLEARVIEW_MAX_LINKS', 8 );

// ---------------------------------------------------------------------------
// Capabilities
// ---------------------------------------------------------------------------

/**
 * Capability needed to lose the toolbar and see the icon links. Read on each
 * call, not at load, so a filter in a theme's functions.php still applies.
 *
 * @return string
 */
function drift_clearview_capability() {
	/**
	 * Filters who gets the icon links instead of the toolbar.
	 *
	 * @param string $capability Default edit_posts (authors and above).
	 */
	return (string) apply_filters( 'drift_clearview_capability', 'edit_posts' );
}

/**
 * Capability needed to see and save the settings screen.
 *
 * @return string
 */
function drift_clearview_settings_capability() {
	/**
	 * Filters the capability needed for Settings → Drift: Clearview.
	 *
	 * @param string $capability Default manage_options.
	 */
	return (string) apply_filters( 'drift_clearview_settings_capability', 'manage_options' );
}

/**
 * Whether the current front-end request gets the icon links.
 *
 * @return bool
 */
function drift_clearview_is_active() {
	return ! is_admin() && is_user_logged_in() && current_user_can( drift_clearview_capability() );
}

// ---------------------------------------------------------------------------
// Settings registration
// ---------------------------------------------------------------------------

add_action( 'admin_init', 'drift_clearview_register_settings' );
/**
 * Registers the options and the main section's fields. The Links list has its
 * own card on the settings screen, so it has no settings field.
 *
 * @return void
 */
function drift_clearview_register_settings() {
	register_setting(
		DRIFT_CLEARVIEW_OPTION_GROUP,
		'drift_clearview_placement',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'drift_clearview_sanitize_placement',
			'default'           => DRIFT_CLEARVIEW_DEFAULT_PLACEMENT,
			'show_in_rest'      => false,
		)
	);

	register_setting(
		DRIFT_CLEARVIEW_OPTION_GROUP,
		'drift_clearview_hover_color',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'drift_clearview_sanitize_hover_color',
			'default'           => DRIFT_CLEARVIEW_DEFAULT_HOVER_COLOR,
			'show_in_rest'      => false,
		)
	);

	register_setting(
		DRIFT_CLEARVIEW_OPTION_GROUP,
		'drift_clearview_links',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'drift_clearview_sanitize_links',
			'show_in_rest'      => false,
		)
	);

	add_settings_section( 'drift_clearview_main', '', '__return_false', DRIFT_CLEARVIEW_PAGE_SLUG );

	add_settings_field(
		'drift_clearview_placement',
		__( 'Link placement', 'drift-clearview' ),
		'drift_clearview_render_placement_field',
		DRIFT_CLEARVIEW_PAGE_SLUG,
		'drift_clearview_main'
	);

	add_settings_field(
		'drift_clearview_hover_color',
		__( 'Link hover colour', 'drift-clearview' ),
		'drift_clearview_render_hover_color_field',
		DRIFT_CLEARVIEW_PAGE_SLUG,
		'drift_clearview_main',
		array( 'label_for' => 'drift-clearview-hover-color' )
	);
}

/*
 * options.php checks manage_options for every option group unless told
 * otherwise, so a filtered settings capability could see the page but not save.
 */
add_filter( 'option_page_capability_' . DRIFT_CLEARVIEW_OPTION_GROUP, 'drift_clearview_settings_capability' );

/**
 * Corner choices.
 *
 * @return array<string, string>
 */
function drift_clearview_get_placements() {
	return array(
		'top-right'    => __( 'Top right', 'drift-clearview' ),
		'top-left'     => __( 'Top left', 'drift-clearview' ),
		'bottom-right' => __( 'Bottom right', 'drift-clearview' ),
		'bottom-left'  => __( 'Bottom left', 'drift-clearview' ),
	);
}

/**
 * Sanitises the placement option, falling back to top right.
 *
 * @param mixed $value Raw option value.
 * @return string
 */
function drift_clearview_sanitize_placement( $value ) {
	if ( ! is_string( $value ) || ! array_key_exists( $value, drift_clearview_get_placements() ) ) {
		return DRIFT_CLEARVIEW_DEFAULT_PLACEMENT;
	}

	return $value;
}

/**
 * Sanitises the hover colour option, falling back to the default.
 *
 * @param mixed $value Raw option value.
 * @return string A valid hex colour.
 */
function drift_clearview_sanitize_hover_color( $value ) {
	$color = sanitize_hex_color( is_string( $value ) ? $value : '' );

	return $color ? $color : DRIFT_CLEARVIEW_DEFAULT_HOVER_COLOR;
}

// ---------------------------------------------------------------------------
// Links: definitions, defaults and sanitising
// ---------------------------------------------------------------------------

/**
 * Built-in links, in their default order. Dashboard and Edit Page are on by
 * default so sites upgrading from 1.0 see no change.
 *
 * @return array<string, array{label: string, description: string, icon: string, default: bool}>
 */
function drift_clearview_get_builtin_links() {
	$block_theme = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();

	return array(
		'dashboard' => array(
			'label'       => __( 'WP Dashboard', 'drift-clearview' ),
			'description' => __( 'Back to wp-admin.', 'drift-clearview' ),
			'icon'        => 'dashboard',
			'default'     => true,
		),
		'edit'      => array(
			'label'       => __( 'Edit Page', 'drift-clearview' ),
			'description' => __( 'Opens the editor for the post or page being viewed. Only shows when there is something the user can edit.', 'drift-clearview' ),
			'icon'        => 'edit',
			'default'     => true,
		),
		'new_post'  => array(
			'label'       => __( 'New Post', 'drift-clearview' ),
			'description' => __( 'Starts a new blog post.', 'drift-clearview' ),
			'icon'        => 'plus',
			'default'     => false,
		),
		'customize' => array(
			'label'       => $block_theme ? __( 'Site Editor', 'drift-clearview' ) : __( 'Customise', 'drift-clearview' ),
			'description' => $block_theme
				? __( 'Opens the Site Editor. Only shows to users who can edit the theme (administrators).', 'drift-clearview' )
				: __( 'Opens the Customiser on the page being viewed. Only shows to users who can customise the site (administrators).', 'drift-clearview' ),
			'icon'        => 'customize',
			'default'     => false,
		),
		'logout'    => array(
			'label'       => __( 'Log out', 'drift-clearview' ),
			'description' => __( 'Logs out and returns to the page you were on.', 'drift-clearview' ),
			'icon'        => 'logout',
			'default'     => false,
		),
	);
}

/**
 * Who can see a custom link. Each choice maps to a capability; "all" means
 * everyone who gets the icons at all.
 *
 * @return array<string, array{label: string, capability: string}>
 */
function drift_clearview_get_visibility_options() {
	return array(
		'all'    => array(
			'label'      => __( 'Everyone who sees the icons', 'drift-clearview' ),
			'capability' => '',
		),
		'editor' => array(
			'label'      => __( 'Editors and administrators', 'drift-clearview' ),
			'capability' => 'edit_others_posts',
		),
		'admin'  => array(
			'label'      => __( 'Administrators only', 'drift-clearview' ),
			'capability' => 'manage_options',
		),
		'shop'   => array(
			'label'      => __( 'Shop managers and administrators', 'drift-clearview' ),
			'capability' => 'manage_woocommerce',
		),
	);
}

/**
 * Links shown when nothing has been saved yet: the built-ins in default
 * order, with only Dashboard and Edit Page switched on.
 *
 * @return array<int, array>
 */
function drift_clearview_default_links() {
	$links = array();
	foreach ( drift_clearview_get_builtin_links() as $id => $builtin ) {
		$links[] = array(
			'type'    => 'builtin',
			'id'      => $id,
			'enabled' => $builtin['default'],
		);
	}

	return $links;
}

/**
 * Cleans a stored or submitted custom link URL. Full URLs keep their scheme
 * (http, https, mailto, tel). Paths starting with "/" are kept as site paths.
 * A bare "something.php…" is kept as a wp-admin path. Anything else, such as
 * "example.com/page", gets http:// added by esc_url_raw().
 *
 * @param mixed $url Raw URL.
 * @return string Cleaned URL, or '' if unusable.
 */
function drift_clearview_sanitize_link_url( $url ) {
	if ( ! is_string( $url ) ) {
		return '';
	}

	$url = trim( $url );
	if ( '' === $url ) {
		return '';
	}

	return esc_url_raw( $url, array( 'http', 'https', 'mailto', 'tel' ) );
}

/**
 * Turns a stored custom link URL into a full URL for output.
 *
 * @param string $url Sanitised stored URL.
 * @return string
 */
function drift_clearview_resolve_link_url( $url ) {
	if ( preg_match( '#^[a-z][a-z0-9+.\-]*:#i', $url ) ) {
		return $url;
	}

	if ( 0 === strpos( $url, '/' ) ) {
		return home_url( $url );
	}

	return admin_url( $url );
}

/**
 * Cleans the links list into its stored shape. Safe to run on stored data
 * as well as on a submitted form, since WordPress can run a sanitize callback
 * twice on the first save.
 *
 * Stored shape, in display order:
 * - array( 'type' => 'builtin', 'id' => 'dashboard', 'enabled' => true )
 * - array( 'type' => 'custom', 'label' => '…', 'url' => '…', 'icon' => 'link', 'new_tab' => false, 'visibility' => 'all' )
 *
 * @param mixed $value   Raw value.
 * @param bool  $trimmed Set to true if links were dropped to stay under the limit.
 * @return array<int, array>
 */
function drift_clearview_normalize_links( $value, &$trimmed = false ) {
	$trimmed = false;

	if ( ! is_array( $value ) ) {
		return drift_clearview_default_links();
	}

	$builtins   = drift_clearview_get_builtin_links();
	$icons      = drift_clearview_get_custom_icons();
	$visibility = drift_clearview_get_visibility_options();
	$links      = array();
	$seen       = array();
	$enabled    = 0;

	foreach ( $value as $item ) {
		if ( ! is_array( $item ) || ! isset( $item['type'] ) ) {
			continue;
		}

		if ( 'builtin' === $item['type'] ) {
			$id = isset( $item['id'] ) && is_string( $item['id'] ) ? sanitize_key( $item['id'] ) : '';
			if ( ! isset( $builtins[ $id ] ) || isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;

			$on = ! empty( $item['enabled'] );
			if ( $on && $enabled >= DRIFT_CLEARVIEW_MAX_LINKS ) {
				$on      = false;
				$trimmed = true;
			}
			if ( $on ) {
				++$enabled;
			}

			$links[] = array(
				'type'    => 'builtin',
				'id'      => $id,
				'enabled' => $on,
			);
			continue;
		}

		if ( 'custom' === $item['type'] ) {
			$label = isset( $item['label'] ) && is_string( $item['label'] ) ? sanitize_text_field( $item['label'] ) : '';
			$url   = isset( $item['url'] ) ? drift_clearview_sanitize_link_url( $item['url'] ) : '';

			// A half-filled row is dropped rather than saved broken.
			if ( '' === $label || '' === $url ) {
				continue;
			}

			if ( $enabled >= DRIFT_CLEARVIEW_MAX_LINKS ) {
				$trimmed = true;
				continue;
			}
			++$enabled;

			$icon = isset( $item['icon'] ) && is_string( $item['icon'] ) && isset( $icons[ $item['icon'] ] ) ? $item['icon'] : 'link';
			$who  = isset( $item['visibility'] ) && is_string( $item['visibility'] ) && isset( $visibility[ $item['visibility'] ] ) ? $item['visibility'] : 'all';

			$links[] = array(
				'type'       => 'custom',
				'label'      => $label,
				'url'        => $url,
				'icon'       => $icon,
				'new_tab'    => ! empty( $item['new_tab'] ),
				'visibility' => $who,
			);
		}
	}

	// Built-ins added in a later version (or missing from the data) go on the end, switched off.
	foreach ( array_keys( $builtins ) as $id ) {
		if ( ! isset( $seen[ $id ] ) ) {
			$links[] = array(
				'type'    => 'builtin',
				'id'      => $id,
				'enabled' => false,
			);
		}
	}

	return $links;
}

/**
 * Sanitize callback for the links option. Warns once if links were dropped
 * to stay within the limit.
 *
 * @param mixed $value Submitted value.
 * @return array<int, array>
 */
function drift_clearview_sanitize_links( $value ) {
	static $warned = false;

	$trimmed = false;
	$links   = drift_clearview_normalize_links( $value, $trimmed );

	if ( $trimmed && ! $warned ) {
		$warned = true;
		add_settings_error(
			'drift_clearview_links',
			'drift_clearview_links_limit',
			sprintf(
				/* translators: %d: maximum number of links. */
				esc_html__( 'Only %d links can be shown at once, so the extra links were switched off or not saved.', 'drift-clearview' ),
				DRIFT_CLEARVIEW_MAX_LINKS
			),
			'warning'
		);
	}

	return $links;
}

/**
 * The saved links list, or the defaults when nothing has been saved.
 *
 * @return array<int, array>
 */
function drift_clearview_get_links_setting() {
	$saved = get_option( 'drift_clearview_links', false );

	return is_array( $saved ) ? drift_clearview_normalize_links( $saved ) : drift_clearview_default_links();
}

// ---------------------------------------------------------------------------
// Icons
// ---------------------------------------------------------------------------

/**
 * Inner markup of every icon (24×24, stroked). Fixed strings, never user
 * input. Kept here rather than loading an icon font so the links always
 * render, whether or not the active theme loads Dashicons on the front end.
 *
 * @return array<string, string>
 */
function drift_clearview_get_icon_paths() {
	return array(
		// Built-in links.
		'dashboard' => '<rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect>',
		'edit'      => '<path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"></path>',
		'plus'      => '<line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>',
		'customize' => '<rect x="3" y="3" width="18" height="18" rx="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line>',
		'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>',
		// Custom link choices.
		'link'      => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>',
		'external'  => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line>',
		'cart'      => '<circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>',
		'inbox'     => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>',
		'users'     => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
		'settings'  => '<line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line>',
		'chart'     => '<line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line>',
		'calendar'  => '<rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>',
		'image'     => '<rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline>',
		'comment'   => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>',
		'mail'      => '<rect x="2" y="4" width="20" height="16" rx="2"></rect><polyline points="22 6 12 13 2 6"></polyline>',
		'star'      => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>',
		'help'      => '<circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line>',
		'home'      => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>',
		'file'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>',
	);
}

/**
 * Icons offered for custom links, as key => label.
 *
 * @return array<string, string>
 */
function drift_clearview_get_custom_icons() {
	return array(
		'link'      => __( 'Link', 'drift-clearview' ),
		'external'  => __( 'External link', 'drift-clearview' ),
		'cart'      => __( 'Cart / orders', 'drift-clearview' ),
		'inbox'     => __( 'Inbox / form entries', 'drift-clearview' ),
		'mail'      => __( 'Email', 'drift-clearview' ),
		'comment'   => __( 'Comments', 'drift-clearview' ),
		'users'     => __( 'Users', 'drift-clearview' ),
		'settings'  => __( 'Settings', 'drift-clearview' ),
		'chart'     => __( 'Chart / stats', 'drift-clearview' ),
		'calendar'  => __( 'Calendar / bookings', 'drift-clearview' ),
		'image'     => __( 'Media', 'drift-clearview' ),
		'file'      => __( 'Document', 'drift-clearview' ),
		'star'      => __( 'Star', 'drift-clearview' ),
		'help'      => __( 'Help', 'drift-clearview' ),
		'home'      => __( 'Home', 'drift-clearview' ),
		'dashboard' => __( 'Dashboard', 'drift-clearview' ),
		'edit'      => __( 'Pencil', 'drift-clearview' ),
		'plus'      => __( 'Plus', 'drift-clearview' ),
	);
}

/**
 * Full inline SVG for an icon.
 *
 * @param mixed $icon Icon key.
 * @return string Fixed SVG markup, or '' for an unknown key.
 */
function drift_clearview_get_icon_svg( $icon ) {
	$paths = drift_clearview_get_icon_paths();
	if ( ! is_string( $icon ) || ! isset( $paths[ $icon ] ) ) {
		return '';
	}

	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $icon ] . '</svg>';
}

// ---------------------------------------------------------------------------
// Admin screen
// ---------------------------------------------------------------------------

add_action( 'admin_menu', 'drift_clearview_add_menu' );
/**
 * Adds Settings → Drift: Clearview.
 *
 * @return void
 */
function drift_clearview_add_menu() {
	add_options_page(
		__( 'Drift: Clearview', 'drift-clearview' ),
		__( 'Drift: Clearview', 'drift-clearview' ),
		drift_clearview_settings_capability(),
		DRIFT_CLEARVIEW_PAGE_SLUG,
		'drift_clearview_render_settings_page'
	);
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'drift_clearview_add_settings_link' );
/**
 * Adds a Settings link on the Plugins screen.
 *
 * @param array $links Action links.
 * @return array
 */
function drift_clearview_add_settings_link( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=' . DRIFT_CLEARVIEW_PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'drift-clearview' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

add_filter( 'admin_footer_text', 'drift_clearview_admin_footer_text' );
/**
 * Shows the copyright and company details in the admin footer, on this plugin's settings screen only.
 *
 * @param string $text Footer text.
 * @return string
 */
function drift_clearview_admin_footer_text( $text ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'settings_page_' . DRIFT_CLEARVIEW_PAGE_SLUG !== $screen->id ) {
		return $text;
	}
	return 'Copyright &copy; ' . esc_html( wp_date( 'Y' ) ) . ' Drift Creative Systems Ltd &middot; Registered in England and Wales, company number 17548220. Registered office: 167-169 Great Portland Street, 5th Floor, London W1W 5PF';
}

add_action( 'admin_enqueue_scripts', 'drift_clearview_admin_assets' );
/**
 * Styles, the core colour picker and the links list script, on the settings
 * screen only. Unlike a native type="color" input the picker has a hex text
 * field, so a value such as #E95A3A can be pasted straight in.
 *
 * @param string $hook_suffix Current admin page.
 * @return void
 */
function drift_clearview_admin_assets( $hook_suffix ) {
	if ( 'settings_page_' . DRIFT_CLEARVIEW_PAGE_SLUG !== $hook_suffix ) {
		return;
	}

	wp_enqueue_style( 'drift-clearview-admin', DRIFT_CLEARVIEW_URL . 'assets/admin.css', array( 'wp-color-picker' ), DRIFT_CLEARVIEW_VERSION );
	wp_enqueue_script( 'drift-clearview-admin', DRIFT_CLEARVIEW_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker', 'wp-a11y' ), DRIFT_CLEARVIEW_VERSION, true );
	wp_localize_script(
		'drift-clearview-admin',
		'driftClearviewAdmin',
		array(
			'max'     => DRIFT_CLEARVIEW_MAX_LINKS,
			/* translators: 1: links switched on, 2: maximum number of links. */
			'count'   => __( '%1$d of %2$d links in use.', 'drift-clearview' ),
			/* translators: 1: new position, 2: number of rows. */
			'moved'   => __( 'Moved to position %1$d of %2$d.', 'drift-clearview' ),
			'added'   => __( 'Link added.', 'drift-clearview' ),
			'removed' => __( 'Link removed.', 'drift-clearview' ),
		)
	);
}

/**
 * Drift monogram. Fixed markup; only the fill is variable.
 *
 * @param string $fill Fill colour.
 * @param int    $size Width and height in px.
 * @return string
 */
function drift_clearview_mark_svg( $fill = 'currentColor', $size = 32 ) {
	$paths = array(
		'M17 12H54C77 12 91 29 91 50C91 72 76 88 54 88H45L55 76H54C70 76 79 64 79 50C79 35 69 24 54 24H29V44H17Z',
		'M17 55H29L17 67Z',
		'M41 55H54L25 88H12Z',
		'M55 76H70L59 88H43Z',
	);

	$svg = sprintf( '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 100 100" aria-hidden="true" focusable="false">', (int) $size );
	foreach ( $paths as $d ) {
		$svg .= '<path fill="' . esc_attr( $fill ) . '" d="' . esc_attr( $d ) . '"/>';
	}

	return $svg . '</svg>';
}

/**
 * Placement radios.
 *
 * @return void
 */
function drift_clearview_render_placement_field() {
	$value = drift_clearview_sanitize_placement( get_option( 'drift_clearview_placement', DRIFT_CLEARVIEW_DEFAULT_PLACEMENT ) );
	?>
	<fieldset aria-describedby="drift-clearview-placement-description">
		<legend class="screen-reader-text"><?php esc_html_e( 'Link placement', 'drift-clearview' ); ?></legend>
		<?php foreach ( drift_clearview_get_placements() as $key => $label ) : ?>
			<label class="drift-clearview__radio">
				<input type="radio" name="drift_clearview_placement" value="<?php echo esc_attr( $key ); ?>" <?php checked( $value, $key ); ?> />
				<?php echo esc_html( $label ); ?>
			</label>
		<?php endforeach; ?>
	</fieldset>
	<p class="description" id="drift-clearview-placement-description">
		<?php esc_html_e( 'The corner of the screen where the icon links appear on the front end.', 'drift-clearview' ); ?>
	</p>
	<?php
}

/**
 * Hover colour picker.
 *
 * @return void
 */
function drift_clearview_render_hover_color_field() {
	$value = drift_clearview_sanitize_hover_color( get_option( 'drift_clearview_hover_color', DRIFT_CLEARVIEW_DEFAULT_HOVER_COLOR ) );
	?>
	<input
		type="text"
		id="drift-clearview-hover-color"
		name="drift_clearview_hover_color"
		class="drift-clearview-color"
		value="<?php echo esc_attr( $value ); ?>"
		data-default-color="<?php echo esc_attr( DRIFT_CLEARVIEW_DEFAULT_HOVER_COLOR ); ?>"
		maxlength="7"
		spellcheck="false"
		aria-describedby="drift-clearview-hover-color-description"
	/>
	<p class="description" id="drift-clearview-hover-color-description">
		<?php
		printf(
			/* translators: %s: default hex colour. */
			esc_html__( 'Background colour of the icon links on hover and keyboard focus. The icons are white, so pick a colour dark enough to read against. Defaults to %s.', 'drift-clearview' ),
			esc_html( DRIFT_CLEARVIEW_DEFAULT_HOVER_COLOR )
		);
		?>
	</p>
	<?php
}

/**
 * Drag handle and keyboard move buttons shared by every links row.
 *
 * @return void
 */
function drift_clearview_render_link_row_controls() {
	?>
	<span class="drift-clearview-link__handle hide-if-no-js" aria-hidden="true" title="<?php esc_attr_e( 'Drag to reorder', 'drift-clearview' ); ?>">
		<span class="dashicons dashicons-menu"></span>
	</span>
	<span class="drift-clearview-link__move hide-if-no-js">
		<button type="button" class="drift-clearview-link__up" aria-label="<?php esc_attr_e( 'Move up', 'drift-clearview' ); ?>"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>
		<button type="button" class="drift-clearview-link__down" aria-label="<?php esc_attr_e( 'Move down', 'drift-clearview' ); ?>"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>
	</span>
	<?php
}

/**
 * One built-in row in the links list.
 *
 * @param int   $index Row index, used only to group the row's fields.
 * @param array $item  Normalised built-in link.
 * @return void
 */
function drift_clearview_render_builtin_row( $index, $item ) {
	$builtins = drift_clearview_get_builtin_links();
	$builtin  = $builtins[ $item['id'] ];
	$name     = 'drift_clearview_links[' . $index . ']';
	$field_id = 'drift-clearview-link-' . $index;
	?>
	<li class="drift-clearview-link drift-clearview-link--builtin">
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>[type]" value="builtin" />
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>[id]" value="<?php echo esc_attr( $item['id'] ); ?>" />
		<?php drift_clearview_render_link_row_controls(); ?>
		<span class="drift-clearview-link__icon"><?php echo drift_clearview_get_icon_svg( $builtin['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG markup from drift_clearview_get_icon_paths(). ?></span>
		<span class="drift-clearview-link__body">
			<label class="drift-clearview-link__toggle-label" for="<?php echo esc_attr( $field_id ); ?>">
				<input type="checkbox" class="drift-clearview-link__toggle" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" aria-describedby="<?php echo esc_attr( $field_id ); ?>-description" <?php checked( $item['enabled'] ); ?> />
				<strong><?php echo esc_html( $builtin['label'] ); ?></strong>
			</label>
			<span class="description" id="<?php echo esc_attr( $field_id ); ?>-description"><?php echo esc_html( $builtin['description'] ); ?></span>
		</span>
	</li>
	<?php
}

/**
 * One custom link row. Also used, with $index '__INDEX__' and an empty item,
 * as the template the Add link button clones.
 *
 * @param int|string $index Row index, or '__INDEX__' for the template.
 * @param array      $item  Normalised custom link (may be empty for the template).
 * @return void
 */
function drift_clearview_render_custom_row( $index, $item = array() ) {
	$item = wp_parse_args(
		$item,
		array(
			'label'      => '',
			'url'        => '',
			'icon'       => 'link',
			'new_tab'    => false,
			'visibility' => 'all',
		)
	);

	$name     = 'drift_clearview_links[' . $index . ']';
	$field_id = 'drift-clearview-link-' . $index;
	$has_shop = class_exists( 'WooCommerce' );
	?>
	<li class="drift-clearview-link drift-clearview-link--custom">
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>[type]" value="custom" />
		<?php drift_clearview_render_link_row_controls(); ?>
		<span class="drift-clearview-link__icon drift-clearview-link__preview"><?php echo drift_clearview_get_icon_svg( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG markup from drift_clearview_get_icon_paths(). ?></span>
		<span class="drift-clearview-link__body">
			<span class="drift-clearview-link__fields">
				<span class="drift-clearview-link__field">
					<label for="<?php echo esc_attr( $field_id ); ?>-label"><?php esc_html_e( 'Label', 'drift-clearview' ); ?></label>
					<input type="text" class="regular-text" id="<?php echo esc_attr( $field_id ); ?>-label" name="<?php echo esc_attr( $name ); ?>[label]" value="<?php echo esc_attr( $item['label'] ); ?>" required />
				</span>
				<span class="drift-clearview-link__field drift-clearview-link__field--url">
					<label for="<?php echo esc_attr( $field_id ); ?>-url"><?php esc_html_e( 'URL', 'drift-clearview' ); ?></label>
					<input type="text" class="regular-text code" id="<?php echo esc_attr( $field_id ); ?>-url" name="<?php echo esc_attr( $name ); ?>[url]" value="<?php echo esc_attr( $item['url'] ); ?>" placeholder="edit.php?post_type=product" spellcheck="false" required />
				</span>
				<span class="drift-clearview-link__field">
					<label for="<?php echo esc_attr( $field_id ); ?>-icon"><?php esc_html_e( 'Icon', 'drift-clearview' ); ?></label>
					<select class="drift-clearview-link__icon-select" id="<?php echo esc_attr( $field_id ); ?>-icon" name="<?php echo esc_attr( $name ); ?>[icon]">
						<?php foreach ( drift_clearview_get_custom_icons() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $item['icon'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
				<span class="drift-clearview-link__field">
					<label for="<?php echo esc_attr( $field_id ); ?>-visibility"><?php esc_html_e( 'Who sees it', 'drift-clearview' ); ?></label>
					<select id="<?php echo esc_attr( $field_id ); ?>-visibility" name="<?php echo esc_attr( $name ); ?>[visibility]">
						<?php foreach ( drift_clearview_get_visibility_options() as $key => $option ) : ?>
							<?php
							// Shop managers only exist with WooCommerce; keep the choice if it was saved earlier.
							if ( 'shop' === $key && ! $has_shop && 'shop' !== $item['visibility'] ) {
								continue;
							}
							?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $item['visibility'], $key ); ?>><?php echo esc_html( $option['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
			</span>
			<span class="drift-clearview-link__footer">
				<label for="<?php echo esc_attr( $field_id ); ?>-new-tab">
					<input type="checkbox" id="<?php echo esc_attr( $field_id ); ?>-new-tab" name="<?php echo esc_attr( $name ); ?>[new_tab]" value="1" <?php checked( $item['new_tab'] ); ?> />
					<?php esc_html_e( 'Open in a new tab', 'drift-clearview' ); ?>
				</label>
				<button type="button" class="button-link button-link-delete drift-clearview-link__remove hide-if-no-js"><?php esc_html_e( 'Remove link', 'drift-clearview' ); ?></button>
			</span>
		</span>
	</li>
	<?php
}

/**
 * The Links card: the ordered list of built-in and custom links, the Add link
 * button, the row template and a hidden icon library the script copies
 * previews from.
 *
 * @return void
 */
function drift_clearview_render_links_card() {
	$links   = drift_clearview_get_links_setting();
	$enabled = 0;
	foreach ( $links as $item ) {
		if ( 'custom' === $item['type'] || ! empty( $item['enabled'] ) ) {
			++$enabled;
		}
	}
	?>
	<div class="drift-clearview__card drift-clearview__card--links">
		<h2><?php esc_html_e( 'Links', 'drift-clearview' ); ?></h2>
		<p class="description" id="drift-clearview-links-description">
			<?php esc_html_e( 'Switch the built-in links on or off, add your own, and drag them (or use the arrows) into the order they should appear. For your own links, a full URL such as https://example.com is used as it is, a path starting with / is on this site, and anything else, such as edit.php?post_type=product or admin.php?page=my-plugin, is a wp-admin screen.', 'drift-clearview' ); ?>
		</p>
		<p class="drift-clearview-links__count hide-if-no-js" id="drift-clearview-links-count">
			<?php
			printf(
				/* translators: 1: links switched on, 2: maximum number of links. */
				esc_html__( '%1$d of %2$d links in use.', 'drift-clearview' ),
				(int) $enabled,
				(int) DRIFT_CLEARVIEW_MAX_LINKS
			);
			?>
		</p>

		<ul class="drift-clearview-links" id="drift-clearview-links" aria-describedby="drift-clearview-links-description" data-next-index="<?php echo esc_attr( (string) count( $links ) ); ?>">
			<?php
			foreach ( $links as $index => $item ) {
				if ( 'builtin' === $item['type'] ) {
					drift_clearview_render_builtin_row( $index, $item );
				} else {
					drift_clearview_render_custom_row( $index, $item );
				}
			}
			?>
		</ul>

		<p class="hide-if-no-js">
			<button type="button" class="button" id="drift-clearview-add-link"><?php esc_html_e( 'Add link', 'drift-clearview' ); ?></button>
		</p>

		<template id="drift-clearview-link-template">
			<?php drift_clearview_render_custom_row( '__INDEX__' ); ?>
		</template>

		<div id="drift-clearview-icon-library" hidden>
			<?php foreach ( array_keys( drift_clearview_get_custom_icons() ) as $key ) : ?>
				<span data-icon="<?php echo esc_attr( $key ); ?>"><?php echo drift_clearview_get_icon_svg( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG markup from drift_clearview_get_icon_paths(). ?></span>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Settings screen.
 *
 * @return void
 */
function drift_clearview_render_settings_page() {
	if ( ! current_user_can( drift_clearview_settings_capability() ) ) {
		return;
	}
	?>
	<div class="wrap drift-clearview">
		<h1 class="drift-clearview__title">
			<span class="drift-clearview__mark"><?php echo drift_clearview_mark_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed markup, attributes escaped in drift_clearview_mark_svg(). ?></span>
			<?php echo esc_html( get_admin_page_title() ); ?>
		</h1>

		<form method="post" action="options.php">
			<?php settings_fields( DRIFT_CLEARVIEW_OPTION_GROUP ); ?>

			<div class="drift-clearview__card">
				<p class="drift-clearview__intro"><?php esc_html_e( 'Logged-in editors see a clean front end with no admin toolbar, plus a few small icon links in one corner: WP Dashboard, Edit Page on anything they can edit, and any others you switch on or add below. Visitors see no change.', 'drift-clearview' ); ?></p>
				<?php do_settings_sections( DRIFT_CLEARVIEW_PAGE_SLUG ); ?>
			</div>

			<?php drift_clearview_render_links_card(); ?>

			<?php
			/**
			 * Fires after the settings fields, inside the settings form.
			 */
			do_action( 'drift_clearview_settings_after_fields' );

			submit_button();
			?>
		</form>
	</div>
	<?php
}

// ---------------------------------------------------------------------------
// Hide the front-end admin toolbar
// ---------------------------------------------------------------------------

add_filter( 'show_admin_bar', 'drift_clearview_maybe_hide_admin_bar' );
/**
 * Turns the toolbar off on the front end for users who get the icon links.
 *
 * @param bool $show Whether to show the toolbar.
 * @return bool
 */
function drift_clearview_maybe_hide_admin_bar( $show ) {
	return drift_clearview_is_active() ? false : $show;
}

// ---------------------------------------------------------------------------
// Front-end icon links
// ---------------------------------------------------------------------------

add_action( 'wp_enqueue_scripts', 'drift_clearview_front_assets' );
/**
 * Front-end styles, only for users who see the links. Placement and hover
 * colour go in as inline CSS on top of the static stylesheet.
 *
 * @return void
 */
function drift_clearview_front_assets() {
	if ( ! drift_clearview_is_active() ) {
		return;
	}

	$positions = array(
		'top-right'    => 'top:0;right:0;',
		'top-left'     => 'top:0;left:0;',
		'bottom-right' => 'bottom:0;right:0;',
		'bottom-left'  => 'bottom:0;left:0;',
	);
	$placement = drift_clearview_sanitize_placement( get_option( 'drift_clearview_placement', DRIFT_CLEARVIEW_DEFAULT_PLACEMENT ) );
	$hover     = drift_clearview_sanitize_hover_color( get_option( 'drift_clearview_hover_color', DRIFT_CLEARVIEW_DEFAULT_HOVER_COLOR ) );

	wp_enqueue_style( 'drift-clearview', DRIFT_CLEARVIEW_URL . 'assets/clearview.css', array(), DRIFT_CLEARVIEW_VERSION );
	wp_add_inline_style(
		'drift-clearview',
		'#drift-clearview{' . $positions[ $placement ] . '}#drift-clearview .drift-clearview__link:hover,#drift-clearview .drift-clearview__link:focus{background:' . $hover . ';}'
	);
}

/**
 * Full URL of the page being viewed. Built from home_url()'s scheme and host
 * rather than HTTP_HOST, so a forged Host header can't change it.
 *
 * @return string
 */
function drift_clearview_current_url() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$home        = wp_parse_url( home_url() );

	if ( empty( $home['scheme'] ) || empty( $home['host'] ) ) {
		return home_url( '/' );
	}

	$origin = $home['scheme'] . '://' . $home['host'] . ( isset( $home['port'] ) ? ':' . $home['port'] : '' );

	return $origin . '/' . ltrim( $request_uri, '/' );
}

/**
 * Works out a built-in link for the current request, or null if it shouldn't
 * show (nothing to edit, or the user lacks the capability).
 *
 * @param string $id Built-in link ID.
 * @return array{id: string, label: string, url: string, icon: string, new_tab: bool}|null
 */
function drift_clearview_resolve_builtin_link( $id ) {
	$builtins = drift_clearview_get_builtin_links();
	if ( ! isset( $builtins[ $id ] ) ) {
		return null;
	}

	$url = '';
	switch ( $id ) {
		case 'dashboard':
			$url = admin_url();
			break;

		case 'edit':
			// get_edit_post_link() returns nothing when the user can't edit the post.
			$url = is_singular() ? (string) get_edit_post_link( get_queried_object_id(), '' ) : '';
			break;

		case 'new_post':
			$url = current_user_can( 'edit_posts' ) ? admin_url( 'post-new.php' ) : '';
			break;

		case 'customize':
			if ( wp_is_block_theme() ) {
				$url = current_user_can( 'edit_theme_options' ) ? admin_url( 'site-editor.php' ) : '';
			} elseif ( current_user_can( 'customize' ) ) {
				$url = add_query_arg( 'url', rawurlencode( drift_clearview_current_url() ), wp_customize_url() );
			}
			break;

		case 'logout':
			$url = wp_logout_url( drift_clearview_current_url() );
			break;
	}

	if ( '' === $url ) {
		return null;
	}

	return array(
		'id'      => $id,
		'label'   => $builtins[ $id ]['label'],
		'url'     => $url,
		'icon'    => $builtins[ $id ]['icon'],
		'new_tab' => false,
	);
}

/**
 * The links to print for the current user and request, in order.
 *
 * @return array<int, array{id: string, label: string, url: string, icon: string, new_tab: bool}>
 */
function drift_clearview_get_front_links() {
	$visibility = drift_clearview_get_visibility_options();
	$links      = array();

	foreach ( drift_clearview_get_links_setting() as $item ) {
		if ( 'builtin' === $item['type'] ) {
			$link = $item['enabled'] ? drift_clearview_resolve_builtin_link( $item['id'] ) : null;
		} else {
			$capability = $visibility[ $item['visibility'] ]['capability'];
			if ( '' !== $capability && ! current_user_can( $capability ) ) {
				continue;
			}

			$link = array(
				'id'      => 'custom',
				'label'   => $item['label'],
				'url'     => drift_clearview_resolve_link_url( $item['url'] ),
				'icon'    => $item['icon'],
				'new_tab' => $item['new_tab'],
			);
		}

		if ( $link ) {
			$links[] = $link;
		}
	}

	/**
	 * Filters the icon links before they are printed.
	 *
	 * Each link is an array with:
	 * - id      (string) Built-in ID (dashboard, edit, new_post, customize, logout) or 'custom'.
	 * - label   (string) Tooltip and screen reader text.
	 * - url     (string) Full URL.
	 * - icon    (string) Icon key, e.g. link, cart, inbox, users. Unknown keys fall back to link.
	 * - new_tab (bool)   Open in a new tab.
	 *
	 * @param array $links Links for the current user and request, in display order.
	 */
	$links = apply_filters( 'drift_clearview_links', $links );

	$clean = array();
	foreach ( (array) $links as $link ) {
		if ( ! is_array( $link ) || empty( $link['label'] ) || empty( $link['url'] ) || ! is_string( $link['label'] ) || ! is_string( $link['url'] ) ) {
			continue;
		}

		$clean[] = array(
			'id'      => isset( $link['id'] ) && is_string( $link['id'] ) ? $link['id'] : 'custom',
			'label'   => $link['label'],
			'url'     => $link['url'],
			'icon'    => isset( $link['icon'] ) && '' !== drift_clearview_get_icon_svg( $link['icon'] ) ? $link['icon'] : 'link',
			'new_tab' => ! empty( $link['new_tab'] ),
		);
	}

	return $clean;
}

add_action( 'wp_footer', 'drift_clearview_render_links' );
/**
 * Prints the icon links.
 *
 * @return void
 */
function drift_clearview_render_links() {
	if ( ! drift_clearview_is_active() ) {
		return;
	}

	$links = drift_clearview_get_front_links();
	if ( ! $links ) {
		return;
	}
	?>
	<div id="drift-clearview">
		<?php foreach ( $links as $link ) : ?>
			<a
				href="<?php echo esc_url( $link['url'] ); ?>"
				class="drift-clearview__link drift-clearview__link--<?php echo esc_attr( sanitize_html_class( $link['id'] ) ); ?>"
				title="<?php echo esc_attr( $link['label'] ); ?>"
				<?php if ( $link['new_tab'] ) : ?>
					target="_blank" rel="noopener"
				<?php endif; ?>
			>
				<?php echo drift_clearview_get_icon_svg( $link['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG markup from drift_clearview_get_icon_paths(). ?>
				<span class="drift-clearview__label">
					<?php echo esc_html( $link['label'] ); ?>
					<?php if ( $link['new_tab'] ) : ?>
						<?php esc_html_e( '(opens in a new tab)', 'drift-clearview' ); ?>
					<?php endif; ?>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}
