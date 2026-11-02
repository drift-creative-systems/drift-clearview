=== Drift: Clearview ===
Contributors: driftcreativesystems
Tags: admin bar, toolbar, hide admin bar, edit link, front end
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hide the admin toolbar on the front end for logged-in editors and keep two small corner icons: WP Dashboard and Edit Page.

== Description ==

The WordPress admin toolbar pushes your site down by 32 pixels and covers sticky headers, so logged-in editors never see the front end the way visitors do. Drift: Clearview removes the toolbar on the front end and puts two small icons in one corner of the screen instead:

* **WP Dashboard**: back to wp-admin.
* **Edit Page**: straight to the editor for the post or page you're looking at. It only appears when there is something to edit and you're allowed to edit it, so it's hidden on archives, search results and 404 pages.

Features:

* Choose the corner: top right, top left, bottom right or bottom left.
* Choose the hover colour to match the site, with the colour picker built into WordPress. You can paste in a hex value.
* The icons are inline SVGs, so they show even if your theme doesn't load Dashicons on the front end.
* Each icon has a tooltip and screen reader text.
* wp-admin is untouched, and so is the front end for visitors and users who can't edit content.
* Light: one small stylesheet, loaded only for users who see the icons.
* Everything is removed when you delete the plugin.

= Privacy =

Drift: Clearview doesn't connect to any external service, track you or collect data. Visitors who aren't logged in get nothing from it.

= For developers =

* `drift_clearview_capability` (filter): the capability a user needs to lose the toolbar and see the icons. Default `edit_posts` (authors and above).
* `drift_clearview_settings_capability` (filter): the capability needed for Settings → Drift: Clearview. Default `manage_options`.
* `drift_clearview_settings_after_fields` (action): add fields to the settings form.

Example, to show the icons to administrators only:

`add_filter( 'drift_clearview_capability', function () { return 'manage_options'; } );`

Source code, issues and pull requests: [github.com/drift-creative-systems/drift-clearview](https://github.com/drift-creative-systems/drift-clearview).

= Copyright =

Copyright © 2026 Drift Creative Systems Ltd

Drift Creative Systems Ltd. Registered in England and Wales, company number 17548220. Registered office: 167-169 Great Portland Street, 5th Floor, London W1W 5PF

== Installation ==

1. In your WordPress admin, go to Plugins → Add New, search for "Drift: Clearview", then install and activate it.
2. View the front end of your site while logged in. The toolbar is gone and the two icons are in the top right corner.
3. To change the corner or the hover colour, go to Settings → Drift: Clearview.

== Frequently Asked Questions ==

= Who sees the icons? =

Logged-in users who can edit posts, which means authors, editors and administrators by default. Subscribers, contributors and logged-out visitors keep the normal front end. Developers can change this with the `drift_clearview_capability` filter.

= Why is there no Edit Page icon on this page? =

It only appears on a single post, page or custom post type entry that you're allowed to edit. Archives, the blog index, search results and 404 pages have nothing to edit.

= Does it hide the toolbar in wp-admin? =

No. Only the front end changes.

= Can I turn the toolbar back on for one user? =

Not per user. The plugin overrides the "Show Toolbar when viewing site" profile setting for everyone it applies to. Use the `drift_clearview_capability` filter to leave some roles out.

= The icons sit on top of my header or cookie banner =

Pick a different corner under Settings → Drift: Clearview.

== Screenshots ==

1. The two icons in the top right corner of the front end.
2. Settings → Drift: Clearview.

== Changelog ==

= 1.0.0 =
* First public release: front-end toolbar replaced with WP Dashboard and Edit Page icons, with a choice of corner and hover colour.

== Upgrade Notice ==

= 1.0.0 =
First public release.
