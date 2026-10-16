=== PDF Gallery Viewer ===
Contributors: panezio923
Tags: pdf, gallery, responsive, modal, javascript
Stable tag: 1.8.1
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donation: https://ko-fi.com/panezio

A simple plugin that creates a responsive PDF gallery with live thumbnail previews and a modal PDF viewer.

== Description ==

PDF Gallery Viewer allows you to easily embed PDF galleries with live previews. It uses PDF.js to render the first page of each PDF directly in the browser, without relying on server-side libraries like Imagick. Each item includes a customizable title and a “View” button that opens the PDF in a modal viewer.

The layout is responsive and adapts from a multi-column grid to a single-column stack on smaller screens.

**Key Features:**

* Live thumbnail of first PDF page (via PDF.js) — lazy-loaded via IntersectionObserver
* Responsive layouts (Grid, List, or Slider)
* Modal viewer with zoom, navigation, and download
* Gutenberg block with layout selector and drag-and-drop file integration
* Tooltip + truncation for long titles
* Dynamic shortcode generation by category, tag, or post
* Sorting UI (Title / Filename / Manual drag & drop)
* Manual ordering with FIFO/LIFO behavior for newly added items
* Zero external requests — all assets (PDF.js, Font Awesome, Swiper.js) served locally
* Assets only load on pages that contain a gallery
* Settings page (Settings > PDF Gallery Viewer) for site-wide accent color, surface color, text color, corner radius, and default layout
* Per-gallery accent color override via the `accent_color` shortcode/block attribute

== Installation ==

1. Upload the plugin to `/wp-content/plugins/pdf-gallery-viewer/`
2. Activate it via the WordPress Plugins menu
3. Use the `[pdf_gallery]` or `[pdf_gallery_container]` shortcodes in posts or pages

== Usage ==

Basic usage:

[pdf_gallery url="https://example.com/doc.pdf" title="Document Title"]

Multiple items in a container:

[pdf_gallery_container layout="grid"]
[pdf_gallery url="..." title="..."]
[pdf_gallery url="..." title="..."]
[/pdf_gallery_container]

Sorting / ordering:

[pdf_gallery_container layout="grid" order_by="title" order="asc" ui="1"]
...
[/pdf_gallery_container]

Manual ordering (UI drag & drop) with FIFO/LIFO for newly added items:

[pdf_gallery_container id="my-gallery" layout="grid" order_by="manual" manual_mode="fifo" ui="1"]
...
[/pdf_gallery_container]

IMPORTANT: Manual ordering is managed by admins and saved server-side (post meta) so the order is the same for all visitors.
For reliable persistence, always set a stable id="..." on each container (the Gutenberg block does this automatically).

Dynamic by category:

[pdf_gallery_container category="brochures" layout="slider"]

Per-gallery accent color (overrides the site-wide default set in Settings > PDF Gallery Viewer):

[pdf_gallery_container layout="grid" accent_color="#ff6600"]
...
[/pdf_gallery_container]

== Frequently Asked Questions ==

= Does this plugin require Imagick? =
No. Previews are generated client-side using PDF.js.

= Will this work on mobile? =
Yes, all layouts and the modal are responsive.

= Can I add multiple PDFs? =
Yes, using either multiple shortcodes or the Gutenberg block.

== Screenshots ==

1. Admin PDF list — manage, edit, and delete gallery items
2. Responsive gallery grid layout
3. Gutenberg block editor — inserting a PDF Gallery block
4. Modal viewer with zoom, navigation, and download controls
5. Slider layout (Swiper.js)
6. Tooltip and truncation for long titles

== Changelog ==

= 1.8.1 =
* Confirmed compatibility with WordPress 7.0
* Fixed: screenshot captions in the plugin listing were shifted and no longer matched the actual screenshots; added the missing 6th screenshot (tooltip/truncation demo)

= 1.8.0 =
* Added a Settings page (Settings > PDF Gallery Viewer) for site-wide accent color, surface color, text color, and corner radius
* Added a default layout setting (grid/list/slider)
* Added a per-gallery `accent_color` override, available as a shortcode attribute and a Gutenberg block color control
* Manual drag & drop ordering is now fully functional: dragging reorders items, "Salva ordine" saves the order server-side, and it now correctly persists across page reloads
* Fixed: saved manual order was being ignored on reload for galleries built with the Gutenberg block
* Fixed: button hover color and icon color did not follow the accent/text color settings (were hardcoded)
* Fixed: block-presence check used the wrong block name and never matched (asset loading already worked via the shortcode check, so no user-facing effect)

= 1.7.1 =
* Added missing Text Domain header for translations
* Removed unused internal global variable
* Documented why manual drag & drop ordering controls remain disabled (client-side wiring incomplete)

= 1.7.0 =
* Performance: thumbnails now lazy-loaded via IntersectionObserver (renders only when item enters viewport)
* Performance: all scripts and styles skipped on pages without a gallery
* Privacy: Font Awesome now served locally — no CDN requests
* Privacy: PDF.js worker now served locally — no CDN requests
* All external network dependencies removed

= 1.6.3 =
* Fixed: disabled UI order controls

= 1.6.2 =
* Added filename visibility in Gutenberg block selection list
* Added numeric ordering for PDFs (admin-defined, persistent)
* Removed FIFO/LIFO ordering logic
* Fixed manual ordering issues in block editor
* Fixed frontend sorting UI buttons not working
* Improved list view readability (title + actions layout)
* General UI/UX refinements and accessibility improvements

= 1.5.0 =
* Added sorting UI (Title / Filename / Manual)
* Added admin-managed manual ordering via drag & drop (saved server-side, same order for everyone)
* Added FIFO/LIFO option for newly added items when manual ordering is enabled

= 1.3.0 =
* Added dynamic gallery support by category, tag, and post ID
* Added layout selector: Grid, List, Slider
* Integrated Swiper.js for modern carousel layout
* Added title truncation and tooltips
* Improved shortcode output and styling

= 1.2.1 =
* Integrated Font Awesome for icons

= 1.2.0 =
* Removed Imagick dependency
* Live thumbnail previews using PDF.js
* Modal viewer with zoom, navigation, and download
* Initial Gutenberg block with visual editing

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.8.1 =
Confirmed compatible with WordPress 7.0. Also fixes mismatched screenshot captions in the .org listing.

= 1.8.0 =
New Settings page for site-wide colors/layout plus a per-gallery accent color override. Manual drag & drop ordering now works end-to-end and persists correctly on reload.

= 1.7.0 =
Performance and privacy release. All assets now served locally (no CDN). Thumbnails lazy-load on scroll. Scripts skip on pages without a gallery.

= 1.6.0 =
Manual ordering is now managed by admins and saved server-side (same order for all visitors). Gutenberg block now uses a stable container id.

= 1.5.0 =
Adds sorting controls, manual drag & drop ordering, and FIFO/LIFO behavior for new items in manual mode.

= 1.3.0 =
Adds dynamic galleries, new layout selector, slider, and UI improvements.

= 1.2.1 =
Font Awesome icons added.

= 1.2.0 =
Major front-end upgrade. Imagick removed. Modal viewer added.
