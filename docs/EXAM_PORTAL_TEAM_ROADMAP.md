# HIGH-Q Exam Portal Team Roadmap

This document is the shared delivery plan for the exam portal workstream inside the current HIGH-Q repo.

It is written for two parallel contributors:
- Frontend developer
- Backend developer

The goal is to keep both sides aligned, keep scope under control, and land an MVP in 10 weeks with 2 optional buffer weeks.

## 1. Project Intent

The exam portal is not a separate random product. It is a guided exam experience inside the current HIGH-Q site.

Current repo facts:
- Public site exam entry currently lives at [public/exams.php](C:/xampp/htdocs/HIGH-Q/public/exams.php)
- Public navbar currently points to `app_url('exams.php')` from [public/includes/header.php](C:/xampp/htdocs/HIGH-Q/public/includes/header.php)
- Existing static exam prototype lives in [exam](C:/xampp/htdocs/HIGH-Q/exam)
- New UI source-of-truth mockups live in [stitch_high_q_solid_exam_portal](C:/xampp/htdocs/HIGH-Q/stitch_high_q_solid_exam_portal)
- Current exam prototype is localStorage-based and documented in [EXAM_SYSTEM_COMPREHENSIVE_OVERVIEW.md](C:/xampp/htdocs/HIGH-Q/EXAM_SYSTEM_COMPREHENSIVE_OVERVIEW.md)
- New rule: the exam portal implementation itself should live inside `exam/` wherever practical

## 2. Delivery Outcome

By MVP completion, the system should support:
- Student registration and login for exam portal
- Student dashboard
- Exam selection
- Instructions page
- CBT exam session with timer and auto-save
- Submission and result view
- Answer review
- Leaderboard
- Subscription plans and payment status UI
- Admin exam dashboard
- Admin exam, question, student, result, and settings management

## 3. Scope Guardrails

These are important. They keep the work from drifting.

### Frontend developer owns
- Visual implementation from Stitch UI
- Responsive layouts
- Real page routing and page-to-page linking
- Button/link completeness
- API integration once backend contracts exist
- Frontend state handling inside exam flows

### Backend developer owns
- Database schema for exam portal
- Authentication/session rules for exam users
- JSON API contracts
- Exam attempt lifecycle
- Scoring, persistence, and leaderboard rules
- Subscription and payment status logic
- Admin CRUD and reporting endpoints

### Neither developer should do without alignment
- Rewrite the whole main site header/footer architecture
- Reuse unrelated admissions/payment tables blindly
- Invent API shapes that are not in the shared contract
- Add extra features beyond the agreed week target
- Break the current public site while building the exam portal

### Workspace lock
- Treat `exam/` as the exam portal workspace
- New student pages belong in `exam/`
- New exam admin pages belong in `exam/admin/`
- New exam APIs belong in `exam/api/`
- New exam schema/migration artifacts belong in `exam/database/`
- `public/exams.php` is allowed only as a bridge into the portal
- Anything outside `exam/` should be touched only when bridging into the portal or reusing shared infrastructure intentionally

## 4. Recommended Repo Working Boundaries

Use the current repo shape instead of pretending this is a brand-new monorepo.

### Student-facing exam pages
- `public/exams.php` = optional public bridge page only
- `exam/` = actual exam portal UI surface

### Backend/API
- `exam/api/` = exam portal API endpoints
- `exam/database/` = exam portal schema/migration artifacts
- `config/` = shared DB/bootstrap usage where needed
- `src/` = reusable PHP service classes if the backend dev wants cleaner structure

### Admin
- `exam/admin/` for fast isolated UI shells during implementation
- Later integration path can move stable admin screens into main `admin/` if desired

## 5. Route Strategy

To avoid navbar churn, keep this stable:
- Navbar exam link may continue to hit `public/exams.php` if you want a bridge from the main site

Then `public/exams.php` should only act as a bridge into the exam portal:
- `public/exams.php` shows the stitched landing page or redirects into `exam/index.php`
- New feature work should happen in `exam/`, not inside `public/`

That means:
- frontend implementation target = `exam/...`
- backend implementation target = `exam/api/...`
- schema artifact target = `exam/database/...`

Recommended student routes:
- `/public/exams.php`
- `/exam/index.php`
- `/exam/login.php`
- `/exam/register.php`
- `/exam/dashboard.php`
- `/exam/select-exam.php`
- `/exam/instructions.php`
- `/exam/quiz.php`
- `/exam/result.php`
- `/exam/review.php`
- `/exam/leaderboard.php`
- `/exam/subscription.php`
- `/exam/billing.php`
- `/exam/profile.php`
- `/exam/history.php`

Recommended admin routes for exam portal:
- `/exam/admin/login.php`
- `/exam/admin/dashboard.php`
- `/exam/admin/exams.php`
- `/exam/admin/questions.php`
- `/exam/admin/students.php`
- `/exam/admin/results.php`
- `/exam/admin/subscriptions.php`
- `/exam/admin/settings.php`

## 6. Timeline

Recommended schedule:
- Core MVP target: 10 weeks
- Buffer and fixes: 2 extra weeks
- Total window: 10 to 12 weeks

This fits your 2 to 4 month window without forcing the team to rush badly.

## 7. Weekly Plan

