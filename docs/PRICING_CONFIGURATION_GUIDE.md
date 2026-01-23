# Pricing Configuration Guide

## How to Edit Prices for Registration Forms

High Q Tutorial has a flexible pricing system that allows you to configure prices for each educational program. This guide shows you exactly how to set and manage prices.

---

## 1. Program Base Pricing (Admin > Courses)

Each registration program (JAMB, WAEC, Post-UTME, Digital, International) has a base price that you can edit.

### How to Set Base Prices:

1. **Go to Admin Dashboard** → Click on **Courses** in the sidebar
2. **Find the program** you want to price:
   - "JAMB/Post-UTME" - For JAMB/UTME registration
   - "Professional Services" - For WAEC/NECO/GCE exams
   - "Digital Skills" - For Digital skills training
   - (Look for international program courses)

3. **Click Edit** on the course card

4. **In the Price field**, enter the amount (e.g., `10000` for ₦10,000)
   - Leave blank to show "Varies" on the website
   - Enter numbers only (no commas or currency symbols)
   - System supports decimal values (e.g., `5000.50`)

5. **Click Save**

### Default Base Prices (in `/public/process-registration.php`):
```
JAMB:           ₦10,000
WAEC:           ₦8,000
Post-UTME:      ₦10,000
Digital:        ₦0 (free track with tutor fee)
International:  ₦15,000
```

These are used as fallback if no price is set in the Courses table.

---

## 2. Compulsory Fees (Form Fee + Card Fee)

In addition to the base program price, there are two compulsory fees applied to ALL registrations:

- **Form Fee**: ₦1,000
- **Card Fee**: ₦1,500
- **Total Fees**: ₦2,500

### How to Change Compulsory Fees:

**Location**: `/public/process-registration.php` (lines 57-58)

```php
$formFee = 1000;   // Change this value
$cardFee = 1500;   // Change this value
```

To change fees:
1. Edit `/public/process-registration.php`
2. Locate lines 57-58 (marked with `// Form Fee` and `// Card Fee` comments)
3. Update the values
4. Save the file

**Note**: Currently, these fees are hardcoded. Future enhancement could move them to a database table for easier admin configuration.

---

## 3. Total Registration Amount Calculation

The final amount students pay = **Base Price + Form Fee + Card Fee**

### Example Calculations:

**JAMB Registration**:
```
Base Price:     ₦10,000
Form Fee:       + ₦1,000
Card Fee:       + ₦1,500
─────────────────────
Total:          ₦12,500
```

**WAEC Registration**:
```
Base Price:     ₦8,000
Form Fee:       + ₦1,000
Card Fee:       + ₦1,500
─────────────────────
Total:          ₦10,500
```

**Digital Skills**:
```
Base Price:     ₦0
Form Fee:       + ₦1,000
Card Fee:       + ₦1,500
─────────────────────
Total:          ₦2,500
```

---

## 4. Payment Surcharges (Optional)

The system supports optional surcharges that are applied AFTER calculating base + fees.

### Surcharge Configuration:

**Location**: `/config/payments.php`

Supported surcharge configurations:
```php
'surcharge_percent' => 2.5,  // Apply 2.5% surcharge on total
'surcharge_fixed' => 500,    // Apply fixed ₦500 surcharge
'surcharge' => 1.5,          // Legacy: treated as percent
```

### Example with Surcharge:

**JAMB with 2% Surcharge**:
```
Base Price:     ₦10,000
Form Fee:       + ₦1,000
Card Fee:       + ₦1,500
Subtotal:       = ₦12,500
Surcharge (2%): + ₦250
─────────────────────
Total:          ₦12,750
```

---

## 5. Form/Card Fee Tracking in Database

The payment details are stored in the `payments` table with the following structure:

```sql
payments table columns:
- id: Unique payment record ID
- student_id: Student/user ID (NULL for public registrations)
- amount: Total amount to be paid (includes fees + surcharge)
- payment_method: Method used (e.g., 'online', 'bank')
- reference: Payment reference number
- status: Payment status (pending, completed, failed)
- registration_type: Type of registration (jamb, waec, postutme, digital, international, regular)
- metadata: JSON data containing:
  - program_type
  - registration_id
  - email
  - phone
  - name
  - surcharge details
- created_at: Payment creation timestamp
```

