// screenshot-offcanvas.js
// Captures screenshots (mobile/tablet/desktop) for a set of pages and
// also opens the mobile offcanvas (if present) and captures that state.

const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

(async () => {
  const outDir = path.resolve(__dirname, 'screenshots');
  if (!fs.existsSync(outDir)) fs.mkdirSync(outDir);

  const base = 'http://localhost/HIGH-Q';
  const pages = [
    '/',
    '/post.php?id=1',
    '/programs.php',
    '/register.php',
    '/contact.php'
  ];

  const viewports = [
    { label: 'mobile', width: 375, height: 812 },
    { label: 'tablet', width: 768, height: 1024 },
    { label: 'desktop', width: 1366, height: 900 }
  ];

  const browser = await chromium.launch();

  for (const pagePath of pages) {
    const url = new URL(pagePath, base).toString();
    const safeName = url.replace(/^https?:\/\//, '').replace(/[\/?=&:<>\\"'*|]/g, '_');

    for (const vp of viewports) {
      const context = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
      const page = await context.newPage();
      try {
        console.log(`Visiting ${url} @ ${vp.label} ${vp.width}x${vp.height}`);
        await page.goto(url, { waitUntil: 'load', timeout: 15000 });
        await page.waitForTimeout(600); // short pause for any late layout

        const outPath = path.join(outDir, `${safeName}_${vp.label}.png`);
        await page.screenshot({ path: outPath, fullPage: true });
        console.log(`Saved ${outPath}`);

        // If mobile, try to open offcanvas/menu toggler
        if (vp.label === 'mobile') {
          try {
            const toggle = await page.$('.mobile-toggle');
            if (toggle) {
              await toggle.click();
              // wait for bootstrap offcanvas to show
              await page.waitForSelector('.offcanvas.show', { timeout: 3000 });
              const offPath = path.join(outDir, `${safeName}_${vp.label}_offcanvas.png`);
              await page.screenshot({ path: offPath, fullPage: true });
              console.log(`Saved ${offPath} (offcanvas opened)`);
              // close if close button exists
              const closeBtn = await page.$('.offcanvas .btn-close');
              if (closeBtn) await closeBtn.click();
            } else {
              console.log('No .mobile-toggle found on page (skipping offcanvas capture)');
            }
          } catch (err) {
            console.log('Offcanvas capture failed or not present:', err.message);
          }
        }

      } catch (err) {
        console.error(`Error capturing ${url} @ ${vp.label}:`, err.message);
      } finally {
        await page.close();
        await context.close();
      }
    }
  }

  await browser.close();
  console.log('All done. Screenshots saved to screenshots/');
})();
