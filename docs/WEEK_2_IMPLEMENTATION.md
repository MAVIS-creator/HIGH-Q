# Week 2 Backend Implementation Progress & Tasks

## Objective
Move the exam portal from Week 1 planning/scaffold work into real backend implementation inside `exam/`.

Week 2 focus:
- turn scaffolded auth routes into working endpoints
- make the `exam/` folder the true implementation workspace
- prepare the frontend developer for live auth integration
- start the first real backend contract the frontend can call safely

---

## Week 2 Working Rule

The exam portal implementation now lives in:
- `exam/api/` for endpoints
- `exam/database/` for schema and migration artifacts
- `exam/admin/` for exam admin UI pages
- `exam/` for student-facing exam pages

Allowed exception:
- `public/exams.php` may remain as a bridge from the main site into the exam portal

Not the active workspace:
- `public/api/exam/` is legacy scaffold only
- `exam/api-source/` is a temporary copy and must not be used for new work

---

## Task 1: Fix and Harden Shared Exam API Bootstrap

**Requirement:** Make the shared exam API bootstrap usable for real endpoint work.

### Implemented
- Fixed the database include path to use `public/config/db.php`
- Added a dedicated exam session name: `HIGHQEXAMSESSID`
- Added a session storage fallback under:
  - `storage/framework/sessions/exam`
- Added shared helpers for:
  - request method enforcement
  - JSON responses
  - request payload parsing
  - setup/table existence checks
  - current authenticated exam student loading
  - login/logout session handling
  - student response summary formatting

### Files
- [exam/api/_bootstrap.php](C:/xampp/htdocs/HIGH-Q/exam/api/_bootstrap.php)

### Status
- [x] Completed

---

## Task 2: Implement Exam Student Authentication Endpoints

**Requirement:** Replace placeholder auth scaffold routes with real PHP logic.

### Implemented

#### `POST exam/api/auth/register.php`
- validates `full_name`
- validates `email`
- validates password length
- validates password confirmation when provided
- prevents duplicate email registration
- creates:
  - `exam_students` row
  - `exam_student_profiles` row
- starts exam session immediately after successful registration

#### `POST exam/api/auth/login.php`
- validates login payload
- finds exam student by email
- verifies password hash
- blocks inactive accounts
- updates `last_login_at`
- starts exam session on success

#### `GET exam/api/auth/me.php`
- returns authenticated false when no active session exists
- returns current exam student summary when logged in

#### `POST exam/api/auth/logout.php`
- clears current exam session
- removes matching `exam_sessions` record when available

### Files
- [exam/api/auth/register.php](C:/xampp/htdocs/HIGH-Q/exam/api/auth/register.php)
- [exam/api/auth/login.php](C:/xampp/htdocs/HIGH-Q/exam/api/auth/login.php)
- [exam/api/auth/me.php](C:/xampp/htdocs/HIGH-Q/exam/api/auth/me.php)
- [exam/api/auth/logout.php](C:/xampp/htdocs/HIGH-Q/exam/api/auth/logout.php)

### Status
- [x] Completed

---

## Task 3: Apply and Validate Exam Schema Locally

**Requirement:** Confirm the auth endpoints can run against real exam tables.

### Implemented
- Applied:
  - [exam/database/2026-05-12-exam-portal-schema.sql](C:/xampp/htdocs/HIGH-Q/exam/database/2026-05-12-exam-portal-schema.sql)
- Confirmed `exam_` tables now exist locally
- Confirmed auth flow can run against the real schema

### Status
- [x] Completed locally

---

## Verification Completed

### Code checks
- `php -l exam/api/_bootstrap.php`
- `php -l exam/api/auth/register.php`
- `php -l exam/api/auth/login.php`
- `php -l exam/api/auth/me.php`
- `php -l exam/api/auth/logout.php`

All passed.

### Runtime checks completed
- schema applied successfully from `exam/database/...`
- real `register` smoke test passed
- real `me` session check passed
- real `logout` check passed
- real `login` smoke test passed
- final `me` check passed again after login

### Cleanup completed
- temporary smoke-test student rows were removed after validation

---

## Week 2 Progress Summary

### Completed this week
- [x] real auth bootstrap in `exam/api/_bootstrap.php`
- [x] real exam student registration endpoint
- [x] real exam student login endpoint
- [x] real current-session endpoint
- [x] real logout endpoint
- [x] local schema application and auth smoke verification

### Still remaining in Week 2
- [ ] implement `exam/api/exams/index.php`
- [ ] implement `exam/api/exams/show.php`
- [ ] implement `exam/api/exams/start.php`
- [ ] return mock-safe but real JSON payloads for frontend exam discovery pages

---

## Frontend Handoff Impact

The frontend developer can now integrate against real auth routes:
- `POST /exam/api/auth/register.php`
- `POST /exam/api/auth/login.php`
- `POST /exam/api/auth/logout.php`
- `GET /exam/api/auth/me.php`

Expected response shape remains:

```json
{
  "status": "ok",
  "message": "Human-readable summary",
  "data": {},
  "errors": []
}
```

---

## Next Implementation Target

The next backend block should stay inside Week 2 and focus on exam discovery:

1. `exam/api/exams/index.php`
2. `exam/api/exams/show.php`
3. `exam/api/exams/start.php`

That will give the frontend developer the first complete auth + exam-launch integration surface.
