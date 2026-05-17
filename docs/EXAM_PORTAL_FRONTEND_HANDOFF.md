# HIGH-Q Exam Portal Frontend Handoff

This document is for the frontend developer working from the Stitch designs in [stitch_high_q_solid_exam_portal](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal).

## 1. Frontend Mission

Your job is to turn the Stitch screens into real routed pages inside the current repo.

You are not building a disconnected mockup. You are replacing the old static exam UI in [exam](C:/xampp/htdocs/HIGH-Q/exam) and wiring the public site exam entry from [public/exams.php](C:/xampp/htdocs/HIGH-Q/public/exams.php).

Important workspace rule:
- Build the actual exam portal inside `exam/`
- Treat `public/exams.php` only as a bridge from the main site if needed
- Do not spread new exam feature implementation across unrelated `public/` pages

Implementation lock for frontend:
- Your owned page work should live in `exam/` and `exam/admin/`
- Do not create new exam feature pages under `public/`
- Do not redesign unrelated main-site pages as part of this task
- Only touch `public/exams.php` if you are wiring or polishing the bridge into `exam/`

## 2. Current State You Are Replacing

Existing legacy pages:
- [exam/index.html](C:/xampp/htdocs/HIGH-Q/exam/index.html)
- [exam/home.html](C:/xampp/htdocs/HIGH-Q/exam/home.html)
- [exam/quiz.html](C:/xampp/htdocs/HIGH-Q/exam/quiz.html)
- [exam/result.html](C:/xampp/htdocs/HIGH-Q/exam/result.html)
- [exam/admin.html](C:/xampp/htdocs/HIGH-Q/exam/admin.html)
- [exam/admin-result.html](C:/xampp/htdocs/HIGH-Q/exam/admin-result.html)

The public nav currently points to:
- [public/includes/header.php](C:/xampp/htdocs/HIGH-Q/public/includes/header.php)
- link target: `app_url('exams.php')`

So your first route rule is:
- keep `public/exams.php` as a simple public entry bridge only
- make it lead cleanly into the exam portal
- do the real page implementation work in `exam/`

## 3. Frontend Route Map

### Public entry

| Current/Target | Purpose | Stitch source |
|---|---|---|
| `public/exams.php` | optional bridge from main site only | `exam_landing_page` |
| `exam/index.php` | real exam portal landing | `exam_landing_page` |

### Student routes

| Target file | Purpose | Stitch source |
|---|---|---|
| `exam/login.php` | student login | `student_login` |
| `exam/register.php` | student registration | `student_registration` |
| `exam/dashboard.php` | student overview after login | `student_dashboard` |
| `exam/select-exam.php` | exam choice and category | `select_exam` |
| `exam/instructions.php` | rules before starting exam | derive from `select_exam` + current quiz flow |
| `exam/quiz.php` | main CBT interface | `main_cbt_exam_page` |
| `exam/result.php` | single attempt result summary | `exam_results` |
| `exam/review.php` | answer review | `answer_review` |
| `exam/leaderboard.php` | ranking and top performers | `leaderboard` |
| `exam/subscription.php` | pricing plans | `pricing_plans` |
| `exam/billing.php` | checkout/plan completion | `complete_subscription` |
| `exam/subscription-manage.php` | active plan management | `subscription_management` |
| `exam/profile.php` | profile/settings | derive from dashboard styling |
| `exam/history.php` | exam history and previous attempts | derive from dashboard + results patterns |

### Admin routes

| Target file | Purpose | Stitch source |
|---|---|---|
| `exam/admin/dashboard.php` | exam admin home | `admin_dashboard` |
| `exam/admin/exams.php` | manage exams | `manage_exams` |
| `exam/admin/questions.php` | manage questions | `manage_questions` |
| `exam/admin/students.php` | manage students | `manage_students` |
| `exam/admin/results.php` | results and analytics | `results_analytics` |
| `exam/admin/settings.php` | exam settings | `admin_settings` |

### Visual reference

