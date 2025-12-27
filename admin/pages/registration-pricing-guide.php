<?php
// admin/pages/registration-pricing-guide.php
// Documentation for managing registration pricing via course slugs
require_once __DIR__ . '/../auth_check.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Pricing Guide | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body { background: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .guide-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .guide-header { background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: white; padding: 40px; border-radius: 12px; margin-bottom: 30px; }
        .guide-header h1 { font-size: 2.5rem; font-weight: 700; margin-bottom: 15px; }
        .guide-header p { font-size: 1.1rem; opacity: 0.95; }
        .section { background: white; border-radius: 12px; padding: 30px; margin-bottom: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .section-title { font-size: 1.8rem; font-weight: 700; color: #0b1a2c; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
        .section-title i { font-size: 2rem; color: #dc2626; }
        .mapping-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .mapping-table th, .mapping-table td { padding: 15px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .mapping-table th { background: #f3f4f6; font-weight: 700; color: #374151; }
        .mapping-table tr:hover { background: #f9fafb; }
        .code-block { background: #1e293b; color: #e2e8f0; padding: 20px; border-radius: 8px; font-family: "Courier New", monospace; font-size: 0.9rem; overflow-x: auto; margin: 20px 0; }
        .code-comment { color: #94a3b8; }
        .code-variable { color: #38bdf8; }
        .code-string { color: #34d399; }
        .alert-info { background: #e0f2fe; border-left: 4px solid #0ea5e9; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .alert-warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .steps { counter-reset: step-counter; }
        .step { counter-increment: step-counter; position: relative; padding-left: 60px; margin-bottom: 25px; }
        .step::before { content: counter(step-counter); position: absolute; left: 0; top: 0; background: #dc2626; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; }
        .step h4 { font-weight: 700; color: #0b1a2c; margin-bottom: 10px; }
        .visual-diagram { background: #f9fafb; border: 2px dashed #d1d5db; padding: 30px; border-radius: 12px; margin: 25px 0; text-align: center; }
        .flow-arrow { display: inline-block; margin: 0 10px; color: #dc2626; font-size: 1.5rem; }
        .fee-breakdown { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 25px 0; }
        .fee-card { background: #f3f4f6; padding: 20px; border-radius: 8px; text-align: center; }
        .fee-card .amount { font-size: 2rem; font-weight: 700; color: #dc2626; }
        .fee-card .label { font-size: 0.9rem; color: #6b7280; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="guide-container">
        <div class="guide-header">
            <h1><i class='bx bx-money'></i> Registration Pricing Guide</h1>
            <p>Complete guide to managing program prices through course slugs</p>
        </div>

        <!-- Overview -->
        <div class="section">
            <h2 class="section-title"><i class='bx bx-info-circle'></i> How It Works</h2>
            <p style="font-size: 1.1rem; line-height: 1.8; color: #374151;">
                The registration system uses a <strong>slug-based mapping</strong> to determine program prices. 
                Instead of storing prices separately for each registration type, the system maps program types to 
                course slugs in the <code>courses</code> table. This allows you to manage all prices centrally 
                through the <strong>Admin > Courses</strong> menu.
            </p>

            <div class="visual-diagram">
                <h4 style="margin-bottom: 20px; color: #0b1a2c;">Pricing Flow Diagram</h4>
                <div style="font-size: 1.1rem;">
                    <span style="background: #dbeafe; padding: 12px 20px; border-radius: 8px; display: inline-block;">User selects program (JAMB)</span>
                    <span class="flow-arrow">→</span>
                    <span style="background: #fef3c7; padding: 12px 20px; border-radius: 8px; display: inline-block;">System maps to slug ('jamb-post-utme')</span>
                    <span class="flow-arrow">→</span>
                    <span style="background: #dcfce7; padding: 12px 20px; border-radius: 8px; display: inline-block;">Queries courses table</span>
                    <span class="flow-arrow">→</span>
                    <span style="background: #fee2e2; padding: 12px 20px; border-radius: 8px; display: inline-block;">Adds fees (₦2,500)</span>
                    <span class="flow-arrow">→</span>
                    <span style="background: #e0e7ff; padding: 12px 20px; border-radius: 8px; display: inline-block;">Final amount</span>
                </div>
            </div>
        </div>

        <!-- Slug Mapping -->
        <div class="section">
            <h2 class="section-title"><i class='bx bx-code-alt'></i> Program → Slug Mapping</h2>
            <p style="margin-bottom: 20px;">This table shows which course slug controls the price for each program:</p>

            <table class="mapping-table">
                <thead>
                    <tr>
                        <th>Program Type</th>
                        <th>Course Slug</th>
                        <th>Example Course Title</th>
                        <th>Location to Edit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>JAMB</strong></td>
                        <td><code>jamb-post-utme</code></td>
                        <td>JAMB & Post-UTME</td>
                        <td><a href="<?= app_url('admin/pages/courses.php') ?>">Admin > Courses</a></td>
                    </tr>
                    <tr>
                        <td><strong>Post-UTME</strong></td>
                        <td><code>jamb-post-utme</code></td>
                        <td>JAMB & Post-UTME</td>
                        <td><a href="<?= app_url('admin/pages/courses.php') ?>">Admin > Courses</a></td>
                    </tr>
                    <tr>
                        <td><strong>WAEC</strong></td>
                        <td><code>professional-services</code></td>
                        <td>Professional Services</td>
                        <td><a href="<?= app_url('admin/pages/courses.php') ?>">Admin > Courses</a></td>
                    </tr>
                    <tr>
                        <td><strong>Digital Skills</strong></td>
                        <td><code>digital-skills</code></td>
                        <td>Digital Skills Training</td>
                        <td><a href="<?= app_url('admin/pages/courses.php') ?>">Admin > Courses</a></td>
                    </tr>
                    <tr>
                        <td><strong>International</strong></td>
                        <td><code>null</code> (fallback)</td>
                        <td>-</td>
                        <td>Uses default pricing</td>
                    </tr>
                </tbody>
            </table>

            <div class="alert-info">
                <strong>💡 Important:</strong> The <code>slug</code> field in the courses table must <strong>exactly match</strong> 
                these values. If you change a slug, the program's price mapping will break.
            </div>
        </div>

        <!-- Technical Details -->
        <div class="section">
            <h2 class="section-title"><i class='bx bx-code-block'></i> Technical Implementation</h2>
            <p>This is how the system maps programs to slugs in <code>process-registration.php</code>:</p>

            <div class="code-block">
<span class="code-comment">// Define program type to course slug mapping</span>
<span class="code-variable">$slugMap</span> = [
    <span class="code-string">'jamb'</span> => <span class="code-string">'jamb-post-utme'</span>,
    <span class="code-string">'waec'</span> => <span class="code-string">'professional-services'</span>,
    <span class="code-string">'postutme'</span> => <span class="code-string">'jamb-post-utme'</span>,
    <span class="code-string">'digital'</span> => <span class="code-string">'digital-skills'</span>,
    <span class="code-string">'international'</span> => <span class="code-variable">null</span>
];

<span class="code-comment">// Get course slug for this program</span>
<span class="code-variable">$slug</span> = <span class="code-variable">$slugMap</span>[<span class="code-variable">$programType</span>] ?? <span class="code-variable">null</span>;

<span class="code-comment">// Query database for price</span>
<span class="code-variable">$stmt</span> = <span class="code-variable">$pdo</span>-&gt;prepare(<span class="code-string">"SELECT price FROM courses WHERE slug = ?"</span>);
<span class="code-variable">$stmt</span>-&gt;execute([<span class="code-variable">$slug</span>]);
<span class="code-variable">$course</span> = <span class="code-variable">$stmt</span>-&gt;fetch();

<span class="code-comment">// Calculate total with fixed fees</span>
<span class="code-variable">$formFee</span> = 1000;
<span class="code-variable">$cardFee</span> = 1500;
<span class="code-variable">$amount</span> = <span class="code-variable">$course</span>[<span class="code-string">'price'</span>] + <span class="code-variable">$formFee</span> + <span class="code-variable">$cardFee</span>;
            </div>
        </div>

        <!-- Fee Breakdown -->
        <div class="section">
            <h2 class="section-title"><i class='bx bx-calculator'></i> Fee Structure</h2>
            <p style="margin-bottom: 25px;">Every registration includes these fees:</p>

            <div class="fee-breakdown">
                <div class="fee-card">
                    <div class="amount">₦X</div>
                    <div class="label">Base Course Price<br>(from courses table)</div>
                </div>
                <div class="fee-card">
                    <div class="amount">+₦1,000</div>
                    <div class="label">Form Processing Fee<br>(hardcoded)</div>
                </div>
                <div class="fee-card">
                    <div class="amount">+₦1,500</div>
                    <div class="label">Card Transaction Fee<br>(hardcoded)</div>
                </div>
                <div class="fee-card" style="background: #fee2e2;">
                    <div class="amount">₦X+2,500</div>
                    <div class="label"><strong>Total Amount</strong><br>Shown to user</div>
                </div>
            </div>

            <div class="alert-warning">
                <strong>⚠️ Note:</strong> The ₦1,000 form fee and ₦1,500 card fee are <strong>hardcoded</strong> in 
                <code>process-registration.php</code> (lines 57-58). To change these, you must edit the PHP file directly.
            </div>
        </div>

        <!-- How to Edit Prices -->
        <div class="section">
            <h2 class="section-title"><i class='bx bx-edit'></i> How to Edit Prices</h2>
            
            <div class="steps">
                <div class="step">
                    <h4>Navigate to Courses</h4>
                    <p>Go to <strong>Admin > Courses</strong> in the main menu. You'll see a list of all courses with their prices.</p>
                </div>

                <div class="step">
                    <h4>Find the Correct Course</h4>
                    <p>Look for the course with the slug that matches your program (see mapping table above). 
                    For example, to change JAMB prices, find the course with slug <code>jamb-post-utme</code>.</p>
                </div>

                <div class="step">
                    <h4>Click Edit</h4>
                    <p>Click the <strong>Edit</strong> button next to the course. This opens the course editor.</p>
                </div>

                <div class="step">
                    <h4>Update the Price Field</h4>
                    <p>Change the <strong>Price</strong> field to your desired amount (without commas). 
                    Example: <code>15000</code> for ₦15,000.</p>
                </div>

                <div class="step">
                    <h4>Save Changes</h4>
                    <p>Click <strong>Save</strong>. The new price takes effect immediately for new registrations.</p>
                </div>

                <div class="step">
                    <h4>Test the Registration</h4>
                    <p>Go to the public registration page and start a registration for that program. 
                    Verify the price shown = (your new price) + ₦2,500.</p>
                </div>
            </div>
        </div>

        <!-- Troubleshooting -->
        <div class="section">
            <h2 class="section-title"><i class='bx bx-error-circle'></i> Troubleshooting</h2>
            
            <h4 style="margin-top: 20px; color: #0b1a2c;">❌ Price not updating</h4>
            <ul style="line-height: 1.8;">
                <li>Check that the course slug <strong>exactly matches</strong> the mapping (case-sensitive)</li>
                <li>Clear your browser cache and try again</li>
                <li>Verify the course is <strong>active</strong> (not disabled)</li>
            </ul>

            <h4 style="margin-top: 20px; color: #0b1a2c;">❌ Wrong price showing</h4>
            <ul style="line-height: 1.8;">
                <li>Confirm you edited the correct course (check slug, not just title)</li>
                <li>Remember the total includes +₦1,000 form fee + ₦1,500 card fee</li>
                <li>Check for multiple courses with similar slugs</li>
            </ul>

            <h4 style="margin-top: 20px; color: #0b1a2c;">❌ Error during registration</h4>
            <ul style="line-height: 1.8;">
                <li>Ensure the course has a valid numeric price (no commas or symbols)</li>
                <li>Check database logs for SQL errors</li>
                <li>Verify the courses table has the required slug</li>
            </ul>
        </div>

        <!-- Quick Reference -->
        <div class="section">
            <h2 class="section-title"><i class='bx bx-file-blank'></i> Quick Reference</h2>
            
            <table class="mapping-table">
                <thead>
                    <tr>
                        <th>To Change Price For...</th>
                        <th>Edit This Course Slug</th>
                        <th>File Location</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>JAMB Registration</td>
                        <td><code>jamb-post-utme</code></td>
                        <td rowspan="5">Admin > Courses<br>(manage prices in UI)</td>
                    </tr>
                    <tr>
                        <td>Post-UTME Registration</td>
                        <td><code>jamb-post-utme</code></td>
                    </tr>
                    <tr>
                        <td>WAEC Registration</td>
                        <td><code>professional-services</code></td>
                    </tr>
                    <tr>
                        <td>Digital Skills</td>
                        <td><code>digital-skills</code></td>
                    </tr>
                    <tr>
                        <td>International Exams</td>
                        <td>(uses fallback pricing)</td>
                    </tr>
                    <tr>
                        <td>Form Fee (₦1,000)</td>
                        <td>-</td>
                        <td>public/process-registration.php<br>(line 57, hardcoded)</td>
                    </tr>
                    <tr>
                        <td>Card Fee (₦1,500)</td>
                        <td>-</td>
                        <td>public/process-registration.php<br>(line 58, hardcoded)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 40px; padding: 30px; background: #f3f4f6; border-radius: 12px;">
            <p style="font-size: 1.1rem; color: #6b7280; margin-bottom: 20px;">Need more help?</p>
            <a href="<?= app_url('admin/pages/courses.php') ?>" class="btn btn-primary" style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); border: none; padding: 14px 32px; font-weight: 700; border-radius: 8px; text-decoration: none; display: inline-block;">
                Go to Courses Management
            </a>
        </div>
    </div>
</body>
</html>
