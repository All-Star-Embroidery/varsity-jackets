# All Star Varsity Jackets

All Star Embroidery's modular WordPress/WooCommerce varsity-jacket manager.

**Current build:** `1.0.1`

## Permanent plugin identity

- Plugin name: **All Star Varsity Jackets**
- Slug/folder: `all-star-varsity-jackets`
- Main file: `all-star-varsity-jackets.php`
- Release repository: `All-Star-Embroidery/varsity-jackets`
- Release tag prefix: `asevj-v`
- Public updater manifest: `latest.json`

The folder and main file stay stable between releases so WordPress upgrades in place.

## School import ZIP

The preferred importer format is:

```text
Varsity-Import.zip
├── schools.csv
├── Crooksville/
│   ├── Crooksville Logo.png
│   ├── Crooksville Front.jpg
│   ├── Crooksville Back.jpg
│   ├── Crooksville Letter.jpg
│   ├── Crooksville Sleeve.jpg
│   └── Crooksville Detail.jpg
└── ...
```

`School Name` is the only required CSV column. Optional columns: `Mascot`, `Location`, `District`, `Description`, `Primary Color`, `Secondary Color`, `Accent Color`, `Style Name`, `Style Subtitle`, `Style Description`, `Price`, and `CTA`.

Image mapping is deterministic:

- Logo → school logo
- Front → style featured/product image
- Back → gallery
- Letter → gallery
- Sleeve → gallery
- Detail → gallery

Legacy downloader ZIPs with `image-manifest.csv` are still accepted as a fallback.

## GitHub → WordPress update architecture

Source lives in `all-star-varsity-jackets/`. WordPress reads the public `latest.json` manifest using `wp_remote_get()` with SSL verification, caches it for 1,800 seconds, and injects updates through the native plugin updater. Automatic installation is enabled for this plugin.

The primary v1 transport stores the purpose-made WordPress ZIP as normal base64 text chunks under `packages/<version>/`. WordPress reconstructs the ZIP locally and verifies the SHA-256 checksum before installation. This means publishing an update does not depend on GitHub Actions.

A genuine versioned GitHub Release `.zip` remains supported as a fallback package transport.


## WooCommerce bulk product setup

Version 1.0.1 adds a bulk style-to-product workflow under **All Star Jackets → WooCommerce**.

- Default varsity jacket base price: **$400**
- Optional per-style Base Price Override
- Create all missing WooCommerce products in one action
- Synchronize all linked products in one action
- Automatic product image/gallery/category/SKU/style relationship setup
- New products can default to Draft for review or Published
