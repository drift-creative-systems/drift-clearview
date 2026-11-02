![Drift Creative Systems](.github/banner.jpg)

# Drift: Clearview

Free WordPress plugin by **Drift Creative Systems**. Hides the admin toolbar on the front end for logged-in editors and puts two small icon links in a corner instead: **WP Dashboard** and **Edit Page**.

- WordPress.org slug: `drift-clearview` (submission pending)
- Licence: GPL-2.0-or-later
- Requires WordPress 6.0+, PHP 7.4+

The user-facing documentation is [readme.txt](readme.txt), which WordPress.org displays. This file is for developers.

## How it works

- `show_admin_bar` returns `false` on the front end for users with `drift_clearview_capability()` (default `edit_posts`). wp-admin is untouched.
- `wp_footer` prints `#drift-clearview` with two links: `admin_url()` and, on singular views, `get_edit_post_link()`. The Edit link is left out when that returns nothing (no post, or the user can't edit it).
- Icons are hardcoded inline SVGs, not Dashicons, so they render on themes that don't load Dashicons on the front end. Each link has a `title` and visually hidden text using its own class, not `.screen-reader-text`, which many themes don't define.
- `assets/clearview.css` is enqueued only for users who see the links. The chosen corner and hover colour are added with `wp_add_inline_style()`.
- Settings → Drift: Clearview uses the Settings API and core's `wp-color-picker`, which takes pasted hex values.

## Options

| Option | Type | Values |
|---|---|---|
| `drift_clearview_placement` | string | `top-right` (default), `top-left`, `bottom-right`, `bottom-left` |
| `drift_clearview_hover_color` | string | Hex colour, default `#c2410c` |

Both are removed in `uninstall.php`, on every site in a multisite network.

## Hooks

| Hook | Type | Purpose |
|---|---|---|
| `drift_clearview_capability` | filter | Who loses the toolbar and gets the links. Default `edit_posts` |
| `drift_clearview_settings_capability` | filter | Who can view and save the settings screen. Default `manage_options` |
| `drift_clearview_settings_after_fields` | action | Extra fields inside the settings form |

Capabilities are read when needed, not at load time, so filters added in a theme's `functions.php` still apply.

```php
// Show the icons to administrators only.
add_filter( 'drift_clearview_capability', function () {
	return 'manage_options';
} );
```

## Development

- Coding standard: `phpcs.xml.dist` (WordPress, prefixes `drift_clearview` / `DRIFT_CLEARVIEW`).
- Run **Plugin Check** before every release. The PHPCS-based checks don't work in WordPress Playground (its filesystem can't `flock()`), so use a real install, e.g. WP-CLI with the SQLite drop-in:
  ```sh
  wp plugin install plugin-check --activate
  wp plugin check drift-clearview --include-low-severity-errors --include-low-severity-warnings \
    --require=./wp-content/plugins/plugin-check/cli.php
  ```
  Check a copy built from `.distignore`, not the repo, so dev files don't trip the `file_type` check.
- Directory guidelines review: the `wp-plugin-directory-guidelines` Claude Code skill.

## Releasing

1. Bump the version in **three** places: the `Version:` header, `DRIFT_CLEARVIEW_VERSION` and `Stable tag:` in `readme.txt`. Add a changelog entry to both `readme.txt` and `CHANGELOG.md`.
2. Merge `develop` → `main`, then publish a GitHub release tagged `vX.Y.Z`.
3. `.github/workflows/release.yml` checks the three versions match, builds `drift-clearview.zip` from `.distignore` and attaches it to the release.
4. Once approved on WordPress.org, set the repo variable `WPORG_DEPLOY=true` and the secrets `SVN_USERNAME` / `SVN_PASSWORD`. Releases then deploy to SVN automatically, and readme/asset changes on `main` sync on their own (`wporg-assets.yml`).

WordPress.org assets (icon, banners, screenshots) live in `.wordpress-org/`. Screenshots still need adding as `screenshot-1.png` (front-end icons) and `screenshot-2.png` (settings screen) to match `readme.txt`.

## Licence

GPL-2.0-or-later. See [LICENSE](LICENSE).

Copyright © 2026 Drift Creative Systems Ltd

Drift Creative Systems Ltd. Registered in England and Wales, company number 17548220. Registered office: 167-169 Great Portland Street, 5th Floor, London W1W 5PF
