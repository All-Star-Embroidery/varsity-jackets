# All Star Varsity Jackets v0.2.0-beta.9.1

## Changes

- Migrated the authoritative GitHub repository to `All-Star-Embroidery/varsity-jackets`.
- Updated the WordPress plugin `Update URI`, public manifest URL, release repository, and Release asset validation to the organization-owned path.
- Updated the GitHub release workflow so new ZIP assets, `latest.json`, and `update.json` are generated with organization-owned URLs.
- Bumped the updater cache key and clears the pre-migration cache key after upgrades so WordPress does not reuse stale personal-account release metadata.
- Keeps the former Release path as a temporary validation fallback for migration compatibility; all newly generated release metadata uses the organization repository.
- Disabled the broken five-minute v1.0.0 bootstrap schedule. The one-time v1 workflow remains manual until its existing corrupt beta.14 bootstrap payload is repaired.
- No storefront, importer, school data, WooCommerce product behavior, pricing, or design behavior changed in this migration release.

## WordPress migration note

The installed beta.9 updater only trusts the former personal-account Release path. Because All Star is pre-launch, install this migration ZIP once through WordPress if beta.9 does not discover it automatically. After beta.9.1 is installed, subsequent releases use the organization-owned updater path normally.

## Compatibility

- WordPress 6.4+
- PHP 8.0+
- WooCommerce remains optional
