=== SVG Icon Block ===
Contributors: gkits, binsaifullah
Tags: svg, svg icon, icon block, svg block, gutenberg
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily add SVG icon to your Gutenberg block editor with this SVG Icon Block plugin. 

== Description ==
SVG Icon Block is a custom Gutenberg block plugin that allows you to add SVG icon to your Gutenberg block editor. It comes with a lot of customization options, as a result, you can customize it as per your needs.

### Video Tutorial 
[youtube http://www.youtube.com/watch?v=K7iUgJU42GI]

== Features ==
* Easy to use
* Simple, light-weight and fast
* Font Awesome SVG Icon ( not font library, rather direct SVG)
* **Direct SVG upload option**
* **SVG format support for direct upload**
* **My Icons library** - save your own SVG code once and reuse it in any block
* **Rotate, Flip (Horizontal & Vertical)** features are available
* No third-party dependencies.
* Only SVG codes are rendered.

### My Icons
Paste your SVG code in the Custom SVG window, turn on **Add to My Icons**, give it a label, and the icon is saved to your site. A **My Icons** button then appears in the block placeholder so you can pick that icon again in any post or page, without pasting the code a second time.

The library is shared by everyone on the site who can edit posts, so a logo or brand icon saved by one author is available to the whole team. Saved icons can be searched and removed from the same window.

Every icon is sanitized before it is stored, using the same security checks applied to uploaded SVG files: scripts, event handlers and unsafe links are stripped out.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/svg-icon-block` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress

== Screenshots ==


== Changelog ==
= 1.1.0 =
* Added: My Icons library - save a custom SVG with "Add to My Icons" and reuse it in any block
* Added: My Icons button in the block placeholder to pick a saved icon, with search and remove
* Fixed: SVG upload no longer failed on icons exported from Illustrator, Inkscape and similar tools
* Fixed: An SVG that could not be parsed is now rejected with a clear message instead of being saved empty
* Fixed: SVG uploads sent through the REST API (drag and drop into the editor) are accepted
* Fixed: PHP notices while preparing SVG attachments, which could break the upload response
* Fixed: Link is no longer clickable inside the editor, so an accidental click does not navigate away
* Improved: SVG sanitizer rewritten to an allow-list, stripping scripts, event handlers, unsafe links and external references
* Improved: SVG width, height and previews now display correctly in the media library

= 1.0.6 =
* Compatibility check

= 1.0.5 = 
* Transform features (rotate, flip) are added 
* Dynamic CSS codes are optimized 

= 1.0.4 =
* Compatibility with WordPress 6.8

= 1.0.3 =
* Added: Edit SVG Icon option at sidebar
* Improved: Search functionality for icons
* Optimized: Codes are optimized

= 1.0.2 =
* Added: Direct SVG upload option
* Added: SVG format support for direct upload

= 1.0.1 =
* Compatibility with WordPress 6.7.1

= 1.0.0 =
* Initial Release

