responsive-utils (hq-) — quick reference

Purpose

This short doc explains the `hq-` responsive utilities provided in `responsive-utils.css`.
They are lightweight, site-specific helpers that apply only below the listed breakpoints (max-width media queries). Bootstrap remains included and unchanged; use `hq-` classes when you want behaviour that affects only smaller viewports without altering desktop defaults.

Where the CSS lives

- `public/assets/css/responsive-utils.css` — the stylesheet with `hq-` utilities.
- It is included site-wide via `public/includes/header.php` and also directly linked in a few standalone pages.

Contract (2–3 bullets)

- Inputs: HTML elements with `class` attributes using `hq-` utilities.
- Output: CSS rules that apply only under certain max-width breakpoints (mobile-first safety). Desktop/unconstrained styles remain unchanged.
- Error modes: If you apply both a Bootstrap utility and an `hq-` utility that conflict, specificity and rule order determine outcome; `!important` is used in `hq-` helpers to ensure they take effect at those sizes.

How the breakpoints work (important)

- `hq-` classes are scoped with max-width media queries (they apply at or below the listed width):
  - sm: up to 575.98px -> classes contain `-sm-` (e.g. `.hq-d-sm-none`).
  - md: up to 767.98px -> `-md-` (e.g. `.hq-w-md-100`).
  - lg: up to 991.98px -> `-lg-`.
  - xl: up to 1199.98px -> `-xl-`.

Note: Bootstrap's responsive utility naming uses min-width breakpoints (they activate at and above the breakpoint). `hq-` does the opposite: they activate below the breakpoint. Use `hq-` when you want to change behavior only on small viewports while preserving desktop styles.

Available utilities (summary)

Display helpers (show/hide, layout)

- `.hq-d-<bp>-none` — display: none
- `.hq-d-<bp>-block` — display: block
- `.hq-d-<bp>-inline` — display: inline
- `.hq-d-<bp>-inline-block` — display: inline-block
- `.hq-d-<bp>-flex` — display: flex
- `.hq-d-<bp>-grid` — display: grid

Flex / alignment / justification

- `.hq-flex-<bp>-row`, `.hq-flex-<bp>-column` — flex-direction
- `.hq-flex-<bp>-wrap`, `.hq-flex-<bp>-nowrap` — flex-wrap
- `.hq-justify-<bp>-start|center|end|between` — justify-content
- `.hq-align-<bp>-start|center|end` — align-items

Text alignment

- `.hq-text-<bp>-left|center|right`

Width helpers

- `.hq-w-<bp>-100`, `.hq-w-<bp>-auto`, `.hq-w-<bp>-25|50|75` (percent widths)

Spacing (tiny scale)

- Margin: `.hq-m-<bp>-0|1|2|3` (0, .25rem, .5rem, 1rem)
- Padding: `.hq-p-<bp>-0|1|2|3`

Examples

1) Hide an element only on extra-small screens (mobile):

```html
<!-- button visible on desktop, hidden under 576px -->
<button class="btn btn-primary hq-d-sm-none">Call us</button>
```

2) Change flex direction on small screens only:

```html
<div class="d-flex hq-flex-sm-column">
  <div>Item 1</div>
  <div>Item 2</div>
</div>
```

3) Make a call-to-action full width on medium screens and below:

```html
<a href="register.php" class="btn btn-primary hq-w-md-100">Register Now</a>
```

4) Add small mobile-only spacing:

```html
<div class="hq-m-sm-2 hq-p-sm-2">Small-screen margin/padding</div>
```

Mapping to Bootstrap (quick guidance)

- Bootstrap's `d-sm-none` hides an element at small and above (min-width >= 576px). `hq-d-sm-none` hides an element at small and below (max-width <= 575.98px). They are not interchangeable.
- If you want behaviour only under a breakpoint, prefer `hq-` utilities. If you want behaviour at or above a breakpoint, use Bootstrap utilities.

Developer tips

- Prefer `hq-` helpers for page-level adjustments you plan to rework later. They are intentionally conservative (only modify styles on smaller screens).
- Because `hq-` classes use `!important` to ensure effect at the breakpoint, prefer applying them to wrapper elements or where you control the markup. Avoid overusing `!important` elsewhere.
- Run visual QA on these pages after applying any `hq-` class: homepage, pages with offcanvas/mobile nav, `post.php`, and forms.
- If you later want to migrate existing Bootstrap utility usage to `hq-` equivalents, do it in small, tested batches.

Where to look for more

- CSS file: `public/assets/css/responsive-utils.css` (the definitive list of classes).
- Header include: `public/includes/header.php` (shows the stylesheet is loaded site-wide).

If you'd like, I can:

- produce a one-page cheatsheet PNG for designers,
- or run a search-and-suggest process that lists candidate elements where `hq-` helpers would be useful.

End of doc
