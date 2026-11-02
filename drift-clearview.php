<?php
/**
 * Plugin Name:       Drift: Clearview
 * Plugin URI:        https://driftcreativesystems.co.uk/
 * Description:       Hides the WordPress admin toolbar on the front end for logged-in editors and puts two small icon links in a corner instead: WP Dashboard and Edit Page. Choose the corner and hover colour under Settings → Drift: Clearview.
 * Version:           1.0.0
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

define( 'DRIFT_CLEARVIEW_VERSION', '1.0.0' );
define( 'DRIFT_CLEARVIEW_URL', plugin_dir_url( __FILE__ ) );
define( 'DRIFT_CLEARVIEW_OPTION_GROUP', 'drift_clearview_settings' );
define( 'DRIFT_CLEARVIEW_PAGE_SLUG', 'drift-clearview' );
define( 'DRIFT_CLEARVIEW_DEFAULT_PLACEMENT', 'top-right' );
define( 'DRIFT_CLEARVIEW_DEFAULT_HOVER_COLOR', '#c2410c' );

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
 * Registers both options and their fields.
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
 * Styles and the core colour picker, on the settings screen only. Unlike a
 * native type="color" input the picker has a hex text field, so a value such
 * as #E95A3A can be pasted straight in.
 *
 * @param string $hook_suffix Current admin page.
 * @return void
 */
function drift_clearview_admin_assets( $hook_suffix ) {
	if ( 'settings_page_' . DRIFT_CLEARVIEW_PAGE_SLUG !== $hook_suffix ) {
		return;
	}

	wp_enqueue_style( 'drift-clearview-admin', DRIFT_CLEARVIEW_URL . 'assets/admin.css', array( 'wp-color-picker' ), DRIFT_CLEARVIEW_VERSION );
	wp_enqueue_script( 'drift-clearview-admin', DRIFT_CLEARVIEW_URL . 'assets/admin.js', array( 'jquery', 'wp-color-picker' ), DRIFT_CLEARVIEW_VERSION, true );
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
		<?php esc_html_e( 'The corner of the screen where the WP Dashboard and Edit Page icons appear on the front end.', 'drift-clearview' ); ?>
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
				<p class="drift-clearview__intro"><?php esc_html_e( 'Logged-in editors see a clean front end with no admin toolbar, plus two small icons in one corner: WP Dashboard, and Edit Page on anything they can edit. Visitors see no change.', 'drift-clearview' ); ?></p>
				<?php do_settings_sections( DRIFT_CLEARVIEW_PAGE_SLUG ); ?>
			</div>

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
// Fixed Dashboard / Edit Page icon links (front end only)
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
 * Inline SVG icons. Kept here rather than loading an icon font so the links
 * always render, whether or not the active theme loads dashicons on the
 * front end.
 *
 * @param string $icon Icon key.
 * @return string
 */
function drift_clearview_get_icon_svg( $icon ) {
	$icons = array(
		'dashboard' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>',
		'edit'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>',
	);

	return isset( $icons[ $icon ] ) ? $icons[ $icon ] : '';
}

add_action( 'wp_footer', 'drift_clearview_render_links' );
/**
 * Prints the icon links. Edit Page only shows on a singular view the user
 * can edit (get_edit_post_link() returns nothing otherwise).
 *
 * @return void
 */
function drift_clearview_render_links() {
	if ( ! drift_clearview_is_active() ) {
		return;
	}

	$edit_url = is_singular() ? get_edit_post_link( get_queried_object_id(), '' ) : '';
	?>
	<div id="drift-clearview">
		<a href="<?php echo esc_url( admin_url() ); ?>" class="drift-clearview__link" title="<?php esc_attr_e( 'WP Dashboard', 'drift-clearview' ); ?>">
			<?php echo drift_clearview_get_icon_svg( 'dashboard' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, hardcoded SVG markup; no user input. ?>
			<span class="drift-clearview__label"><?php esc_html_e( 'WP Dashboard', 'drift-clearview' ); ?></span>
		</a>
		<?php if ( $edit_url ) : ?>
			<a href="<?php echo esc_url( $edit_url ); ?>" class="drift-clearview__link" title="<?php esc_attr_e( 'Edit Page', 'drift-clearview' ); ?>">
				<?php echo drift_clearview_get_icon_svg( 'edit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, hardcoded SVG markup; no user input. ?>
				<span class="drift-clearview__label"><?php esc_html_e( 'Edit Page', 'drift-clearview' ); ?></span>
			</a>
		<?php endif; ?>
	</div>
	<?php
}
