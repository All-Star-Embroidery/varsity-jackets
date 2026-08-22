# All Star Varsity Jackets v0.2.0-beta.9

## Changes

- Added a separate optional **Mascot Image** field for each school, independent of the School Logo and Mascot Name text.
- Structured ZIP imports now recognize `{School Name} Mascot` automatically.
- Mascot artwork is imported to the WordPress Media Library and assigned to the school; it is not added to the jacket gallery.
- School Logo and Mascot Image are independently optional and may be omitted without blocking the structured import.
- Preserved the existing image mapping: Front → featured/product image, Back → Letter → Sleeve → Detail → gallery.
- Updated importer guidance so the `Mascot` CSV column is clearly the mascot name text, while the mascot image comes from the named image file.

## Compatibility

- WordPress 6.4+
- PHP 8.0+
- WooCommerce remains optional