Use [stitch_high_q_solid_exam_portal/academic_excellence_portal/DESIGN.md](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/academic_excellence_portal/DESIGN.md) as the shared visual tone.

## 3A. Detailed Stitch Mapping

This section is the actual screen breakdown the frontend developer should follow.

Do not just copy HTML blindly. Use each Stitch file as a visual and structural reference, then adapt it to the real HIGH-Q routes and backend needs.

### `exam_landing_page`

Source:
- [stitch_high_q_solid_exam_portal/exam_landing_page/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/exam_landing_page/code.html)

Target pages:
- `public/exams.php`
- `exam/index.php`

Keep these sections:
- top navigation with HIGH-Q branding
- hero area with strong exam-prep positioning
- exam board cards for JAMB, WAEC, NECO, GCE
- leaderboard CTA
- feature/resource presentation

Frontend mapping rules:
- `Home` -> `public/index.php` or main public landing route
- `Programs` -> existing public programs page if available
- `Pricing` -> `exam/subscription.php`
- `Login` -> `exam/login.php`
- `Start Practicing` -> `exam/select-exam.php` if authenticated, else `exam/login.php`
- `View Leaderboard` -> `exam/leaderboard.php`
- each exam board card should route into `exam/select-exam.php?board=<board>`

Do not keep:
- static `href="#"` nav items
- remote demo image dependencies as final production dependency when a local asset can replace them

### `student_login`

Source:
- [stitch_high_q_solid_exam_portal/student_login/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/student_login/code.html)

Target page:
- `exam/login.php`

Keep these sections:
- centered login card
- identity field
- password field with visibility toggle
- remember-me checkbox
- forgot-password link
- register link

Frontend mapping rules:
- `Home` -> `exam/index.php`
- `Programs` -> public programs page if retained, else remove from this transactional header
- `Pricing` -> `exam/subscription.php`
- `Forgot password?` -> `exam/forgot-password.php` or hide until backend supports it
- `Register here` -> `exam/register.php`
- submit success -> `exam/dashboard.php`

Implementation note:
- keep error message zone above the form
- add loading state on submit
- on mobile, bottom nav can be simplified or removed if it adds clutter

### `student_registration`

Source:
- [stitch_high_q_solid_exam_portal/student_registration/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/student_registration/code.html)

Target page:
- `exam/register.php`

Keep these sections:
- hero header inside card
- name, email, phone, student category/class
- password and confirm password
- trust footer

Frontend mapping rules:
- `Login` top action -> `exam/login.php`
- `Login here` bottom link -> `exam/login.php`
- successful registration -> `exam/dashboard.php` or `exam/login.php` based on backend flow

Implementation note:
- replace U.S.-style placeholder values with Nigerian-friendly placeholders where needed
- add validation message slots under each field

### `student_dashboard`

Source:
- [stitch_high_q_solid_exam_portal/student_dashboard/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/student_dashboard/code.html)

Target page:
- `exam/dashboard.php`

Keep these sections:
- student top bar
- left navigation
- welcome hero
- recommended action buttons
- stats cards
- progress cards by exam type
- leaderboard shortcut card

Frontend mapping rules:
- sidebar `Dashboard` -> `exam/dashboard.php`
- `Exams` -> `exam/select-exam.php`
- `Results` -> `exam/history.php`
- `Leaderboard` -> `exam/leaderboard.php`
- `Subscriptions` -> `exam/subscription-manage.php`
- `Settings` -> `exam/profile.php`
- `Start Exam` -> `exam/select-exam.php`
- `View History` -> `exam/history.php`
- `View Ranking` -> `exam/leaderboard.php`
- `Continue Practice` / `Continue Prep` -> `exam/select-exam.php` with relevant board preselected

Implementation note:
- backend must supply cards dynamically; do not hardcode fake percentages for final build

### `select_exam`

Source:
- [stitch_high_q_solid_exam_portal/select_exam/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/select_exam/code.html)

Target page:
- `exam/select-exam.php`