### Week 1: Project foundation
- Frontend:
  - Audit every Stitch screen
  - Extract major sections, CTA list, and route targets from each Stitch file before implementation starts
  - Create page inventory
  - Set up shared exam UI tokens, reusable layout, and component checklist
  - Replace static placeholders in `exam/` naming plan only, not full rebuild yet
- Backend:
  - Finalize exam schema
  - Define API contract draft
  - Create migration plan and seed approach
  - Decide auth model for exam portal

### Week 2: Shells and contracts
- Frontend:
  - Build routed shells for landing, login, registration, dashboard, select exam
  - Make all nav, buttons, and CTA destinations explicit
- Backend:
  - Create DB tables
  - Build auth endpoints
  - Return mock-safe JSON for frontend integration

### Week 3: Student auth and onboarding
- Frontend:
  - Connect login/register screens
  - Hook dashboard to live profile and subscription status
- Backend:
  - Register/login/logout/me endpoints
  - Session handling
  - Validation and error response standards

### Week 4: Exam discovery flow
- Frontend:
  - Select exam page
  - Instructions page
  - Exam launch flow
- Backend:
  - Exams list/details/start attempt endpoints
  - Eligibility rules based on subscription

### Week 5: Core CBT experience
- Frontend:
  - Main CBT page from Stitch
  - Question navigator
  - Subject tabs
  - Timer
  - Flag question state
- Backend:
  - Attempt questions endpoint
  - Save answer endpoint
  - Attempt status endpoint

### Week 6: Submission and result experience
- Frontend:
  - Result page
  - Answer review page
  - Empty/error/loading states
- Backend:
  - Submit attempt
  - Score calculation
  - Result summary
  - Review payload

### Week 7: Leaderboard and history
- Frontend:
  - Leaderboard page
  - History page
  - Profile page
- Backend:
  - Leaderboard endpoints
  - Exam history endpoint
  - Rank calculations

### Week 8: Subscription flow
- Frontend:
  - Pricing page
  - Billing page
  - Subscription management page
- Backend:
  - Subscription plans
  - Subscribe/verify endpoints
  - Access control middleware for paid exams

### Week 9: Admin exam management
- Frontend:
  - Admin dashboard
  - Manage exams
  - Manage questions
  - Manage students
- Backend:
  - Admin auth
  - Admin CRUD for exams/questions/students
  - Import question endpoint planning

### Week 10: Admin analytics and hardening
- Frontend:
  - Results analytics
  - Admin settings
  - Final responsiveness pass
- Backend:
  - Results reporting
  - Settings
  - Audit logs
  - Security hardening

### Week 11-12: Buffer, QA, polish
- Joint:
  - Bug fixing
  - Cross-browser checks
  - Mobile checks
  - Link/CTA audit
  - API contract mismatch fixes
  - Deployment prep

## 8. Definition of Done Per Week

Each week is only done if:
- Pages render without broken layout
- Every visible button/link has a real target or action
- API contract for touched pages is documented
- Error/loading/empty states exist
- No dead `href="#"` placeholders remain in completed pages
- The branch has a short changelog summary

## 9. Weekly Sync Format

Every Friday both developers should answer:
- What pages/modules were completed?
- What API contracts changed?
- What is blocked?
- What must the other developer consume next week?
- What was deferred intentionally?

## 10. Frontend Prompting Rule

The frontend developer should not use AI with vague prompts like “build the page.”

For every Stitch-backed page, the prompt must include:
- the exact Stitch source file
- the exact target route/file
- the sections that must be preserved
- the links/buttons that must become real
- the responsive requirement

Primary reference:
- [docs/EXAM_PORTAL_FRONTEND_HANDOFF.md](C:/xampp/htdocs/HIGH-Q/docs/EXAM_PORTAL_FRONTEND_HANDOFF.md)

## 11. Branch Discipline

Recommended branch categories:
- `frontend/exam-foundation`
- `frontend/exam-student-pages`
- `frontend/exam-cbt`
- `frontend/exam-admin`
- `backend/exam-schema`
- `backend/exam-auth`
- `backend/exam-engine`
- `backend/exam-results`
- `backend/exam-subscriptions`
- `backend/exam-admin`

If you already created branches, keep the same spirit even if the names differ.

## 12. MVP Release Checklist

Must-have before release:
- Student can create account and log in
- Student can select an exam and start it
- Answers save during the session
- Student can submit and see result
- Student can review answers
- Leaderboard works
- Subscription gating works
- Admin can manage exams
- Admin can manage questions
- Admin can view students and results
- Mobile layout is usable on login, dashboard, exam select, and quiz pages

Not required for MVP:
- Essay marking
- AI recommendations
- PDF result export
- Proctoring
- Native mobile app
- Rich media question authoring

## 13. Handoff Documents To Use Alongside This

Read these together:
- [docs/EXAM_PORTAL_FRONTEND_HANDOFF.md](C:/xampp/htdocs/HIGH-Q/docs/EXAM_PORTAL_FRONTEND_HANDOFF.md)
- [docs/EXAM_PORTAL_BACKEND_HANDOFF.md](C:/xampp/htdocs/HIGH-Q/docs/EXAM_PORTAL_BACKEND_HANDOFF.md)
