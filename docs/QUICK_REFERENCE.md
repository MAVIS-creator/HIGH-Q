# Quick Reference - Forms & Pricing Setup

## 🎯 Quick Links

### For Admins
- **Edit Program Prices**: Admin > Courses > Click program > Edit price field
- **Generate Sitemap**: Admin > Tools/Automator > "Generate Sitemap Now"
- **View Sitemap**: Visit `/sitemap.xml` or click "View Sitemap" in Automator
- **Detailed Guides**: See [PRICING_CONFIGURATION_GUIDE.md](PRICING_CONFIGURATION_GUIDE.md) and [SITEMAP_GENERATION_GUIDE.md](SITEMAP_GENERATION_GUIDE.md)

### For Students
- **Take Quiz**: Visit `/find-your-path-quiz.php`
- **Register**: Click "Register" on your path page and complete the form
- **Pay**: System shows total with fees included

---

## 💰 Pricing Quick Reference

### Base Prices (Configurable in Admin > Courses)
```
JAMB:           ₦10,000
WAEC:           ₦8,000
Post-UTME:      ₦10,000
Digital:        ₦0
International:  ₦15,000
Regular:        TBD (set by admin)
```

### Compulsory Fees (Fixed)
```
Form Fee:       ₦1,000
Card Fee:       ₦1,500
Total Fees:     ₦2,500
```

### Total Cost Formula
```
TOTAL = Base Price + ₦2,500 (fees) + Surcharge (if any)

Examples:
- JAMB:        ₦10,000 + ₦2,500 = ₦12,500
- WAEC:        ₦8,000 + ₦2,500 = ₦10,500
- Digital:     ₦0 + ₦2,500 = ₦2,500
- International: ₦15,000 + ₦2,500 = ₦17,500
```

---

## 📝 Forms Overview

| Form | Fields | Program Type | Status |
|------|--------|--------------|--------|
| **postutme-form.php** | 15+ | Post-UTME | Complete |
| **jamb-form.php** | 20+ | JAMB | Enhanced |
| **waec-form.php** | 15+ | WAEC | Enhanced |
| **digital-form.php** | 18+ | Digital Skills | Enhanced |
| **international-form.php** | 16+ | International | Complete |
| **regular-form.php** | 14+ | General | New |

### Key Form Sections
- Personal Information (name, email, phone, DOB, gender, state, address)
- Program-Specific Details (varies by program)
- Learning Preferences (style, pace, goals)
- Career Goals & Aspirations
- Emergency Contact
- Terms & Conditions

---

## 🎨 Brand Colors (Used in Quiz & Path Pages)

```
JAMB:           #4f46e5 (Purple)
WAEC:           #059669 (Green)
Post-UTME:      #dc2626 (Red)
Digital:        #2563eb (Blue)
International:  #9333ea (Magenta)
```

All gradient backgrounds use: `color` → lighter shade  
Example: `linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)`

---

## 🔧 How to Change Fees

**Location**: `/public/process-registration.php` (lines 57-58)

```php
// Current settings:
$formFee = 1000;   // Line 57
$cardFee = 1500;   // Line 58

// To change:
$formFee = 1500;   // New form fee (e.g., ₦1,500)
$cardFee = 2000;   // New card fee (e.g., ₦2,000)
```

**After changing**:
1. Save the file
2. New registrations will use the new fees
3. Tell users about the change (prices on website may need updating)

---

## 📊 Database Storage

### Payments Table (`payments`)
```
id              ← Auto-increment ID
student_id      ← NULL for public registrations
amount          ← Total with all fees + surcharge
reference       ← Unique payment reference
registration_type ← 'jamb', 'waec', 'postutme', 'digital', 'international', 'regular'
status          ← 'pending', 'completed', 'failed'
metadata        ← JSON with details (program, surcharge, email, etc.)
created_at      ← When payment was created
```

---

## 🌐 SEO & Sitemap

### What's in Sitemap
- ✅ Homepage
- ✅ All 6 path pages (quiz + 5 programs)
- ✅ Registration pages
- ✅ Courses page
- ✅ All blog posts

### How to Update Sitemap
1. Go to Admin > Tools > Automator
2. Click "Generate Sitemap Now"
3. Done! Sitemap auto-generated and saved to `/sitemap.xml`

### Submit to Search Engines
1. **Google**: Google Search Console > Sitemaps > Add `/sitemap.xml`
2. **Bing**: Bing Webmaster Tools > Sitemaps > Submit `/sitemap.xml`

---

## 📋 Form Fields by Program

### JAMB Form Collects
- Personal: First name, surname, other names, email, phone, gender, DOB, state, address
- Education: Current level, career goals, study goals, learning preference
- JAMB: Intended course, institution, 4 subjects with scores
- Other: Weak subjects, emergency contact

### WAEC Form Collects
- Personal: First/last name, email, phone, DOB, gender, state, nationality, address
- Education: Exam type (WAEC/NECO/GCE), year, subjects (checkbox selection)
- Preferences: Career goals, study goals, learning style, current class, weak subjects
- Other: Emergency contact