### Tracking Example:

When a student registers for JAMB:
1. `amount` field stores: Base Price + Form Fee + Card Fee + any Surcharge
2. `registration_type` is set to: `"jamb"`
3. `metadata` contains all registration details
4. Payment gateway receives the `amount` value for processing

---

## 6. Form Fields Associated with Pricing

Different registration forms collect different information:

### JAMB Form:
- Program type: JAMB/UTME
- Cost: Base ₦10,000 + Fees ₦2,500 = **₦12,500**
- Fields: Personal info, JAMB subjects, career goals, learning preferences

### WAEC Form:
- Program type: WAEC/NECO/GCE
- Cost: Base ₦8,000 + Fees ₦2,500 = **₦10,500**
- Fields: Personal info, subject selection, current class, career goals

### Post-UTME Form:
- Program type: Post-UTME Screening
- Cost: Base ₦10,000 + Fees ₦2,500 = **₦12,500**
- Fields: Personal info, JAMB details, institution choice, emergency contact

### Digital Skills Form:
- Program type: Digital Skills
- Cost: Base ₦0 + Fees ₦2,500 = **₦2,500**
- Fields: Personal info, skill track, experience level, career goals

### International Programs Form:
- Program type: International (SAT, IELTS, TOEFL, etc.)
- Cost: Base ₦15,000 + Fees ₦2,500 = **₦17,500**
- Fields: Personal info, program selection, target country, study goals

### Regular Form:
- Program type: General Registration
- Cost: Base price TBD + Fees ₦2,500
- Fields: Personal info, interests, newsletter signup (no specific program)

---

## 7. Admin Best Practices

### For Setting Prices:

1. **Review your costs**: Ensure base prices cover your operation costs
2. **Consider the market**: Compare with competitors
3. **Test the system**: Make a test registration to verify calculations
4. **Communicate clearly**: Display prices clearly on the website
5. **Keep records**: Document any price changes and their effective dates

### For Fee Management:

1. **Consistent fees**: Keep form fee and card fee consistent across all programs
2. **Annual reviews**: Review and update fees quarterly
3. **Special offers**: For promotions, adjust base price (don't bypass fees)
4. **Future enhancement**: Consider moving hardcoded fees to database settings

---

## 8. Common Questions

**Q: Can I have different fees for different programs?**  
A: Currently no - all programs use the same Form Fee (₦1,000) and Card Fee (₦1,500). This is hardcoded in `/public/process-registration.php`. Future enhancement could make this per-program.

**Q: Can students see the breakdown of costs?**  
A: Currently, the system shows a total amount. Future enhancement could add a cost breakdown display on the registration page.

**Q: How do I apply a discount?**  
A: Reduce the base program price in the Courses admin. The discount will be reflected in the final payment amount.

**Q: Can I track which fee component was paid?**  
A: The `payments` table stores the total amount. The `metadata` field contains `surcharge` details but not form/card fee breakdown. This could be enhanced in future versions.

**Q: How often can I change prices?**  
A: Prices can be changed anytime from the Courses admin page. Changes apply immediately to new registrations.

---

## 9. Technical Reference

### Key Files:

- **Price configuration**: `/admin/pages/courses.php` (edit base prices)
- **Fee calculation**: `/public/process-registration.php` (lines 49-80)
- **Payment config**: `/config/payments.php` (surcharge settings)
- **Database schema**: `payments` table (tracks all transactions)
- **Registration types**: defined in `process-registration.php` line 50-56

### Quick Code Reference:

```php
// Fee calculation code
$basePrices = ['jamb' => 10000, 'waec' => 8000, ...];
$formFee = 1000;
$cardFee = 1500;
$amount = $basePrices[$programType] + $formFee + $cardFee;
```

---

## 10. Future Enhancements

Recommended improvements for more flexibility:

1. Move form fee and card fee to `site_settings` table for admin configuration
2. Support per-program fee variations
3. Add cost breakdown display on payment confirmation page
4. Create bulk pricing/discount rules
5. Add payment history and revenue reports
6. Support seasonal pricing adjustments
7. Create fee waiver system for scholarship recipients

---

**Last Updated**: 2024  
**Version**: 1.0  
**For Questions**: Contact High Q Tutorial Administrator