Keep these sections:
- exam board selector cards
- subject selection
- difficulty controls
- exam mode selector
- footer summary/action area

Frontend mapping rules:
- sidebar `Dashboard` -> `exam/dashboard.php`
- `Exams` -> current page
- `Results` -> `exam/history.php`
- `Leaderboard` -> `exam/leaderboard.php`
- `Subscriptions` -> `exam/subscription-manage.php`
- `Settings` -> `exam/profile.php`
- chosen board card updates hidden selection state
- final CTA -> `exam/instructions.php`

Implementation note:
- this page is not just a static grid; it must collect the payload needed to start an attempt
- selected board, subjects, difficulty, and mode should be reflected in visible summary text

### `main_cbt_exam_page`

Source:
- [stitch_high_q_solid_exam_portal/main_cbt_exam_page/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/main_cbt_exam_page/code.html)

Target page:
- `exam/quiz.php`

Keep these sections:
- fixed top exam bar
- timer
- question palette sidebar
- progress bar
- question card
- answer options with selected state
- bottom action bar
- flag for review action
- calculator action

Frontend mapping rules:
- `End Exam` -> confirmation modal then submit/exit flow
- palette buttons -> jump to question index
- `Previous` -> previous question
- `Next` -> next question
- `Calculator` -> open calculator panel/modal
- `Flag for Review` -> toggle flagged state
- `Submit Exam` -> confirmation modal then `exam/result.php`

Implementation note:
- the mobile layout is critical here
- keep timer always visible
- mobile must still expose palette access, submit, previous/next, and flagging
- add auto-save indicator with states like `Saving...`, `Saved`, `Offline retry`

### `exam_results`

Source:
- [stitch_high_q_solid_exam_portal/exam_results/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/exam_results/code.html)

Target page:
- `exam/result.php`

Keep these sections:
- large score visualization
- passed/failed badge
- correct/incorrect/skipped cards
- subject performance breakdown
- feedback section
- primary and secondary CTA buttons

Frontend mapping rules:
- `Review Answers` -> `exam/review.php?attempt_id=<id>`
- `Retry Exam` -> `exam/select-exam.php`
- `Back to Dashboard` -> `exam/dashboard.php`

Implementation note:
- support empty states if result data is unavailable
- do not hardcode percentages in final build

### `answer_review`

Source:
- [stitch_high_q_solid_exam_portal/answer_review/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/answer_review/code.html)

Target page:
- `exam/review.php`

Keep these sections:
- review-mode top bar
- corrected question map
- correct vs incorrect option styling
- explanation box
- review navigation

Frontend mapping rules:
- navigator buttons -> jump to review question
- support -> support path if provided, else hide
- `End Exam` should return to results/dashboard, not terminate a live attempt

Implementation note:
- this page needs backend-provided selected answer, correct answer, and explanation payload

### `leaderboard`

Source:
- [stitch_high_q_solid_exam_portal/leaderboard/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/leaderboard/code.html)

Target page:
- `exam/leaderboard.php`

Keep these sections:
- podium top 3
- period toggle
- board filter
- highlighted current-user row
- rank table

Frontend mapping rules:
- sidebar `Dashboard` -> `exam/dashboard.php`
- `Exams` -> `exam/select-exam.php`
- `Results` -> `exam/history.php`
- `Leaderboard` -> current page
- `Subscriptions` -> `exam/subscription-manage.php`
- `Settings` -> `exam/profile.php`

Implementation note:
- backend must provide weekly/all-time filter support
- empty-state copy is needed if there are not enough ranked rows

### `pricing_plans`

Source:
- [stitch_high_q_solid_exam_portal/pricing_plans/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/pricing_plans/code.html)

Target page:
- `exam/subscription.php`

Keep these sections:
- pricing hero
- trial/standard/premium cards
- feature comparison table

Frontend mapping rules:
- `Sign In` -> `exam/login.php`
- `Get Started` -> `exam/register.php`
- `Start Free Trial` -> backend flow for free-trial activation or `exam/register.php`
- `Choose Standard` -> `exam/billing.php?plan=standard`
- `Go Premium` -> `exam/billing.php?plan=premium`

