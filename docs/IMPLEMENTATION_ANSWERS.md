# ANSWERS TO YOUR QUESTIONS

## 1. ✅ Program Pricing Structure - How It Works

### Current System (Already Working)

**Programs get their prices from the `courses` table using slug mapping:**

```php
// In process-registration.php (lines 60-78)
$slugMap = [
    'jamb' => 'jamb-post-utme',        // Links to course with slug 'jamb-post-utme'
    'waec' => 'professional-services',  // Links to course with slug 'professional-services'
    'postutme' => 'jamb-post-utme',    // Links to course with slug 'jamb-post-utme'
    'digital' => 'digital-skills',      // Links to course with slug 'digital-skills'
    'international' => null,            // Uses fallback price (₦15,000)
];
```

### How to Add/Edit Program Prices

**Option 1: Use Existing Courses (Recommended)**

1. Go to **Admin > Courses**
2. Find or create courses with these slugs:
   - `jamb-post-utme` - Sets price for both JAMB and Post-UTME
   - `professional-services` - Sets price for WAEC
   - `digital-skills` - Sets price for Digital programs
   - Create `international-programs` - For International (currently using fallback)
   - Create `regular-registration` - For Regular form (currently no price)

3. Edit the `price` field for each course
4. The registration system automatically uses these prices

**Option 2: Create Separate Pricing Table (Advanced)**

If you want more control, create a new table:

```sql
CREATE TABLE registration_pricing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    program_type VARCHAR(50) NOT NULL UNIQUE,
    base_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    form_fee DECIMAL(10,2) NOT NULL DEFAULT 1000,
    card_fee DECIMAL(10,2) NOT NULL DEFAULT 1500,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_program (program_type)
);

-- Insert default prices
INSERT INTO registration_pricing (program_type, base_price) VALUES
('jamb', 10000),
('waec', 8000),
('postutme', 10000),
('digital', 0),
('international', 15000),
('regular', 5000);
```

Then add an admin page: `admin/pages/registration-pricing.php` to edit these.

### Recommendation

**✅ Use existing courses table** - It's already working and integrated. Just:
1. Create courses with the right slugs
2. Set prices in Admin > Courses
3. Done!

---

## 2. ✅ Post-UTME Form Fields - Using post-utme.php as Reference

I'll now update `/public/forms/postutme-form.php` to match all fields from `post-utme.php`.

**Fields from post-utme.php that will be added:**
- Institution name
- Passport photo upload
- First name, Surname, Other name
- Gender, Address, Parent phone, Email
- NIN number, State of origin, LGA, Place of birth, Nationality, Religion
- Mode of entry, Marital status, Disability
- **JAMB Details**: Registration number, Score, 4 subjects with individual scores
- **O'Level Results**: Exam type, Candidate name, Exam number, Exam year/month, 8 subjects with grades
- WAEC token and serial
- Course choices (1st & 2nd choice, institution)
- **Parent Info**: Father's name/phone/email/occupation, Mother's name/phone/occupation
- **Educational Background**: Primary school/year, Secondary school/year
- **Sponsor Info**: Name, address, email, phone, relationship
- **Next of Kin**: Name, address, email, phone, relationship
- Optional tutor fee checkbox

---

## 3. ✅ SEO Titles for Other Pages

I'll add proper SEO meta tags to:
- All registration form pages
- Post-UTME page
- JAMB page
- WAEC page
- Digital page
- International page
- Courses pages
- About/Contact pages

---

## 4. ✅ Workspace Errors - Will Fix

**Critical Errors to Fix:**
1. CSS vendor prefix missing: `background-clip` needs `-webkit-background-clip` fallback
2. Invalid CSS property `hover:` in inline styles
3. PHP syntax error in welcome-kit-generator.php
4. Undefined `DEBUG_MODE` constant in path pages
5. Markdown linting issues (non-critical but will clean up)

---

## 5. ✅ UI/UX Master Overview Implementation

Will implement based on your specifications:

### Desktop (Premium Mode)
- ✅ Darkened hero blue backgrounds (8-15% darker)
- ✅ Subtle particle effects (neural network style)
- ✅ Glassmorphism cards (.hq-glass)
- ✅ Magnetic CTA buttons (desktop only)
- ✅ Staggered content reveals

### Tablet (Balanced Mode)
- ✅ Reduced motion intensity
- ✅ Glass cards preserved
- ✅ Touch-first interactions
- ❌ No magnetic effects
- ❌ No continuous motion

### Mobile (Conversion Mode)
- ✅ Fade/slide-in only
- ✅ 48px minimum tap targets
- ✅ .hq-ripple on buttons
- ✅ Glass navigation
- ✅ Swipe testimonials
- ✅ Lottie success animations
- ❌ No particles/parallax

### Typography
- ✅ Inter/Montserrat/Outfit
- ✅ Strong hierarchy
- ✅ High contrast
- ✅ Generous spacing

### Performance
- ✅ Respects `prefers-reduced-motion`
- ✅ GPU-friendly transforms
- ✅ Lazy-load visuals
- ✅ Limited concurrent animations

---

## Implementation Plan

1. ✅ Fix workspace errors (CSS, PHP syntax)
2. ✅ Update postutme-form.php with comprehensive fields
3. ✅ Add SEO meta tags to all pages
4. ✅ Implement UI/UX system:
   - Create `hq-ui-system.css` with device-specific rules
   - Add particle effects (desktop only)
   - Implement glassmorphism
   - Add magnetic buttons
   - Create swipe testimonials
   - Add Lottie success animations
5. ✅ Create admin guide for pricing management

---

**Next Steps**: I'll implement all of these systematically in the following order:
1. Fix critical errors first
2. Update forms
3. Add SEO
4. Implement UI/UX system
