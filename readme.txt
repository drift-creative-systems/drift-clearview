=== Drift: Clearview ===
Contributors: driftcreativesystems
Tags: admin bar, toolbar, hide admin bar, edit link, front end
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hide the admin toolbar on the front end for logged-in editors and keep small corner icons: WP Dashboard, Edit Page and your own links.

== Description ==

The WordPress admin toolbar pushes your site down by 32 pixels and covers sticky headers, so logged-in editors never see the front end the way visitors do. Drift: Clearview removes the toolbar on the front end and puts small icons in one corner of the screen instead. By default there are two:

* **WP Dashboard**: back to wp-admin.
* **Edit Page**: straight to the editor for the post or page you're looking at. It only appears when there is something to edit and you're allowed to edit it, so it's hidden on archives, search results and 404 pages.

Switch on more built-in links (New Post, Customise or Site Editor, and Log out) or add links of your own, such as WooCommerce orders, form entries or a client's booking system. Up to eight links can show at once.

Features:

* Add your own links with a label, URL, icon, "open in a new tab" option and who can see them (everyone, editors, administrators or shop managers). URLs can be full links, paths on your site or wp-admin screens such as `edit.php?post_type=product`.
* Drag links into any order, or use the arrow buttons from the keyboard.
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
* `drift_clearview_links` (filter): the links about to be printed for the current user and page. Add, remove or reorder them. Each link is an array with `id`, `label`, `url`, `icon` and `new_tab`.
* `drift_clearview_settings_after_fields` (action): add fields to the settings form.

Example, to show the icons to administrators only:

`add_filter( 'drift_clearview_capability', function () { return 'manage_options'; } );`

Source code, issues and pull requests: [github.com/drift-creative-systems/drift-clearview](https://github.com/drift-creative-systems/drift-clearview).

= Copyright =

Copyright © 2026 Drift Creative Systems Ltd

Drift Creative Systems Ltd. Registered in England and Wales, company number 17548220. Registered office: 167-169 Great Portland Street, 5th Floor, London W1W 5PF

== Installation ==

1. In your WordPress admin, go to Plugins → Add New, search for "Drift: Clearview", then install and activate it.
2. View the front end of your site while logged in. The toolbar is gone and the icons are in the top right corner.
3. To change the corner, the hover colour or the links, go to Settings → Drift: Clearview.

== Frequently Asked Questions ==

= Who sees the icons? =

Logged-in users who can edit posts, which means authors, editors and administrators by default. Subscribers, contributors and logged-out visitors keep the normal front end. Developers can change this with the `drift_clearview_capability` filter.

= Why is there no Edit Page icon on this page? =

It only appears on a single post, page or custom post type entry that you're allowed to edit. Archives, the blog index, search results and 404 pages have nothing to edit.

= Can I add my own links? =

Yes. Under Settings → Drift: Clearview → Links, click Add link and give it a label, URL and icon. Up to eight links can be shown at once, counting the built-in ones you have switched on. Developers can also add links in code with the `drift_clearview_links` filter.

= How do I log out without the toolbar? =

Switch on the Log out link under Settings → Drift: Clearview → Links. It logs you out and brings you back to the page you were on.

= Does it hide the toolbar in wp-admin? =

No. Only the front end changes.

= Can I turn the toolbar back on for one user? =

Not per user. The plugin overrides the "Show Toolbar when viewing site" profile setting for everyone it applies to. Use the `drift_clearview_capability` filter to leave some roles out.

= The icons sit on top of my header or cookie banner =

Pick a different corner under Settings → Drift: Clearview.

== Screenshots ==

1. The icons in the top right corner of the front end.
2. Settings → Drift: Clearview.

== Changelog ==

= 1.1.0 =
* New: add your own links, each with a label, URL, icon, new-tab option and who can see it.
* New: optional built-in New Post, Customise / Site Editor and Log out links.
* New: drag (or use the arrow buttons) to put the links in any order. Up to eight links at once.
* New: `drift_clearview_links` filter.
* Sites updating from 1.0.0 keep showing only WP Dashboard and Edit Page until the settings are changed.

= 1.0.0 =
* First public release: front-end toolbar replaced with WP Dashboard and Edit Page icons, with a choice of corner and hover colour.

== Upgrade Notice ==

= 1.1.0 =
Adds custom links and optional New Post, Customise and Log out links. Nothing changes until you switch them on.

= 1.0.0 =
First public release.