### Post-UTME Form Collects
- Personal: First name, surname, other names, email, phone, gender, DOB, state, address
- JAMB: Registration number, score, 4 subjects with scores
- Program: Intended course, institution
- Other: Emergency contact (parent/guardian)

### Digital Form Collects
- Personal: First/last name, email, phone, DOB, gender, address
- Program: Skill track (Web Dev, Cybersecurity, Design, etc.), skill level, laptop availability
- Goals: Career goals, study goals, learning preference, tech interests, prior experience
- Other: Emergency contact

### International Form Collects
- Personal: First/last name, email, phone, DOB, gender, nationality, address, state of origin
- Program: Program choice (SAT, IELTS, TOEFL, JUPEB, GRE, GMAT)
- Goals: Target country, passport status, intended course, institution, education level
- Aspirations: Study goals, career motivations, emergency contact

### Regular Form Collects
- Personal: First/last name, email, phone, DOB, gender, state, nationality, address
- Interests: Which programs interested in (checkboxes), preferred contact method
- Message: Optional additional message
- Other: Emergency contact, newsletter opt-in

---

## 🚀 Student Registration Flow

```
1. Visit /find-your-path-quiz.php
   ↓
2. Answer 7 questions about goals/style
   ↓
3. System scores and recommends best path
   ↓
4. Redirect to /path-[program].php with matching colors
   ↓
5. User clicks "Register for this program"
   ↓
6. Form loads (postutme, jamb, waec, digital, international, or regular)
   ↓
7. User fills all required fields
   ↓
8. Form submits to /process-registration.php
   ↓
9. System creates payment record with amount:
   Base Price + ₦1,000 form fee + ₦1,500 card fee
   ↓
10. User redirected to payment gateway (Paystack)
    ↓
11. After payment: Registration confirmed + email sent
```

---

## ⚡ Common Admin Tasks

### Task: Change JAMB Price
1. Go to Admin Dashboard
2. Click "Courses" in sidebar
3. Find "JAMB/Post-UTME" course
4. Click "Edit"
5. Change Price field to new amount (e.g., `12000`)
6. Click "Save"
7. New registrations use new price

### Task: Change Form Fee
1. Open `/public/process-registration.php`
2. Find line 57: `$formFee = 1000;`
3. Change `1000` to new amount (e.g., `1500`)
4. Save file
5. New registrations use new fee

### Task: Generate Sitemap
1. Log into Admin Dashboard
2. Go to Tools > Automator
3. Click "Generate Sitemap Now"
4. See success message
5. Optionally click "View Sitemap" to verify

### Task: Submit Sitemap to Google
1. Go to [Google Search Console](https://search.google.com/search-console)
2. Select your property
3. Go to Sitemaps (left sidebar)
4. Click "Add/Test Sitemap"
5. Type `sitemap.xml`
6. Click "Submit"
7. Done - Google will crawl your sitemap

---

## 📞 Support Documents

Read these for detailed information:
- **[PRICING_CONFIGURATION_GUIDE.md](PRICING_CONFIGURATION_GUIDE.md)** - Complete pricing documentation
- **[SITEMAP_GENERATION_GUIDE.md](SITEMAP_GENERATION_GUIDE.md)** - SEO and sitemap setup
- **[PROJECT_COMPLETION_SUMMARY.md](PROJECT_COMPLETION_SUMMARY.md)** - Full project overview

---

## ✅ Testing Checklist

Before going live:
- [ ] JAMB form loads and submits (₦12,500 total)
- [ ] WAEC form loads and submits (₦10,500 total)
- [ ] Post-UTME form loads and submits (₦12,500 total)
- [ ] Digital form loads and submits (₦2,500 total)
- [ ] International form loads and submits (₦17,500 total)
- [ ] Regular form loads and submits
- [ ] Quiz loads with purple gradient
- [ ] Quiz redirects to correct path page
- [ ] Path pages show correct brand colors
- [ ] Prices display correctly on website
- [ ] Sitemap generates without errors
- [ ] Sitemap includes all path pages
- [ ] Admin can edit prices in Courses

---

## 🎓 Educational Path Matching

The quiz matches based on:
- **Goal** (University, Career, International) - 40% weight
- **Qualification** (In-school, SSCE, Degree) - 20% weight
- **Learning style** (Structured, Project, Mixed) - 15% weight
- **Commitment** (Flexible, Part-time, Intensive) - 10% weight
- **Schedule** (Weekday, Weekend) - 8% weight
- **Experience** (Experienced vs Beginner) - 5% weight
- **Budget** (Flexible) - 2% weight

Paths:
- **JAMB** → For students aiming for Nigerian university entrance
- **WAEC** → For students taking O-Level exams
- **Post-UTME** → For graduates doing screening exams
- **Digital** → For career-focused tech learners
- **International** → For SAT, IELTS, TOEFL, GMAT, GRE seekers

---

**Last Updated**: 2024  
**Version**: 1.0  
**Status**: ✅ Production Ready
