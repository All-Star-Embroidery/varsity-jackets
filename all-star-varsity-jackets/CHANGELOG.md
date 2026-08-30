## 1.0.5

- Clarified customer-facing Varsity Jackets prices as **Starting at** prices because customization may increase the final total.
- Added the missing **Starting at** label above the jacket-detail popup price.
- Kept the existing Starting at treatment in style cards, selected-style pricing, and the sticky customization bar.
- Added responsive modal price-label styling.

## 1.0.3

- Fixed jacket detail/gallery images overflowing their intended media area on tablets and narrow portrait screens.
- The detail viewer now uses a responsive single-column tablet layout and force-contains all full-size images inside the gallery viewport.
- Clicking a gallery tile now opens that exact image in the detail viewer instead of always resetting to the Front image.
- Added a prominent **Customize Jacket** button beside **View Jacket Details** in Browse by School.
- The Customize Jacket link updates automatically when a different jacket style is selected.
- Added a sticky bottom conversion bar inside the jacket-detail modal so **Customize Jacket** remains visible while scrolling through details.
- The sticky modal bar can also show the current starting price beside the CTA.
- Improved thumbnail active states and narrow-screen action spacing.

## 1.0.2

- Fixed the Hero block's mobile controls not being registered in Gutenberg's JavaScript block definition.
- Mobile Hero settings now persist, rerender, and visibly update in the editor/frontend.
- Added **Use main Hero controls on mobile** for the two-block workflow: duplicate the Hero, hide one from mobile and the other from desktop/tablet, then turn this on for the mobile copy.
- With that option enabled, the normal Hero title size, kicker/body size, padding, feature scale, jacket scale, text/feature/jacket X/Y controls, glow controls, and appearance settings drive the mobile-only Hero copy directly.
- Keeping the option off preserves the independent responsive mobile controls for sites using one Hero block across all devices.

## 1.0.1

- Added global varsity-jacket WooCommerce product defaults with a $400 base price by default.
- Added optional per-style Base Price Override; blank styles inherit the global base price automatically.
- Added bulk Create / Sync All Styles, Create Missing Products, and Sync Linked Products actions.
- Product generation now carries over the school/style name, effective base price, short/full description, style features, featured image, gallery, product category, SKU, and the style-to-product relationship.
- Added configurable new-product status (Draft or Published), Varsity Jackets product category, and SKU prefix.
- Existing linked products can be synchronized in bulk instead of editing prices and media one-by-one.
- Individual style/product controls remain available for exceptions and manual review.

## 1.0.0 — Production Release

- Moved “Slide to find your school” beneath the school carousel, increased contrast, and retained its auto-disappearing behavior.
- Newark is now the default selected school whenever it is enabled; otherwise the first enabled school is used.
- Polished Available Customizations on phones with consistent typography, horizontal breathing room, and an easy-to-scan stacked list.
- Rebuilt the phone hero as an intentional mobile composition instead of a shrunken desktop layout.
- Added dedicated mobile controls for title size, padding, jacket area height, feature/jacket scale, and independent text/feature/jacket positioning.
- Mobile jacket imagery now uses contain behavior so the signature jacket is not unintentionally cropped.
- Preserved responsive-safe laptop and split-window behavior.
- Removed beta-facing dashboard language and completed a final frontend/admin responsiveness and production-readiness sweep.
- Retains structured ZIP/CSV imports, school/logo/mascot data, multiple jacket styles, gallery-first school browsing, WooCommerce integration, backups, and GitHub-powered updating.

## 0.2.0-beta.15

- Replaced the GitHub-Actions-dependent update path with a direct GitHub package transport.
- WordPress can now reconstruct a verified plugin ZIP from ordinary UTF-8 package chunks committed to the repository.
- Added SHA-256 verification before WordPress installs a chunk-based update.
- Retained genuine GitHub Release assets as a fallback transport.
- Automatic plugin updates remain enabled for All Star Varsity Jackets.
- Includes all beta.10 through beta.14 importer, editor, responsive hero, and gallery-first Browse by School improvements.

