# All Star Varsity Jackets

Development and release repository for the All Star Embroidery Varsity Jackets WordPress/WooCommerce plugin.

Current release line: **0.2.0-beta.8**.

## Stable WordPress plugin identity

- Plugin name: `All Star Varsity Jackets`
- Plugin slug/folder: `all-star-varsity-jackets`
- Main file: `all-star-varsity-jackets.php`
- Update URI: `https://github.com/rolejarczyk/ASE.VarsityJackets`

The plugin folder and basename remain stable between releases so WordPress upgrades the existing installation rather than creating a second plugin directory.

## GitHub → WordPress release architecture

The release pipeline mirrors the working All Star Bulk Order updater architecture:

1. Plugin source lives in `all-star-varsity-jackets/` on `main`.
2. `.github/workflows/publish-plugin-release.yml` validates the source and builds a purpose-built WordPress ZIP.
3. The workflow verifies that the ZIP contains exactly one top-level `all-star-varsity-jackets/` directory and the expected main PHP file.
4. GitHub creates a versioned Release and uploads the ZIP as a genuine **Release asset**.
5. Only after that asset is downloaded and revalidated does the workflow update `latest.json`.
6. Installed WordPress copies read `latest.json` and use the `/releases/download/...` asset URL through the native WordPress updater.
7. The plugin supplies version details, permits automatic updates for itself, caches the manifest for 30 minutes, and clears that cache after a successful plugin upgrade.

Do not use repository ZIPs, raw committed ZIPs, or GitHub source archives as the WordPress installation package.

## Structured school importer

A varsity import ZIP can contain a `schools.csv` plus one folder per school. The importer recognizes image roles by filename:

- `{School Name} Logo` → school logo
- `{School Name} Front` → jacket style featured/product image
- `{School Name} Back` → gallery
- `{School Name} Letter` → gallery
- `{School Name} Sleeve` → gallery
- `{School Name} Detail` → gallery

The CSV can also populate school/initial-style metadata such as mascot, location, district, description, colors, style name/description, price, and CTA. Legacy `image-manifest.csv` imports remain supported as a fallback.

## Releasing future versions

For each version:

1. Update the plugin header version and `ASEVJ_VERSION` together.
2. Update block metadata versions where applicable.
3. Add `releases/RELEASE-X.Y.Z.md`.
4. Commit source changes to `main` or manually run **Publish WordPress Plugin Release** with the matching version.
5. Do not consider the release complete until the GitHub Release asset has been validated and `latest.json` points to that asset.
