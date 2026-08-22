# All Star Varsity Jackets

Development and release repository for the All Star Embroidery Varsity Jackets WordPress/WooCommerce plugin.

## WordPress update system

`0.2.0-beta.7` is the bridge release for the permanent updater architecture.

From beta.7 forward, WordPress does **not** depend on GitHub Actions successfully building or uploading a binary release asset. The plugin reads `update.json` directly from this repository. Releases may be delivered either as a normal ZIP URL or as checksum-verified base64 package chunks stored as ordinary UTF-8 files in this repository.

For chunked releases, WordPress downloads the chunk list from the manifest, reconstructs the ZIP in its temporary directory, verifies the SHA-256 checksum, and passes the verified ZIP to the normal WordPress upgrader.

That means future releases can be published entirely through normal GitHub file writes, which is the same access path used for ongoing development here.

### Release flow after beta.7

1. Build and validate the plugin ZIP.
2. Split the ZIP into base64 text chunks and commit them under `packages/<version>/`.
3. Update `update.json` with the new version, chunk URLs, and SHA-256 checksum.
4. WordPress installations on the Beta + Stable channel discover and install the release normally.

GitHub Actions and formal GitHub Releases are optional validation/convenience layers. They are not required for WordPress to receive an update.

## Current bridge state

The public manifest remains on beta.5 until the beta.7 bridge is installed on the active WordPress site. This prevents older updater code from being handed a chunked package format it does not understand.
