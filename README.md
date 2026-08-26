# All Star Varsity Jackets

Development and release repository for the All Star Embroidery Varsity Jackets WordPress/WooCommerce plugin.

Current production release: **v1.0.3**.

## Stable WordPress plugin identity

- Plugin name: `All Star Varsity Jackets`
- Plugin slug/folder: `all-star-varsity-jackets`
- Main file: `all-star-varsity-jackets.php`
- Update URI / repository: `https://github.com/All-Star-Embroidery/varsity-jackets`
- Current release asset: `all-star-varsity-jackets-1.0.3.zip`

The plugin folder and basename remain stable between releases so WordPress upgrades the existing installation rather than creating a second plugin directory.

## Current release baseline

**v1.0.3 is the authoritative current production package.** Older beta releases remain as historical artifacts only and should not be used as the basis for future releases.

The v1.0.3 release improves the responsive jacket-detail experience and purchase flow:

- fixes tablet/phone jacket-detail images overflowing the gallery;
- adds **Customize Jacket** beside **View Jacket Details**;
- keeps style changes connected to the correct WooCommerce product/link;
- adds a sticky popup CTA with the starting price;
- opens the selected gallery photo when a thumbnail is clicked;
- improves active-thumbnail styling;
- uses an even two-button action row on mobile.

## GitHub → WordPress release architecture

1. Current WordPress installs read the organization-owned `latest.json`.
2. `latest.json` points to the versioned GitHub Release asset under `All-Star-Embroidery/varsity-jackets`.
3. The release ZIP is the WordPress installation/update package; repository source archives are not.
4. `.github/workflows/publish-plugin-release.yml` is **manual-only**. It does not run on pushes or schedules, conserving GitHub Actions minutes.
5. Future releases should only run the workflow intentionally after the source/version/release notes are ready.

The `main` source tree is synchronized to the authoritative v1.0.3 production package. Future releases should always begin from the current v1.x source in this organization repository.

## Organization migration

The authoritative repository is:

`All-Star-Embroidery/varsity-jackets`

All active updater manifests and future release URLs must use the organization repository directly. Old personal-account links may remain in historical releases/commits, but should not be used operationally.

## Structured school importer

A varsity import ZIP can contain a `schools.csv` plus one folder per school. The importer recognizes image roles by filename:

- `{School Name} Logo` → school logo
- `{School Name} Mascot` → separate mascot artwork
- `{School Name} Front` → jacket style featured/product image
- `{School Name} Back` → gallery
- `{School Name} Letter` → gallery
- `{School Name} Sleeve` → gallery
- `{School Name} Detail` → gallery

School Logo and Mascot Image are independently optional. The `Mascot` column in `schools.csv` stores the mascot name text; the `{School Name} Mascot` image stores the actual artwork.

## Releasing future versions

For each future version:

1. Start from the current authoritative v1.x source, not an old beta package.
2. Update the plugin header version and internal version together.
3. Update block metadata versions where applicable.
4. Add `releases/RELEASE-X.Y.Z.md`.
5. Commit the source changes to `main`.
6. Manually run **Publish WordPress Plugin Release** with the matching version only when the release is ready.
7. Confirm the GitHub Release asset exists and `latest.json` points to the organization-owned asset.

Do not add scheduled or push-triggered release workflows merely to preserve old versions.
