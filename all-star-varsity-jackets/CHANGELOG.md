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
- Added a GitHub Releases updater targeting `rolejarczyk/ASE.VarsityJackets`.
- Beta and stable update channels.
- Repository release workflow builds the installable ZIP from version tags and marks beta/alpha/RC tags as prereleases.

## 0.1.0-beta.2
- Rebuilt frontend toward approved varsity-jacket design.
- Removed red All Star defaults.
- Added legacy ZIP importer.
- Replaced Gutenberg placeholder cards with actual server-side previews.

## 0.1.0-beta.1
- Initial functional beta skeleton.