## 0.2.0-beta.14

- Rebuilt Browse by School around a gallery-first selected-style experience.
- Replaced the long school description/style-card grid with a compact style selector; switching Style 1/2/3 updates the full jacket gallery instantly.
- Added an always-visible Available Customizations panel for chenille, tackle twill, embroidery, patches, and names/numerals.
- Added featured/front + supporting Back/Letter/Sleeve/Detail gallery layout with role labels and a full-gallery modal.
- Added responsive layouts for desktop, split-window, tablet, and mobile so the school section uses available space instead of shrinking into a small card.

## 0.2.0-beta.13

- Added responsive-safe hero positioning so custom X/Y transforms cannot push the hero copy off-screen on laptop, tablet, split-window, or mobile widths.
- Progressively constrains content, feature-card, and jacket transforms as the viewport narrows while preserving the user's full desktop positioning.
- Refined intermediate 761–1180px breakpoints for better title scaling, gutters, jacket scale, and feature-card layout.
- Prevents oversized custom title/feature/jacket settings from causing clipping at half-width browser sizes.

## 0.2.0-beta.12

- Fixed School and Jacket Style edits not saving from the WordPress editor.
- Removed invalid nested admin forms that could break the main WordPress Update form and redirect users to the normal Blog Posts screen.
- Added explicit School Name and Style Name fields inside the plugin's own detail panels.
- Style names such as `Imported Jacket Gallery` can now be renamed normally.
- School details, branding/logo selection, mascot image, style description, gallery, features, pricing fallback, CTA, and visibility now persist when Update is clicked.
- Custom School/Style saves are kept on their own editor screen rather than being redirected to Posts.
- Duplicate and WooCommerce action buttons now submit through detached POST forms so they no longer interfere with WordPress saving.

## 0.2.0-beta.11

- Fixed UTF-8 BOM handling in `schools.csv`, preventing structured imports from silently falling back to the legacy image-only importer.
- School Mascot, Location, District, Description, colors, School Logo, and Mascot Image now populate correctly when rerunning a structured ZIP import.
- Added CSV support for `Features`, `Style Features`, `Feature List`, and `Style Details`.
- When style subtitle/description/features are blank, the importer now creates safe values derived only from the school/style names and image roles actually present.
- Existing schools/styles and media are updated/reused, so a failed or incomplete initial import can be rerun without intentionally duplicating content.

# Changelog

## 0.2.0-beta.10
- Multi-style school folder import support.
- Robust legacy filename aliases used by the real All Star archive.
- Multiple Detail images are preserved in the gallery.
- Import form now confirms the selected ZIP and shows an active uploading/importing state.
- Designed to work safely with smaller preprocessed import batches for shared hosting.

# 0.2.0-beta.9

- Added optional separate mascot artwork per school.
- Structured ZIP importer recognizes `{School Name} Mascot`.
- Logo and mascot images are optional; Front remains the jacket featured image and Back/Letter/Sleeve/Detail remain gallery images.

## 0.2.0-beta.8 — 2026-08-22
- Replaced the experimental repository/chunk updater with the same native GitHub Release asset architecture used by ASBO.
- Added a public `latest.json` manifest client with a 30-minute cache, native WordPress update metadata, version-details support, automatic installation permission, and precise cache clearing after this plugin upgrades.
- The updater now accepts only HTTPS GitHub `/releases/download/...` ZIP assets for installation packages.
- Added a structured school ZIP importer driven by `schools.csv` (with friendly filename aliases).
- The importer maps `{School Name} Logo` to the school logo, `{School Name} Front` to the style featured image, and Back/Letter/Sleeve/Detail to the gallery in that order.
- CSV fields can populate mascot, location, district, description, school colors, and optional initial style details/price/CTA.
- Structured imports safely create/update schools, reuse previously imported media by ZIP path, and report missing/duplicate role images without failing the whole import.
- Existing legacy `image-manifest.csv` ZIPs remain supported as a fallback.

