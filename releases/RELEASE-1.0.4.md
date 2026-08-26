# All Star Varsity Jackets v1.0.4

## Changes

- Permanently moved the WordPress updater to the organization repository: `All-Star-Embroidery/varsity-jackets`.
- Bumped the updater cache namespace so WordPress immediately fetches fresh organization-owned release metadata after upgrading.
- Clears older pre-migration updater transients left behind by the personal-repository builds.
- Keeps transitional validation for the former `rolejarczyk/ASE.VarsityJackets` release URL so migration-era metadata cannot strand an installation.
- All new manifests, release assets, homepage links, and automatic-update checks now use the All Star Embroidery organization repository.

## Why this release exists

v1.0.3 was migrated to the organization repository after some installations had already received an earlier build. v1.0.4 is the explicit update-path bridge so every installed copy can settle onto the same organization-owned update channel before normal feature releases continue.

## Distribution

- Authoritative repository: `All-Star-Embroidery/varsity-jackets`
- WordPress package: `all-star-varsity-jackets-1.0.4.zip`
- Release tag: `v1.0.4`
- Update manifest: `latest.json` in the organization repository

## Compatibility

- WordPress 6.4+
- PHP 8.0+
- WooCommerce integration supported when WooCommerce is active
