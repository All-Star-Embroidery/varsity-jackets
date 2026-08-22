# All Star Varsity Jackets v0.2.0-beta.8

## Changes

- Replaced the experimental updater with the same GitHub Release asset → `latest.json` → native WordPress updater architecture used by All Star Bulk Order.
- Added a 30-minute manifest cache, WordPress version-details metadata, automatic installation permission, and precise cache invalidation after this plugin upgrades.
- Added a structured ZIP importer driven by `schools.csv`.
- Automatically maps `{School Name} Logo` to the school logo and `{School Name} Front` to the jacket style featured image.
- Automatically maps Back, Letter, Sleeve, and Detail photography into the style gallery in that order.
- CSV import can populate mascot, location, district, description, school colors, and optional initial style details, price, and CTA.
- Existing legacy `image-manifest.csv` ZIP imports remain supported as a fallback.

## Compatibility

- WordPress 6.4+
- PHP 8.0+
- WooCommerce optional
