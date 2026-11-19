# Changelog

All notable changes to this project are documented here. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/).

## [1.1.1] - 2026-11-19

### Changed
- `Plugin URI` header now points at the product page, https://driftcreativesystems.co.uk/apps/clearview/.

## [1.1.0] - 2026-11-19

### Added
- Custom links under Settings → Drift: Clearview → Links: label, URL, icon (18 bundled inline SVGs), open in a new tab, and who sees it (everyone who sees the icons, editors, administrators, or shop managers when WooCommerce is active).
- Optional built-in links: New Post, Customise (Site Editor on block themes) and Log out (returns to the current page).
- One ordered list for built-in and custom links, reordered by dragging (`jquery-ui-sortable`) or with up/down buttons, with screen reader announcements through `wp-a11y`.
- Limit of 8 links in use (`DRIFT_CLEARVIEW_MAX_LINKS`), enforced in the sanitiser and on the settings screen.
- `drift_clearview_links` filter for the links about to be printed.
- `drift_clearview_links` option, removed on uninstall.

### Changed
- Settings and readme copy no longer say "two icons".
- The front-end icon bar wraps on narrow screens instead of overflowing.
- Each front-end link has a `drift-clearview__link--{id}` class.

Sites updating from 1.0.0 see no change until the links are edited: with nothing saved, only WP Dashboard and Edit Page show.

## [1.0.0] - 2026-11-02

First release as **Drift: Clearview**, a free WordPress.org plugin by Drift Creative Systems. Based on Bonsai No Toolbar Edit 1.4.2, which carries on separately for Bonsai client sites. There is no data migration between the two.

### Added
- Copyright and company details in the admin footer on Settings → Drift: Clearview, the README, readme.txt and the main file header.
- `drift_clearview_settings_after_fields` action for add-ons.
- `readme.txt`, `LICENSE` (GPLv2), `uninstall.php` (multisite aware), WordPress.org icon and banners, release workflow with optional SVN deploy.
- Visible keyboard focus ring on the icon links. The links are hidden when printing.

### Changed
- Renamed to Drift: Clearview: slug and text domain `drift-clearview`, prefix `drift_clearview_` / `DRIFT_CLEARVIEW_`, options `drift_clearview_placement` and `drift_clearview_hover_color`.
- Settings moved to Settings → Drift: Clearview, with the Drift monogram in orange (`#f97316`).
- Default hover colour is now `#c2410c` (white icons pass AA contrast on it) instead of the Bonsai pink.
- Front-end CSS moved from an inline `<style>` block in the footer to `assets/clearview.css`, enqueued only for users who see the links, with placement and hover colour added as inline CSS.
- Capabilities are read through `drift_clearview_capability()` / `drift_clearview_settings_capability()` when needed instead of constants set at load time, so filters in a theme's `functions.php` now take effect.
- Minimum PHP lowered to 7.4.

### Removed
- Plugin Update Checker and Composer. Updates come from WordPress.org.
- Bundled Bonsai Hub menu and suite installer.
