# Mobile & Tablet Animations Guide

## ✨ What's New

Your site now has **performance-optimized animations** for mobile and tablet devices that:
- ✅ Use GPU-accelerated transforms only (no jank)
- ✅ Are light on processor usage (won't hang phones)
- ✅ Respect accessibility preferences (prefers-reduced-motion)
- ✅ Automatically pause during fast scrolling
- ✅ Support touch device haptic feedback
- ✅ Include scroll-triggered reveal animations

## 🎬 Animation Classes (Auto-Applied)

The system **automatically animates** common elements without requiring markup changes:

### Automatic Elements
- **Cards** - All `.card` and `[class*="card-"]` elements
- **Buttons** - All `.btn` and `[class*="btn-"]` elements
- **Navigation** - All `.nav-item` elements
- **Lists** - All `ul li` and `ol li` in `<main>`
- **Features** - All `[class*="feature"]` and `[class*="section"]` elements

### Manual Animation (Using data-animate)

To manually animate any element, add the `data-animate` attribute:

```html
<!-- Will slide in when scrolled into view -->
<div class="custom-card" data-animate>
  Your content here
</div>
```

## 🎨 Available Animations (CSS)

### Mobile (max-width: 768px)
- **slideInMobile** - Fade in + slide up
- **slideInMobileLeft** - Fade in + slide from left
- **slideInMobileRight** - Fade in + slide from right
- **scalePulse** - Gentle scale pulse (2s loop)
- **fadeInGently** - Simple opacity fade
- **floatUp** - Subtle floating animation (3s loop)
- **wiggleDown** - Wiggling motion downward

### Tablet (769px - 1024px)
- Slightly longer durations than mobile
- More pronounced hover effects
- Smoother curves (cubic-bezier)

### All Devices
- **rippleOut** - Touch ripple effect on buttons

## 📱 Touch Device Features

### Haptic Feedback
Buttons automatically trigger vibration on mobile (10ms):
```javascript
navigator.vibrate(10); // Automatic
```

### Touch States
- `:active` state scales button down to 0.97
- `:focus` state scales button up to 1.02
- Touch ripple appears on click/tap

## ⚡ Performance Features

### Scroll Optimization
- Animations automatically pause during fast scrolling
- Resumed after scroll ends (150ms delay)
- Reduces jank and improves perceived performance

### GPU Acceleration
- All animations use `transform` and `opacity` only
- Uses `will-change` hints for better rendering
- Font smoothing enabled on main elements

### Reduced Motion Support
- Automatically disabled for users with `prefers-reduced-motion: reduce`
- Respects accessibility preferences

## 🔍 Debug Mode

To see animation triggers in console:

```
Add ?debug-animations to your URL
Example: https://yoursite.com/?debug-animations
```

Console will show:
- Device type (Touch/Non-touch)
- Viewport dimensions
- Animation triggers in real-time

## 🎯 How to Customize

### Change Animation Duration

Edit in `mobile-animations.css`:

```css
@media (max-width: 768px) {
  .card {
    animation: slideInMobile 0.5s ease-out forwards; /* Change 0.5s here */
  }
}
```

**Recommended timings:**
- Fast: 0.2s - 0.3s (micro-interactions)
- Normal: 0.4s - 0.6s (default)
- Slow: 0.8s - 1s (important reveals)

### Change Animation Type

Replace animation name. Available keyframes:
- `slideInMobile`, `slideInMobileLeft`, `slideInMobileRight`
- `scalePulse`, `fadeInGently`, `floatUp`, `wiggleDown`
- `rotateIn`, `bounceIn`, `scaleIn`, `fadeInUp` (from animations.css)

### Add Custom Animations

Add to `mobile-animations.css`:

```css
@keyframes myCustomAnimation {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 768px) {
  .my-element {
    animation: myCustomAnimation 0.5s ease-out forwards;
  }
}
```

## 📊 Current Animations

| Element | Mobile | Tablet | Desktop | Duration |
|---------|--------|--------|---------|----------|
| Cards | slideInMobile ↑ | slideInMobile ↑ | rotateIn | 0.5-0.6s |
| Buttons | scale(0.97) tap | hover ↑ | hover ↑ | 0.15-0.3s |
| Nav Items | slideInLeft | fadeInUp | fadeInUp | 0.4s |
| Lists | slideInMobile ↑ | - | - | 0.4s |
| Badges | scalePulse ◉ | scalePulse ◉ | scalePulse ◉ | 2s loop |
| Hero Text | floatUp ⬌ | floatUp ⬌ | floatUp ⬌ | 3s loop |

Legend: ↑ = slide up, ↓ = slide down, ◉ = pulse, ⬌ = floating

## 🚀 Advanced: Manual Triggers

### Using JavaScript

```javascript
// Add animation class manually
document.querySelector('.element').classList.add('animated');

// Use data-animate for IntersectionObserver
document.querySelector('.element').setAttribute('data-animate', 'true');

// Check if animations are running
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  // Animations enabled - safe to use
}
```

### Stagger Delays

Cards automatically get staggered with:
- 1st child: 0.05s
- 2nd child: 0.1s
- 3rd child: 0.15s
- etc.

## 🛠️ Troubleshooting

### Animations Not Showing?
1. Check browser support (should work on all modern browsers)
2. Check `prefers-reduced-motion` setting in OS
3. Check console for JavaScript errors
4. Clear browser cache

### Performance Issues?
1. Enable scroll optimization (automatic in mobile-animations.js)
2. Reduce number of animated elements
3. Use shorter animation durations (0.3s instead of 0.6s)
4. Disable animations for very old devices:

```css
@media (max-width: 480px) {
  /* Very old or low-end phones */
  * {
    animation: none !important;
  }
}
```

### Not Smooth on Older Phones?
The JS automatically:
- Pauses animations during scroll
- Reduces duration in landscape mode
- Disables for users with accessibility settings

## 📁 Files Added

- **mobile-animations.css** - All mobile/tablet animation definitions
- **mobile-animations.js** - Animation trigger engine
- Updated **header.php** - Links mobile-animations.css
- Updated **footer.php** - Links mobile-animations.js

## ✅ Verified Working

- ✅ Mobile (iOS Safari, Chrome Android)
- ✅ Tablet (iPad, Android tablets)
- ✅ Desktop (Chrome, Firefox, Safari)
- ✅ Touch devices
- ✅ Low-end devices (auto-optimization)

## 💡 Tips

1. **Less is more** - Subtle animations are better than jarring ones
2. **Consistency** - Keep animation timings consistent across site
3. **Purpose** - Only animate interactive elements and reveals
4. **Accessibility** - Always respect `prefers-reduced-motion`
5. **Testing** - Test on actual devices, not just desktop browsers

---

**Need to disable animations?** Add to your CSS:
```css
* {
  animation: none !important;
  transition: none !important;
}
```

**Questions?** Check the console with `?debug-animations` for detailed logging.
