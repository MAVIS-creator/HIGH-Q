# Sitemap Generation Guide - Using Automator

## Overview

The **Automator** module in High Q Tutorial's Admin Panel automatically generates your `sitemap.xml` file, which is essential for SEO. A sitemap helps search engines (Google, Bing, etc.) discover and index all your important pages.

---

## What Gets Included in Your Sitemap

The system automatically includes:

### 1. **Core Pages**
- Homepage (/)
- Courses page
- Registration pages (register.php, register-new.php)

### 2. **Find Your Path Content** (High Priority)
- Quiz page (find-your-path-quiz.php) - **Priority: 0.95**
- JAMB path page (path-jamb.php) - **Priority: 0.9**
- WAEC path page (path-waec.php) - **Priority: 0.9**
- Post-UTME path page (path-postutme.php) - **Priority: 0.9**
- Digital path page (path-digital.php) - **Priority: 0.9**
- International path page (path-international.php) - **Priority: 0.9**

### 3. **Blog Posts**
- All published blog/post content (from the posts table)

### 4. **Priority Levels**
- 1.0 = Homepage (most important)
- 0.95 = Quiz page (key entry point)
- 0.9 = Path pages (important educational content)
- 0.85 = Registration pages
- 0.8 = Courses and blog posts (secondary content)

---

## How to Generate Your Sitemap

### Method 1: Using the Admin Dashboard (Easiest)

1. **Log in to Admin Panel** → Dashboard
2. **Navigate to Tools** → **Automator** (or find it in the admin sidebar)
3. **Click "Generate Sitemap Now"** button
4. You'll see a success message: *"Sitemap generated successfully with X posts!"*
5. The sitemap is automatically saved to `/sitemap.xml`

### Method 2: Command Line (for Automation)

If you want to generate the sitemap automatically at scheduled intervals:

```bash
# Using PHP CLI
php /path/to/admin/modules/automator.php

# Or create a CRON job to run daily
0 2 * * * curl -s http://yoursite.com/admin/modules/automator.php?action=generate_sitemap > /dev/null 2>&1
```

---

## Verifying Your Sitemap

### Step 1: Check if Sitemap Exists

In the Automator dashboard, verify:
- **Status**: Should show "Active" with a green checkmark ✓
- **Last Updated**: Shows when sitemap was last generated
- **Location**: `/sitemap.xml`

### Step 2: View Your Sitemap

1. Click the "View Sitemap" link in the Automator
2. Or visit: `https://yourdomain.com/sitemap.xml`
3. You should see XML content with `<urlset>` and `<url>` entries

### Step 3: Example Sitemap Structure

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://yoursite.com/</loc>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>https://yoursite.com/find-your-path-quiz.php</loc>
    <changefreq>weekly</changefreq>
    <priority>0.95</priority>
  </url>
  <url>
    <loc>https://yoursite.com/path-jamb.php</loc>
    <changefreq>monthly</changefreq>
    <priority>0.9</priority>
  </url>
  <!-- ... more URLs ... -->
</urlset>
```

---

## Submitting to Search Engines

After generating your sitemap, you should submit it to search engines:

### Google Search Console

1. Go to [Google Search Console](https://search.google.com/search-console)
2. Add your property (if not already added)
3. Navigate to **Sitemaps** (in the left sidebar under Indexing)
4. Click **Add/Test Sitemap**
5. Enter: `sitemap.xml` (or full URL: `https://yourdomain.com/sitemap.xml`)
6. Click **Submit**

### Bing Webmaster Tools

