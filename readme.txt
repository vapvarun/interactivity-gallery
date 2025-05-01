=== Interactivity Gallery ===
Contributors: vapvarun
Tags: gallery, media, lightbox, interactivity api, block
Requires at least: 6.5
Tested up to: 6.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A modern gallery plugin that displays attached media with lightbox functionality and pagination using the WordPress Interactivity API.

== Description ==

Interactivity Gallery is a modern WordPress plugin that provides both a shortcode and block editor support to display all attached media to a post with lightbox functionality and pagination.

The plugin is built using the WordPress Interactivity API, a modern JavaScript framework included in WordPress 6.5+, which allows for dynamic interactivity without the need for additional JavaScript libraries.

**Features:**

* Display attached media in a responsive grid layout
* Lightbox support for images
* Pagination for large media collections
* Native support for various media types (images, video, audio, files)
* Configurable number of columns and items per page
* Available as both a shortcode and a block for the block editor

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/interactivity-gallery` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the shortcode `[interactivity_gallery]` in your posts or pages, or use the 'Interactivity Gallery' block in the block editor.

== Frequently Asked Questions ==

= Does this plugin require any additional libraries? =

No, it uses the WordPress Interactivity API which is included in WordPress 6.5 and later.

= Can I display media from a different post? =

Yes, you can specify a post ID using the shortcode attribute `post_id` or through the block settings.

= How can I customize the number of columns? =

You can set the number of columns using the shortcode attribute `columns` or through the block settings.

== Shortcode Usage ==

The plugin provides a shortcode to easily add a gallery to your posts or pages:

`[interactivity_gallery post_id="123" per_page="12" columns="3"]`

Parameters:

* `post_id` (optional) - ID of the post to display attached media from. Default: current post ID
* `per_page` (optional) - Number of items to display per page. Default: 12
* `columns` (optional) - Number of columns in the grid. Default: 3

== Screenshots ==

1. Gallery display with various media types
2. Lightbox view of an image
3. Block editor settings

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of the Interactivity Gallery plugin.