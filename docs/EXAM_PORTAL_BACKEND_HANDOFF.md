# HIGH-Q Exam Portal Backend Handoff

This document is for the backend developer building the exam portal logic inside the current HIGH-Q PHP/MySQL repo.

## 1. Backend Mission

Your job is to turn the current static/localStorage exam prototype into a real database-backed exam system that supports the frontend pages defined in:
- [docs/EXAM_PORTAL_FRONTEND_HANDOFF.md](C:/xampp/htdocs/HIGH-Q/docs/EXAM_PORTAL_FRONTEND_HANDOFF.md)

The frontend developer will be building page routes in `exam/` and needs stable API contracts from you.

## 2. Current State

The current exam system is still largely static:
- [exam/home.js](C:/xampp/htdocs/HIGH-Q/exam/home.js)
- [exam/quiz.js](C:/xampp/htdocs/HIGH-Q/exam/quiz.js)
- [exam/admin.js](C:/xampp/htdocs/HIGH-Q/exam/admin.js)
- [exam/data/questions.json](C:/xampp/htdocs/HIGH-Q/exam/data/questions.json)

That means the backend must replace:
- localStorage-based answers
- localStorage-based results
- static question source
- no-auth exam flow

## 3. Backend Boundaries

Stay inside these boundaries:
- Do not break the existing admissions/public site flows
- Do not reuse unrelated tables casually
- Prefer dedicated exam-prefixed tables
- Return JSON for the exam frontend
- Keep APIs under a dedicated exam namespace

Recommended API location:
- `public/api/exam/`

Recommended structure:
- `public/api/exam/auth/`
- `public/api/exam/student/`
- `public/api/exam/exams/`
- `public/api/exam/attempts/`
- `public/api/exam/results/`
- `public/api/exam/leaderboard/`
- `public/api/exam/subscriptions/`
- `public/api/exam/admin/`

## 4. Recommended Database Plan

Use dedicated exam-prefixed tables.

### Core tables
- `exam_students`
- `exam_student_profiles`
- `exam_sessions`
- `exam_categories`
- `exam_subjects`
- `exam_definitions`
- `exam_definition_subjects`
- `exam_questions`
- `exam_question_options`
- `exam_attempts`
- `exam_attempt_subjects`
- `exam_attempt_answers`
- `exam_attempt_flags`
- `exam_results`
- `exam_result_subject_breakdowns`
- `exam_subscription_plans`
- `exam_student_subscriptions`
- `exam_payments`
- `exam_leaderboard_cache`
- `exam_admins`
- `exam_activity_logs`
- `exam_settings`

### Why prefixed tables

The main repo already has many unrelated site tables. Prefixing avoids collisions and lets you migrate the exam portal independently.

## 5. Auth Recommendation

Use a dedicated exam student auth flow for MVP.

Recommended:
- `exam_students` stores student account credentials
- password hash with PHP native hashing
- PHP session-based auth is acceptable for MVP
- admin exam auth can be separate from main site admin for now

Do not force reuse of current public registration users unless a later integration phase is approved.

## 6. API Contract

### Auth

| Method | Route | Purpose |
|---|---|---|
| `POST` | `/public/api/exam/auth/register.php` | create exam student account |
| `POST` | `/public/api/exam/auth/login.php` | login |
| `POST` | `/public/api/exam/auth/logout.php` | logout |
| `GET` | `/public/api/exam/auth/me.php` | current student session |

### Exams

| Method | Route | Purpose |
|---|---|---|
| `GET` | `/public/api/exam/exams/index.php` | list available exams |
| `GET` | `/public/api/exam/exams/show.php?id=` | single exam details |
| `POST` | `/public/api/exam/exams/start.php` | create attempt |

### Attempts

| Method | Route | Purpose |
|---|---|---|
| `GET` | `/public/api/exam/attempts/questions.php?attempt_id=` | fetch questions for attempt |
| `POST` | `/public/api/exam/attempts/save-answer.php` | save answer |
| `POST` | `/public/api/exam/attempts/flag.php` | flag/unflag question |
| `GET` | `/public/api/exam/attempts/status.php?attempt_id=` | timer/progress status |
| `POST` | `/public/api/exam/attempts/submit.php` | submit exam |

### Results

| Method | Route | Purpose |
|---|---|---|
| `GET` | `/public/api/exam/results/show.php?attempt_id=` | result summary |
| `GET` | `/public/api/exam/results/review.php?attempt_id=` | review payload |
| `GET` | `/public/api/exam/results/history.php` | student history |

### Leaderboard

| Method | Route | Purpose |
|---|---|---|
| `GET` | `/public/api/exam/leaderboard/index.php` | leaderboard list |
| `GET` | `/public/api/exam/leaderboard/my-rank.php` | student rank |

### Subscriptions