Implementation note:
- currency, copy, and plan naming may need alignment with the business team

### `complete_subscription`

Source:
- [stitch_high_q_solid_exam_portal/complete_subscription/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/complete_subscription/code.html)

Target page:
- `exam/billing.php`

Keep these sections:
- payment method selector
- card detail area
- billing address
- order summary
- promo code
- calculations

Frontend mapping rules:
- `Return to Pricing` -> `exam/subscription.php`
- submit payment -> success state or redirect to `exam/subscription-manage.php`

Implementation note:
- if bank transfer is used for exam subscriptions later, this page should support method switching without layout break

### `subscription_management`

Source:
- [stitch_high_q_solid_exam_portal/subscription_management/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/subscription_management/code.html)

Target page:
- `exam/subscription-manage.php`

Keep these sections:
- revenue/plan status hero adapted for student subscription management
- filters or status widgets if meaningful
- subscription table/cards

Important note:
- this Stitch screen reads more like an admin/subscription operations page than a pure student page
- frontend dev should adapt it into a student-facing “My Subscription” view, not ship it unchanged as an admin grid

Frontend mapping rules:
- `Subscriptions` -> current page
- `Support` -> support path
- if admin-grade filters are not applicable to students, replace them with plan status, renewal date, and upgrade CTA

### `admin_dashboard`

Source:
- [stitch_high_q_solid_exam_portal/admin_dashboard/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/admin_dashboard/code.html)

Target page:
- `exam/admin/dashboard.php`

Keep these sections:
- admin side nav
- academy overview header
- create exam and add question quick actions
- bento KPI cards

Frontend mapping rules:
- `Create Exam` -> `exam/admin/exams.php?action=create`
- `Add Question` -> `exam/admin/questions.php?action=create`
- side nav routes should map to real admin exam pages

### `manage_exams`

Source:
- [stitch_high_q_solid_exam_portal/manage_exams/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/manage_exams/code.html)

Target page:
- `exam/admin/exams.php`

Keep these sections:
- page heading
- create exam CTA
- stat cards
- exam table/list area
- issue/health visibility

Frontend mapping rules:
- `Create New Exam` -> create modal or dedicated create route
- row actions must include edit, archive/deactivate, and inspect details

### `manage_questions`

Source:
- [stitch_high_q_solid_exam_portal/manage_questions/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/manage_questions/code.html)

Target page:
- `exam/admin/questions.php`

Keep these sections:
- question bank header
- add question CTA
- search/filter row
- question cards/table rows
- edit/delete/statistics actions

Frontend mapping rules:
- `Add New Question` -> create modal or route
- edit/delete/stats buttons need real handlers

### `manage_students`

Source:
- [stitch_high_q_solid_exam_portal/manage_students/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/manage_students/code.html)

Target page:
- `exam/admin/students.php`

Keep these sections:
- student management overview
- export and add student actions
- KPI cards
- searchable/filterable student listing

Frontend mapping rules:
- `Export CSV` -> backend export endpoint
- `Add Student` -> create/import path

### `results_analytics`

Source:
- [stitch_high_q_solid_exam_portal/results_analytics/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/results_analytics/code.html)

Target page:
- `exam/admin/results.php`

Keep these sections:
- analytics header
- export results action
- KPI cards
- charts/visualization zones
- review/pending metrics

Frontend mapping rules:
- `Export Results` -> backend export endpoint
- date range control should be wired to query params or dashboard state

### `admin_settings`

Source:
- [stitch_high_q_solid_exam_portal/admin_settings/code.html](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal/admin_settings/code.html)

Target page:
- `exam/admin/settings.php`

Keep these sections:
- settings header
- discard/save actions
- general settings form
- maintenance/support/system cards

Frontend mapping rules:
- `Discard Changes` -> reset unsaved local state
- `Save All Settings` -> backend settings update endpoint

## 3B. Screen-to-Deliverable Matrix

