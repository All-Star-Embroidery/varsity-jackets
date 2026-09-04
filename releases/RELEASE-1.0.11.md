# All Star Varsity Jackets v1.0.11

This release makes the dedicated varsity jacket product page fully theme-compatible.

## Changes
- Registers **Varsity Jacket Product** as a real WordPress block template.
- Linked varsity jacket WooCommerce products automatically route to that template.
- The template uses the active theme's Header and Footer template parts.
- The template is editable from **Appearance → Editor → Templates**.
- Normal WooCommerce products continue using the normal Single Product template.
- Classic themes retain a safe PHP fallback using the theme's shop header/footer.
- Existing product-page block customization is carried into the registered template's default content.

Online purchasing remains disabled for linked varsity jacket products; the call-to-order experience is unchanged.
