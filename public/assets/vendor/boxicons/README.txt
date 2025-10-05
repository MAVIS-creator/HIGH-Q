To install Boxicons locally:

1. Download Boxicons from https://boxicons.com/ or the npm/unpkg distribution.
2. Place the CSS file as `boxicons.min.css` in this folder.
3. Place the `fonts/` folder (containing .woff/.woff2/.ttf) next to the CSS, and update any font-face paths in the CSS if necessary.

This project will prefer this local CSS (./assets/vendor/boxicons/boxicons.min.css) if present. If not present, the header uses the CDN as a fallback.
