# Guidelines for Managing Twig Templates

- Templates should be organized in a way that separates concerns, such as layout and partials.
- Most sites will be centered around a common `_layout.twig` file, which will include the header and footer. Each other template will extend this layout.
- Any time you render a copyright year, use the relevant programming language (Twig, JavaScript, PHP, etc.) to get the current year. For example, in Twig, you would use `{{ 'now'|date('Y') }}`.

## Placeholder Image URLs

If no image is provided or specified, generate a placeholder image as described below.

When generating placeholder images, use this URL format: `https://picsum.photos/seed/{seed}/{width}/{height}`.

- `seed` - A short, random string to ensure different images.
- `width` - The desired width of the image.
- `height` - The desired height of the image.

Always output a Super Retina (`@3x`) version of the image (meaning the image dimensions will be 3x the dimensions of the space where it will be shown).

To replace/regenerate an image, you only need to change the `seed` value (unless new dimensions are also desired).
