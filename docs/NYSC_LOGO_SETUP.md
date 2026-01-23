# NYSC Logo Instructions

## Current Setup
The NYSC Accredited Centre badge has been added to:
- **Homepage** (hero section)
- **About Page** (hero section)
- **Footer** (all pages)

Currently using an inline SVG placeholder logo.

## How to Replace with Actual NYSC Logo

### Option 1: Using PNG/JPG Image
1. Get the official NYSC logo image (PNG format recommended for transparency)
2. Save it as: `public/assets/images/nysc-logo.png`
3. Update the code in these files:

**In `public/home.php`** (around line 43):
Replace the `<div class="nysc-logo">` SVG content with:
```php
<img src="<?= app_url('assets/images/nysc-logo.png') ?>" alt="NYSC Logo" class="nysc-logo">
```

**In `public/includes/footer.php`** (around line 21):
Replace the `<div class="footer-nysc-logo">` SVG content with:
```php
<img src="<?= app_url('assets/images/nysc-logo.png') ?>" alt="NYSC Logo" class="footer-nysc-logo">
```

**In `public/about.php`** (around line 33):
Replace the `<div class="nysc-logo-small">` SVG content with:
```php
<img src="<?= app_url('assets/images/nysc-logo.png') ?>" alt="NYSC Logo" class="nysc-logo-small">
```

### Option 2: Using External URL
If the logo is hosted elsewhere, replace `app_url('assets/images/nysc-logo.png')` with the full URL:
```php
<img src="https://example.com/path/to/nysc-logo.png" alt="NYSC Logo">
```

## Customization

### Change Badge Text
To modify the accreditation text, edit these files:
- `public/home.php` - lines with "NYSC Accredited Centre" and "Official Skills Acquisition Partner"
- `public/includes/footer.php` - same text strings
- `public/about.php` - "NYSC Accredited Centre"

### Change Badge Colors
Edit the CSS in:
- `public/home.php` - `.nysc-accreditation-badge` styles (green theme)
- `public/assets/css/public.css` - `.footer-nysc-badge` styles
- `public/about.php` - `.nysc-accreditation-badge-about` styles

### Remove Badge
To remove the badge entirely:
1. Delete the `<!-- NYSC Accreditation Badge -->` sections from the files
2. Optionally remove the CSS styles

## Badge Features
- ✅ Responsive design (mobile-friendly)
- ✅ Animated pulse glow effect on homepage
- ✅ Consistent branding across pages
- ✅ Fallback SVG logo included
- ✅ Professional green color scheme matching NYSC branding