# All Star Varsity Jackets — Changelog

## 0.2.0-beta.5
- Added a repository-manifest update channel (`update.json`) so WordPress can discover and install releases directly from GitHub without depending on GitHub Releases.
- Keeps GitHub Releases as a fallback update source.
- Reduced update cache to one hour; manual “Check GitHub Now” still forces an immediate refresh.


## 0.2.0-beta.4 — 2026-08-21

- Widened the school summary column so longer school names no longer run underneath the first jacket style card.
- Made the school title layout shrink-safe while preserving readable single-line names whenever space allows.
- Kept existing responsive tablet/mobile behavior unchanged.

## 0.2.0-beta.3 — 2026-08-21

This beta moves the plugin from a visual prototype into a complete working build that can be used to assemble the real Varsity Jackets page while the workflow is still allowed to evolve.

### Migration & school management
- Legacy varsity ZIP importer creates/matches school records and imports the old jacket photos into WordPress Media Library.
- Importer preserves the legacy image descriptions when `image-manifest.csv` is present.
- Re-running the same legacy ZIP reuses previously imported media instead of intentionally duplicating it.
- New **Visual Style Organizer** lets an admin drag imported images between Style 1 / Style 2 / Style 3 / additional style columns.
- The first image in an organizer column becomes the style card image; remaining images become that style's gallery.
- Add new style columns directly from the organizer.
- Duplicate an individual jacket style.
- Duplicate a school and its style structure without duplicating WooCommerce links.
- Drag-and-drop school ordering controls the storefront school selector order.
- One-click draft Varsity Jackets page generator inserts the full live Gutenberg experience.
- JSON backup / restore for plugin settings, schools, styles, image references, and product links.

### Frontend
- Multiple jacket styles per school with automatic Style 1 / Style 2 / Style 3 numbering based on drag order.
- School search, district filtering, mascot filtering, horizontal school selector, and school-specific gallery.
- Product-style cards with image, description, feature chips, optional starting price, and CTA.
- Style gallery/lightbox with main image and supporting images.
- School Gallery button now combines imagery across all styles for the selected school.
- All Star interface defaults use navy, gold, white, and warm cream — no red brand default.
- All Star signature jacket is configurable globally and remains separate from school-specific jackets.
- Responsive desktop/tablet/mobile layouts.

### Gutenberg
- Hero, Browse by School, Benefits Strip, and Full Varsity Experience blocks.
- Blocks render the **actual server-side frontend design inside Gutenberg**, including demo content before schools are imported.
- Per-block content overrides for hero/browser copy.
- Per-block controls for hero height/features, search, filters, prices, and style-card count while retaining global design defaults.

### WooCommerce
- Styles may remain showcase-only or link to an existing WooCommerce product.
- Create a safe **draft WooCommerce product** from a jacket style.
- Sync style image, gallery, short description, and simple-product fallback price to a linked product.
- Linked products supply frontend price and destination URL.
- Dedicated Style → Product Manager screen.

### Design & admin
- Backend intentionally uses a clean text-only plugin header — no substitute/fake All Star logo.
- Global design settings remain simple by default, with additional layout controls and advanced typography overrides tucked away.
- Added configurable content width, hero height, spacing, style-card count, school-tile width, radii, visibility toggles, and font inheritance.

### Updates / releases
- Added a GitHub Releases updater targeting `All-Star-Embroidery/varsity-jackets`.
- Beta and stable update channels.
- Repository release workflow builds the installable ZIP from version tags and marks beta/alpha/RC tags as prereleases.

## 0.1.0-beta.2
- Rebuilt frontend toward approved varsity-jacket design.
- Removed red All Star defaults.
- Added legacy ZIP importer.
- Replaced Gutenberg placeholder cards with actual server-side previews.

## 0.1.0-beta.1
- Initial functional beta skeleton.