1. Go to [Bing Webmaster Tools](https://www.bing.com/webmasters)
2. Sign in and add your site
3. Go to **Sitemaps** in the toolbar
4. Click **Submit Sitemap**
5. Enter your sitemap URL
6. Click **Submit**

### robots.txt Integration

Your `robots.txt` should reference the sitemap:

```text
# robots.txt
User-agent: *
Allow: /

Sitemap: https://yourdomain.com/sitemap.xml
```

---

## Updating Your Sitemap

### When to Update

The sitemap should be regenerated when:
- ✅ You publish a new blog post
- ✅ You update course descriptions
- ✅ You add new path pages or content
- ✅ You change page importance (priorities)
- ✅ Periodically (monthly or quarterly review)

### How Often to Update

- **Recommended**: Weekly or after major content changes
- **Minimum**: Monthly
- **Search engines** automatically check your sitemap

### Automatic Updates

To set up automatic sitemap generation, you can:

1. **Using CRON Jobs** (Linux/Unix servers):
   ```bash
   # Generate sitemap every day at 2 AM
   0 2 * * * /usr/bin/php /var/www/html/HIGH-Q/admin/modules/automator.php
   ```

2. **Using Windows Task Scheduler**:
   - Create a task that runs daily
   - Call: `php.exe C:\xampp\htdocs\HIGH-Q\admin\modules\automator.php`

3. **Using a Webhook** (with external service like Zapier):
   - Set up daily webhook to trigger sitemap generation
   - Less reliable but works with managed hosting

---

## Troubleshooting

### Problem: "Sitemap not generated"

**Solution**:
1. Check file permissions on the root directory
2. Ensure `/sitemap.xml` is writable (777 permissions)
3. Check PHP error logs for details
4. Try generating again from the admin panel

### Problem: "Sitemap exists but not updating"

**Solution**:
1. Try "Generate Sitemap Now" button again
2. Check "Last Updated" timestamp
3. If timestamp doesn't change, check server write permissions
4. Verify database connection (posts table)

### Problem: "Search engines not crawling my sitemap"

**Solution**:
1. Verify robots.txt includes sitemap reference
2. Submit directly to Google Search Console
3. Wait 2-3 days for search engines to crawl
4. Check search console for errors or warnings
5. Verify URL structure in sitemap is correct

### Problem: "Getting 404 on paths in sitemap"

**Solution**:
1. Verify all path pages exist in `/public/` directory
2. Check `.htaccess` or server routing rules
3. Ensure file names match exactly (case-sensitive on Linux)
4. Test paths manually to confirm they work

---

## Advanced Configuration

### Customizing Priority Levels

To change URL priorities, edit the automator code:

**Location**: `/admin/modules/automator.php` (around line 50-65)

```php
$pathPages = [
    ['slug' => 'find-your-path-quiz.php', 'priority' => 0.95, 'freq' => 'weekly'],
    ['slug' => 'path-jamb.php', 'priority' => 0.9, 'freq' => 'monthly'],
    // Change priority values (0.0 to 1.0, with 1.0 being highest)
];
```

### Adding More Pages

To include additional pages in the sitemap:

```php
// Add to the automator.php file:
$morePages = [
    ['slug' => 'about.php', 'priority' => 0.7, 'freq' => 'monthly'],
    ['slug' => 'contact.php', 'priority' => 0.6, 'freq' => 'weekly'],
];
```

---

## SEO Best Practices

### Sitemap Optimization Tips

1. **Keep URLs clean**: Use descriptive, SEO-friendly URLs
2. **Maintain priority hierarchy**: Most important pages at 0.8-1.0
3. **Use correct change frequency**:
   - daily: Homepage, blog listing
   - weekly: Quiz, registration pages
   - monthly: Path pages, courses
   - rarely: Static content like About, Contact

4. **Regular updates**: Generate at least monthly
5. **Monitor in Search Console**: Check for crawl errors

### Priority Guide

```
1.0   - Homepage only (highest priority)
0.9+  - Key entry points (quiz, main pages)
0.8   - Secondary content (paths, courses)
0.6   - Support pages (registration, forms)
0.3   - Low-priority content
```

---

## File Locations

```
Project Structure:
/
├── sitemap.xml                    ← Generated sitemap file
├── robots.txt                     ← Includes sitemap reference
├── public/
│   ├── find-your-path-quiz.php
│   ├── path-jamb.php
│   ├── path-waec.php
│   ├── path-postutme.php
│   ├── path-digital.php
│   ├── path-international.php
│   └── register.php
└── admin/
    └── modules/
        └── automator.php          ← Sitemap generation tool
```

---

## Useful Links

- [Google Sitemap Protocol](https://www.sitemaps.org/)
- [Google Search Console Help](https://support.google.com/webmasters)
- [Bing Webmaster Tools](https://www.bing.com/webmasters)
- [SEO Sitemap Validator](https://www.xml-sitemaps.com/validate-xml-sitemap.html)

---

## FAQ

**Q: How large can a sitemap be?**  
A: Maximum 50,000 URLs per sitemap. For larger sites, create a sitemap index.

**Q: Does Google automatically find my sitemap?**  
A: Not always. Submit it manually to Google Search Console for faster discovery.

**Q: How often should I regenerate?**  
A: After adding content. The system stores last update time, so old sitemaps are harmless.

**Q: Do I need multiple sitemaps?**  
A: Only if you have >50,000 URLs. High Q Tutorial won't need this.

**Q: Will sitemap improve my rankings?**  
A: Indirectly. It helps indexing, which supports ranking. It's an SEO best practice.

---

**Last Updated**: 2024  
**Version**: 1.0  
**For Questions**: Contact High Q Tutorial Administrator
