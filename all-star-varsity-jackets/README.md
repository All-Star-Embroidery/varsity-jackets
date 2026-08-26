# All Star Varsity Jackets

All Star Embroidery's modular WordPress/WooCommerce varsity-jacket manager.

**Current build:** `0.2.0-beta.9.1`

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

## GitHub → WordPress release architecture

Source lives in `all-star-varsity-jackets/`. GitHub Actions builds a purpose-made WordPress ZIP containing exactly one root folder named `all-star-varsity-jackets/`, validates it, publishes a versioned GitHub Release, uploads the ZIP as a Release asset, verifies the asset, and only then updates `latest.json`.

WordPress reads the organization-owned `latest.json` using `wp_remote_get()` with SSL verification, caches it for 1,800 seconds, and injects updates through the native plugin updater. Automatic installation is enabled for this plugin.

Repository/raw/source archive ZIPs are not used as WordPress packages.
