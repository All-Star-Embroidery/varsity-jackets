# All Star Varsity Jackets v1.0.12

This release makes the dedicated Varsity Jacket Product Page gallery naturally responsive instead of forcing jacket photos into fixed pixel-height boxes.

## Product-page gallery

- Main jacket photography now uses **Scale to fit** behavior by default.
- The gallery height follows the actual image aspect ratio instead of fixed desktop/mobile pixel heights.
- The full jacket remains visible as the page width changes.
- Thumbnail switching automatically reflows to the selected image's natural proportions.
- Removed the desktop and mobile gallery-height controls from the Product Page block because they are no longer needed.
- Existing saved height values are safely ignored; no content or jacket data is lost.

This keeps the gallery responsive across desktop, tablet, split-window, and mobile layouts while retaining the existing image-size control and All Star styling.