| Stitch folder | Build priority | Target page | Status expectation |
|---|---|---|---|
| `exam_landing_page` | week 2 | `public/exams.php`, `exam/index.php` | polished public entry |
| `student_login` | week 2 | `exam/login.php` | fully wired auth form |
| `student_registration` | week 2 | `exam/register.php` | fully wired auth form |
| `student_dashboard` | week 3 | `exam/dashboard.php` | real data slots |
| `select_exam` | week 3-4 | `exam/select-exam.php` | real filters and CTA |
| `main_cbt_exam_page` | week 4-5 | `exam/quiz.php` | exam-critical |
| `exam_results` | week 6 | `exam/result.php` | result summary |
| `answer_review` | week 6 | `exam/review.php` | review payload ready |
| `leaderboard` | week 7 | `exam/leaderboard.php` | ranked list/filter |
| `pricing_plans` | week 8 | `exam/subscription.php` | plan choice |
| `complete_subscription` | week 8 | `exam/billing.php` | payment/billing |
| `subscription_management` | week 8 | `exam/subscription-manage.php` | student plan state |
| `admin_dashboard` | week 9 | `exam/admin/dashboard.php` | exam admin shell |
| `manage_exams` | week 9 | `exam/admin/exams.php` | CRUD surface |
| `manage_questions` | week 9 | `exam/admin/questions.php` | CRUD surface |
| `manage_students` | week 9 | `exam/admin/students.php` | admin list/manage |
| `results_analytics` | week 10 | `exam/admin/results.php` | analytics surface |
| `admin_settings` | week 10 | `exam/admin/settings.php` | settings surface |

## 4. Navigation Rules

These are mandatory:
- No dead buttons
- No dead links
- No fake menu items
- No `href="#"` in completed pages
- No CTA without a real destination

### Required page-to-page links

#### `public/exams.php`
- primary CTA -> `exam/index.php`
- if student authenticated -> allow CTA to `exam/dashboard.php`

#### `exam/index.php`
- login -> `exam/login.php`
- register -> `exam/register.php`
- browse exams -> `exam/select-exam.php`
- pricing -> `exam/subscription.php`

#### `exam/login.php`
- submit success -> `exam/dashboard.php`
- register link -> `exam/register.php`
- back/home -> `exam/index.php`

#### `exam/register.php`
- submit success -> `exam/dashboard.php` or `exam/login.php` depending on backend contract
- login link -> `exam/login.php`

#### `exam/dashboard.php`
- select exam -> `exam/select-exam.php`
- view history -> `exam/history.php`
- view leaderboard -> `exam/leaderboard.php`
- manage plan -> `exam/subscription-manage.php`
- profile -> `exam/profile.php`

#### `exam/select-exam.php`
- select exam CTA -> `exam/instructions.php`

#### `exam/instructions.php`
- start exam -> `exam/quiz.php`
- back -> `exam/select-exam.php`

#### `exam/quiz.php`
- submit -> `exam/result.php`
- auto-save indicators must be visible

#### `exam/result.php`
- review answers -> `exam/review.php`
- retake/new exam -> `exam/select-exam.php`
- leaderboard -> `exam/leaderboard.php`
- history -> `exam/history.php`

#### `exam/subscription.php`
- choose plan -> `exam/billing.php`

#### `exam/billing.php`
- success -> `exam/subscription-manage.php`

## 5. Frontend Build Rules

Use the existing repo style:
- PHP page shells are acceptable
- HTML/CSS/JS is acceptable
- Reusable partials are preferred
- Do not assume React unless explicitly approved later

### Component targets

Build these as reusable pieces:
- exam header
- student sidebar or dashboard nav
- auth card
- exam card
- timer
- question card
- question palette navigator
- flagged state chip
- result summary card
- leaderboard table
- plan card
- admin stat card
- admin table
- modal
- toast
- loading state
- empty state
- error state

## 6. Responsive Requirements

You were specifically asked not to let things collapse into awkward phone-only mini views.