| Method | Route | Purpose |
|---|---|---|
| `GET` | `/public/api/exam/subscriptions/plans.php` | plan list |
| `GET` | `/public/api/exam/subscriptions/current.php` | active plan |
| `POST` | `/public/api/exam/subscriptions/subscribe.php` | start subscription |
| `POST` | `/public/api/exam/subscriptions/verify.php` | verify payment status |

### Admin

| Method | Route | Purpose |
|---|---|---|
| `POST` | `/public/api/exam/admin/login.php` | admin login |
| `GET` | `/public/api/exam/admin/dashboard.php` | admin dashboard payload |
| `GET` | `/public/api/exam/admin/exams.php` | list exams |
| `POST` | `/public/api/exam/admin/exams-create.php` | create exam |
| `POST` | `/public/api/exam/admin/exams-update.php` | update exam |
| `POST` | `/public/api/exam/admin/questions-create.php` | create question |
| `POST` | `/public/api/exam/admin/questions-update.php` | update question |
| `GET` | `/public/api/exam/admin/students.php` | list students |
| `GET` | `/public/api/exam/admin/results.php` | results analytics |
| `GET` | `/public/api/exam/admin/settings.php` | settings payload |
| `POST` | `/public/api/exam/admin/settings-update.php` | update settings |

## 7. Response Shape Rules

Use one consistent JSON shape:

```json
{
  "status": "ok",
  "message": "Human-readable summary",
  "data": {},
  "errors": []
}
```

For validation failures:

```json
{
  "status": "error",
  "message": "Validation failed",
  "data": null,
  "errors": {
    "email": "Email is required"
  }
}
```

## 8. Frontend Dependencies You Must Support

The frontend pages will need these backend payloads:

### `exam/dashboard.php`
- student name
- active plan
- exams available
- recent attempts
- quick stats

### `exam/select-exam.php`
- exam list
- category
- subject count
- duration
- eligibility state

### `exam/instructions.php`
- exam rules
- duration
- subject list
- attempt restrictions

### `exam/quiz.php`
- attempt id
- timer end or remaining seconds
- ordered subject/question payload
- saved answers
- flagged questions

### `exam/result.php`
- total score
- subject breakdown
- pass/fail or grade
- percentile if available

### `exam/review.php`
- question
- selected answer
- correct answer
- explanation placeholder support

### `exam/leaderboard.php`
- ranked rows
- student’s own rank
- filters by exam and period

### `exam/subscription.php` and `exam/billing.php`
- plan cards
- price
- benefits
- payment state

## 9. Security Requirements

Must include:
- password hashing
- session validation
- CSRF protection for POST forms where applicable
- strict input validation
- prepared statements only
- student-only and admin-only guards
- attempt ownership checks
- submit-once protection
- timer tamper protection on the server side
- activity logging for sensitive admin actions

## 10. Backend AI Prompts

### General endpoint prompt

```text
Build a PHP endpoint inside the HIGH-Q repo for the exam portal.
Target path: <target file>.
Use PDO, prepared statements, validation, and JSON responses in the shared contract shape.
Do not touch unrelated admissions or public-site tables.
Prefer new exam-prefixed tables for persistence.
Return clean success, validation, and auth errors.
```

### Schema prompt

```text
Design a MySQL schema for the HIGH-Q exam portal inside the current repo.
Use exam-prefixed tables, support student auth, exam definitions, question banks, attempts, answers, results, subscriptions, and leaderboard data.
Avoid conflicts with the existing site tables.
```

### Attempt engine prompt

```text
Implement the exam attempt lifecycle for HIGH-Q:
start attempt, fetch questions, save answer, flag question, get attempt status, submit attempt, and compute result.
Use server-side timer validation and ownership checks.
```

### Admin CRUD prompt

```text
Build admin CRUD endpoints for exam definitions and question management in the HIGH-Q exam portal.
Use validation, authentication, activity logs, and clear JSON responses.
Support future CSV import without forcing it into the first endpoint version.
```

## 11. Backend Weekly Deliverables

### Week 1
- schema draft
- route map
- auth/session decision

### Week 2
- migrations or schema SQL
- auth register/login/logout/me

### Week 3
- dashboard and profile support

### Week 4
- exams list/details/start

### Week 5
- attempt questions/save-answer/flag/status

### Week 6
- submit/result/review/history

### Week 7
- leaderboard endpoints

### Week 8
- subscription plan/current/subscribe/verify

### Week 9
- admin login
- admin exams/questions/students

### Week 10
- admin results/settings
- hardening
- audit logs

## 12. Backend Definition of Done

An endpoint or module is not done unless:
- it has validation
- it has auth rules where needed
- it uses the shared JSON contract
- it is tested manually against the real frontend flow it supports
- it does not mutate unrelated site functionality
