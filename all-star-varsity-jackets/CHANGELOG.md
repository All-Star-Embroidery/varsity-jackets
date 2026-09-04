## 1.0.13

- Kept product-page jacket photography in Scale to fit mode while capping it to a responsive medium size.
- Added viewport-aware maximum image heights for desktop, tablet, mobile, and short laptop windows.
- Preserved full-image visibility with no cropping and no fixed gallery-height controls.
- Kept the Jacket Image Size control for optional further reduction.

## 1.0.12

- Replaced fixed desktop/mobile product-gallery heights with intrinsic Scale to fit behavior.
- Jacket images now follow their natural aspect ratio and resize automatically with the page.
- Removed obsolete gallery-height controls from the Product Page block while safely ignoring previously saved values.
- Preserved the existing image-size control and full-image containment.

## 1.0.11

- Replaced the block-theme jacket routing that bypassed the Site Editor with a registered WordPress block template.
- Linked varsity products now use a dedicated **Varsity Jacket Product** template that appears in **Appearance → Editor → Templates**.
- The jacket template uses the active theme's Header and Footer template parts, so theme patterns and global template editing work normally.
- Normal WooCommerce products keep the normal Single Product template.
- Classic themes retain a PHP fallback that still calls the theme's shop header/footer.
- Existing jacket product-page block customization is used as the default content when the new template is first registered.

## 1.0.10

- Ensured the full jacket image always remains visible at every responsive size.
- Capped jacket image scaling at 100% to prevent viewport cropping.
- Sharpened the product-page design to 3–4px radii and removed soft SaaS-style shadows.
- Replaced floating-card styling with hairline borders and flatter retail/document surfaces.

## 1.0.9

- Added automatic routing of linked varsity WooCommerce products to the dedicated jacket product template.
- Added the editable shared Product Page Template.
- Disabled online purchasing for linked varsity jacket products while leaving normal WooCommerce products unchanged.

## 1.0.8

- Added the Varsity Jacket Product Page Gutenberg block.
- Added automatic linked-product detection, gallery, school/style identity, starting-price clarification, jacket details, customization options, and call-to-order CTA.
- Added extensive product-page layout, content, color, visibility, spacing, image-scale, and responsive controls.
- Added live Gutenberg preview plus responsive thumbnail gallery behavior.

## 1.0.7

- Reduced the inline Browse by School gallery height to keep more of the selector in one viewport.
- Slightly reduced supporting gallery tiles while preserving image containment.
- Added responsive gallery caps for laptop, tablet, and mobile widths.
- Left the jacket-detail popup sizing unchanged.

## 1.0.6

- Moved Available Customizations underneath the jacket style selector in the school sidebar.
- Removed the separate customization grid item that was making Browse by School unnecessarily tall.
- Gave the selected jacket gallery more horizontal room with a narrower, denser school/style sidebar.
- Compacted customization typography, spacing, and dividers while retaining the full descriptions.
- Made customization options horizontally swipeable on tablets/phones to avoid a tall mobile stack.
- Tightened school-carousel and school-stage vertical spacing.

## 1.0.5

- Clarified customer-facing Varsity Jackets prices as **Starting at** prices because customization may increase the final total.
- Added the missing **Starting at** label above the jacket-detail popup price.
- Kept the existing Starting at treatment in style cards, selected-style pricing, and the sticky customization bar.
- Added responsive modal price-label styling.

## 1.0.3

- Fixed jacket detail/gallery images overflowing their intended media area on tablets and narrow portrait screens.
- The detail viewer now uses a responsive single-column tablet layout and force-contains all full-size images inside the gallery viewport.
- Clicking a gallery tile now opens that exact image in the detail viewer instead of always resetting to the Front image.
- Added a prominent **Customize Jacket** button beside **View Jacket Details** in Browse by School.
- The Customize Jacket link updates automatically when a different jacket style is selected.
- Added a sticky bottom conversion bar inside the jacket-detail modal so **Customize Jacket** remains visible while scrolling through details.
- The sticky modal bar can also show the current starting price beside the CTA.
- Improved thumbnail active states and narrow-screen action spacing.

## 1.0.2

- Fixed the Hero block's mobile controls not being registered in Gutenberg's JavaScript block definition.
- Mobile Hero settings now persist, rerender, and visibly update in the editor/frontend.
- Added **Use main Hero controls on mobile** for the two-block workflow: duplicate the Hero, hide one from mobile and the other from desktop/tablet, then turn this on for the mobile copy.
- With that option enabled, the normal Hero title size, kicker/body size, padding, feature scale, jacket scale, text/feature/jacket X/Y controls, glow controls, and appearance settings drive the mobile-only Hero copy directly.
- Keeping the option off preserves the independent responsive mobile controls for sites using one Hero block across all devices.

## 1.0.1

- Added global varsity-jacket WooCommerce product defaults with a $400 base price by default.
- Added optional per-style Base Price Override; blank styles inherit the global base price automatically.
- Added bulk Create / Sync All Styles, Create Missing Products, and Sync Linked Products actions.
- Product generation now carries over the school/style name, effective base price, short/full description, style features, featured image, gallery, product category, SKU, and the style-to-product relationship.
- Added configurable new-product status (Draft or Published), Varsity Jackets product category, and SKU prefix.
- Existing linked products can be synchronized in bulk instead of editing prices and media one-by-one.
- Individual style/product controls remain available for exceptions and manual review.

## 1.0.0 — Production Release

- Moved “Slide to find your school” beneath the school carousel, increased contrast, and retained its auto-disappearing behavior.
- Newark is now the default selected school whenever it is enabled; otherwise the first enabled school is used.
- Polished Available Customizations on phones with consistent typography, horizontal breathing room, and an easy-to-scan stacked list.
- Rebuilt the phone hero as an intentional mobile composition instead of a shrunken desktop layout.
- Added dedicated mobile controls for title size, padding, jacket area height, feature/jacket scale, and independent text/feature/jacket positioning.
- Mobile jacket imagery now uses contain behavior so the signature jacket is not unintentionally cropped.
- Preserved responsive-safe laptop and split-window behavior.
- Removed beta-facing dashboard language and completed a final frontend/admin responsiveness and production-readiness sweep.
- Retains structured ZIP/CSV imports, school/logo/mascot data, multiple jacket styles, gallery-first school browsing, WooCommerce integration, backups, and GitHub-powered updating.

## 0.2.0-beta.15

- Replaced the GitHub-Actions-dependent update path with a direct GitHub package transport.
- WordPress can now reconstruct a verified plugin ZIP from ordinary UTF-8 package chunks committed to the repository.
- Added SHA-256 verification before WordPress installs a chunk-based update.
- Retained genuine GitHub Release assets as a fallback transport.
- Automatic plugin updates remain enabled for All Star Varsity Jackets.
- Includes all beta.10 through beta.14 importer, editor, responsive hero, and gallery-first Browse by School improvements.