So for each completed page verify:
- desktop 1440px
- laptop 1024px
- tablet 768px
- mobile 390px

Minimum responsive requirements:
- no overlapping text
- no clipped buttons
- timer stays visible in exam mode
- question nav remains usable on mobile
- results tables collapse gracefully
- admin tables get mobile-safe handling

## 7. Frontend AI Prompts

Use prompts like these when building page by page.

### General page prompt

```text
You are working inside the HIGH-Q PHP repo.
Build the exam portal page using the Stitch reference in stitch_high_q_solid_exam_portal/<screen_name>/code.html.
Target file: <target file path>.
Keep the existing HIGH-Q visual identity and do not invent a different product style.
Make every button and link real. Do not leave href="#" or dead controls.
Use responsive HTML/CSS/JS that works at desktop, tablet, and mobile widths.
Preserve room for backend API integration by using clear ids, data attributes, and component sections.
```

### Link-completeness prompt

```text
Audit this exam page for dead interactions.
For every button, icon action, tab, menu item, and CTA, either wire a real route/action or remove it.
Return a checklist of all interactions and their final destinations.
```

### Responsive prompt

```text
Refine this exam portal page for responsiveness.
Check desktop, 1024px, 768px, and 390px layouts.
Do not allow text overlap, clipped controls, broken tables, or unusable question navigation.
Keep the exam timer and primary actions visible and usable.
```

### CBT-page prompt

```text
Build the HIGH-Q CBT page from the Stitch main_cbt_exam_page design.
Target exam/quiz.php with supporting CSS/JS.
Include a visible timer, subject tabs, question palette, previous/next controls, submit action, flag-for-review state, and auto-save status area.
The page must be fast, focused, and usable on both desktop and mobile.
```

### Stitch-mapping prompt

```text
You are implementing a HIGH-Q exam portal page from an existing Stitch mockup.
Source file: stitch_high_q_solid_exam_portal/<screen>/code.html
Target page: <target page>

First, identify the major UI sections in the Stitch file.
Second, list every visible CTA, icon action, tab, nav item, button, and card action.
Third, map each one to a real HIGH-Q route or frontend handler.
Fourth, remove or redesign any control that does not have a meaningful destination yet.

Do not leave dead interactions.
Do not treat the Stitch HTML as final production markup.
Adapt it to the current HIGH-Q PHP repo and responsive requirements.
```

### Section-preservation prompt

```text
Implement this page from the Stitch mockup, but preserve these specific sections:
<paste preserved sections>

You may simplify visual decoration, but keep the page hierarchy, primary calls to action, and the intended workflow.
Flag any section that feels admin-only, student-only, or out of place for the route being built.
```

### Interaction-audit prompt

```text
Review the finished HIGH-Q exam portal page and produce an interaction audit.
List:
1. every button
2. every text link
3. every icon action
4. every tab or filter
5. every row action

For each item, state:
- label
- destination/handler
- whether it is complete
- whether backend support is still needed
```

## 8. Frontend Acceptance Criteria

Do not mark a page done unless:
- it matches the mapped Stitch screen closely
- it has real routes for visible interactions
- it has loading, empty, and error states where appropriate
- it works on mobile
- it does not break existing exam entry from `public/exams.php`

## 9. Frontend Weekly Deliverables

### Week 1
- route map confirmed
- component inventory
- page shell plan

### Week 2
- `public/exams.php`
- `exam/index.php`
- `exam/login.php`
- `exam/register.php`

### Week 3
- `exam/dashboard.php`
- `exam/select-exam.php`
- `exam/instructions.php`

### Week 4-5
- `exam/quiz.php`

### Week 6
- `exam/result.php`
- `exam/review.php`

### Week 7
- `exam/leaderboard.php`
- `exam/history.php`
- `exam/profile.php`

### Week 8
- `exam/subscription.php`
- `exam/billing.php`
- `exam/subscription-manage.php`

### Week 9-10
- all admin exam UI pages
- final link audit
- final responsive pass
