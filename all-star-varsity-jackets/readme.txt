=== All Star Varsity Jackets ===
Contributors: allstar
Tags: varsity jackets, schools, embroidery, woocommerce, gutenberg
Requires at least: 6.4
Requires PHP: 8.0
Stable tag: 1.0.7

School-based varsity jacket collections for All Star Embroidery with multiple styles per school, live Gutenberg previews, structured school imports, and optional WooCommerce products.

== Description ==

All Star Varsity Jackets manages School -> Jacket Styles -> optional WooCommerce Product.

The structured importer accepts a ZIP containing schools.csv plus school image folders. Logo images become school logos, Front images become style featured images, and Back/Letter/Sleeve/Detail images become the ordered style gallery.

The plugin uses the organization-owned public latest.json manifest and GitHub Release assets for native WordPress updates and automatic installation.

== Installation ==

1. Upload and activate the plugin ZIP.
2. Open All Star Jackets > Import / Export.
3. Upload the structured school ZIP.
4. Add the Full Varsity Jackets Experience block to the desired page.

== Changelog ==

= 1.0.4 =
* Migration patch that permanently points the plugin updater to All-Star-Embroidery/varsity-jackets.
* Clears pre-migration updater caches so WordPress does not keep stale repository metadata.
* Retains transitional acceptance of the old personal release URL while all future manifests and releases come from the organization repository.

= 1.0.3 =
* Fixed responsive tablet jacket-detail images.
* Added Customize Jacket beside View Jacket Details.
* Added a sticky Customize Jacket action inside the detail modal.

= 1.0.2 =
* Fixed mobile Hero controls in Gutenberg.
* Added a mode for dedicated mobile-only Hero copies to use the normal Hero positioning/size controls.

= 1.0.1 =
* Added a $400 default base price and per-style price overrides.
* Added bulk WooCommerce product creation and synchronization for varsity jacket styles.
* Automatically syncs product names, pricing, descriptions, images, galleries, category, SKU, and style links.

= 1.0.0 =
* Production release with final responsive hero/mobile controls, Newark default school, polished school carousel hint, gallery-first style selection, mobile customization spacing, structured importer, editor fixes, and GitHub updater.

= 0.2.0-beta.12 =
* Fix School and Jacket Style editing/saving in WordPress.
* Add explicit School Name and Style Name fields.
* Prevent custom data editors from redirecting to Blog Posts after Update.
* Remove nested admin forms that interfered with normal WordPress saves.

= 0.2.0-beta.8 =
* Rebuilt the GitHub updater around real GitHub Release assets and latest.json.
* Added deterministic CSV + school-folder ZIP importing.
* Maps Logo, Front, Back, Letter, Sleeve, and Detail photography automatically.
* Preserves the legacy gallery importer as a fallback.


= 1.0.5 =
* Clarified all Varsity Jackets module prices as Starting at prices.
* Added Starting at above the jacket-detail popup price to match the selected-style and sticky CTA displays.

= 1.0.6 =
* Compacted Browse by School and moved Available Customizations beneath the style selector.
* Gave the jacket gallery more horizontal room and reduced excess vertical space.
* Made customization options horizontally swipeable on smaller screens.

= 1.0.7 =
* Reduced Browse by School gallery height to minimize scrolling.
* Preserved uncropped jacket images with responsive sizing.
